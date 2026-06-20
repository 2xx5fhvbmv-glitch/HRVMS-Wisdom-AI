<?php

namespace App\Services\Wisdom;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client around the OpenRouter chat-completions API that drives the
 * Wisdom AI assistant, including the multi-step tool-calling loop.
 */
class OpenRouterClient
{
    /** Max tool-call rounds before we force a final answer (safety bound). */
    const MAX_TOOL_ROUNDS = 5;

    /**
     * Run a chat turn.
     *
     * @param array $ctx      Access context from WisdomAccess::context()
     * @param array $history  Prior turns: [['role' => 'user'|'assistant', 'content' => '...'], ...]
     * @return array          ['ok' => bool, 'reply' => string, 'error' => ?string]
     */
    public function chat(array $ctx, array $history): array
    {
        $key   = config('services.openrouter.key');
        $model = config('services.openrouter.model');
        $base  = rtrim(config('services.openrouter.base_url'), '/');

        if (empty($key)) {
            return ['ok' => false, 'reply' => '', 'error' => 'The assistant is not configured yet (missing API key).'];
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($ctx)]],
            $history
        );

        $tools = WisdomTools::definitions($ctx);

        // Reply budget. Kept modest so it fits a low/free OpenRouter balance;
        // auto-shrinks further on a 402 (see below).
        $maxTokens = 700;

        $send = function (array $payload) use ($key, $base) {
            return Http::withToken($key)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url', 'https://app.thewisdom.ai'),
                    'X-Title'      => 'HRVMS Wisdom AI',
                ])
                ->timeout(60)
                ->post($base . '/chat/completions', $payload);
        };

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $payload = [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.3,
                'max_tokens'  => $maxTokens,
            ];
            if (!empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            try {
                $resp = $send($payload);

                // Free-tier credit guard: OpenRouter returns 402 when the
                // requested reply budget exceeds the remaining balance
                // ("... can only afford N tokens"). Shrink to fit and retry
                // once so the assistant still answers (just more concisely).
                if ($resp->status() === 402) {
                    $afford = $this->affordableTokens($resp->body());
                    if ($afford !== null && $afford >= 60) {
                        $maxTokens = min($maxTokens, $afford);
                        $payload['max_tokens'] = $maxTokens;
                        $resp = $send($payload);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Wisdom AI request failed', ['error' => $e->getMessage()]);
                return ['ok' => false, 'reply' => '', 'error' => 'Could not reach the AI service. Please try again.'];
            }

            if (!$resp->successful()) {
                Log::error('Wisdom AI non-200', ['status' => $resp->status(), 'body' => $resp->body()]);
                $err = $resp->status() === 402
                    ? 'The Wisdom AI assistant is out of OpenRouter credits. Please top up at openrouter.ai/settings/credits, or set OPENROUTER_MODEL to a free model.'
                    : 'The AI service returned an error. Please try again.';
                return ['ok' => false, 'reply' => '', 'error' => $err];
            }

            $message = $resp->json('choices.0.message');
            if (!$message) {
                return ['ok' => false, 'reply' => '', 'error' => 'The AI service returned an empty response.'];
            }

            $toolCalls = $message['tool_calls'] ?? [];

            // No native tool calls → either a final answer, or a tool call the
            // provider mistakenly emitted as plain-text JSON in the content.
            if (empty($toolCalls)) {
                $content = trim($message['content'] ?? '');

                // Salvage: detect an inline { "name": "...", "parameters": {...} }
                // tool call, run it for real, and let the model answer with data.
                $inline = $this->parseInlineToolCall($content, $tools);
                if ($inline && $round < self::MAX_TOOL_ROUNDS - 1) {
                    $messages[] = ['role' => 'assistant', 'content' => $content];
                    $result = WisdomTools::execute($inline['name'], $inline['args'], $ctx);
                    $messages[] = [
                        'role'    => 'user',
                        'content' => 'Result of ' . $inline['name'] . ': ' . json_encode($result)
                            . ". Now answer my previous question in plain natural language using this data. Do NOT output any JSON or function-call syntax.",
                    ];
                    continue;
                }

                $content = $this->stripInlineToolCall($content);
                return ['ok' => true, 'reply' => $content !== '' ? $content : 'I am not sure how to answer that.', 'error' => null];
            }

            // Echo the assistant tool-call message back, then append each result.
            $messages[] = $message;
            foreach ($toolCalls as $call) {
                $fnName = $call['function']['name'] ?? '';
                $args   = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $result = WisdomTools::execute($fnName, $args, $ctx);

                $messages[] = [
                    'role'         => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'name'         => $fnName,
                    'content'      => json_encode($result),
                ];
            }
        }

        // Tool budget exhausted — ask the model once more without tools for a summary.
        return ['ok' => true, 'reply' => 'I gathered the data but could not finish composing the answer. Please try rephrasing your question.', 'error' => null];
    }

    /**
     * Parse the affordable token count from an OpenRouter 402 body
     * ("... but can only afford 436 ..."). Returns null if not present.
     */
    private function affordableTokens(string $body): ?int
    {
        if (preg_match('/can only afford (\d+)/i', $body, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Detect a tool call the model wrote as plain-text JSON in its reply
     * (some providers do this instead of using native tool_calls). Returns
     * ['name' => ..., 'args' => [...]] when it matches a known tool.
     */
    private function parseInlineToolCall(string $content, array $tools): ?array
    {
        if ($content === '' || empty($tools)) {
            return null;
        }
        $names = array_map(fn ($t) => $t['function']['name'], $tools);

        if (!preg_match('/\{\s*"name"\s*:\s*"([a-zA-Z_]+)"\s*(?:,\s*"(?:parameters|arguments)"\s*:\s*(\{.*?\}))?\s*\}/s', $content, $m)) {
            return null;
        }
        if (!in_array($m[1], $names, true)) {
            return null;
        }

        $args = [];
        if (!empty($m[2])) {
            $decoded = json_decode($m[2], true);
            if (is_array($decoded)) {
                $args = $decoded;
            }
        }
        return ['name' => $m[1], 'args' => $args];
    }

    /**
     * Remove any leftover inline tool-call JSON / hallucinated filler so the
     * user never sees raw function-call syntax.
     */
    private function stripInlineToolCall(string $content): string
    {
        $content = preg_replace('/\{\s*"name"\s*:\s*"[a-zA-Z_]+"\s*(?:,\s*"(?:parameters|arguments)"\s*:\s*\{.*?\})?\s*\}/s', '', $content);
        $content = preg_replace('/(Let me try again[^.]*\.|The function call did not return the expected result\.?)/i', '', $content);
        return trim($content);
    }

    /**
     * Tier-aware system prompt encoding the access blueprint.
     */
    private function systemPrompt(array $ctx): string
    {
        $base = <<<TXT
You are "Wisdom AI", an intelligent HR assistant embedded in the HRVMS (HR & Visa Management System) for the resort "{$ctx['resort_name']}".

You are speaking with {$ctx['user_name']} (role: {$ctx['role_label']}). Today's date is {$ctx['today']}.

All data you access is automatically restricted to this user's resort ("{$ctx['resort_name']}"); never claim to have data about other resorts.

General behaviour:
- Be warm, concise and professional. Format answers in clean Markdown (use short paragraphs, **bold** for key figures, and bullet/numbered lists or tables where helpful).
- IMPORTANT — YOU ARE A READ-ONLY, INFORMATIONAL ASSISTANT. You can look up, summarise and explain data, policy and law, but you CANNOT make any change to the system. You cannot set or update salaries, approve or reject leave, hire/edit/delete employees, run or edit payroll, change records, send anything, or perform ANY action. If the user asks you to DO or CHANGE something (e.g. "set the salary", "approve this leave", "add this employee"), do NOT pretend you did it and do NOT just report the current value — politely explain that you are an informational AI assistant and cannot perform actions, then guide them to do it themselves: name the relevant module/page in HRVMS and the general steps (e.g. to change a salary: open the **Employee module → the employee's profile → Salary / Compensation**, edit and save; for a raise use the **Salary Increment** workflow). If you are unsure of the exact page, point them to the top **Menu** or **Search** bar to find the module, and suggest contacting HR. Be helpful and specific about the process even though you cannot execute it.
- When a question needs live data, CALL THE PROVIDED TOOLS rather than guessing. Never invent numbers, names, or records.
- CRITICAL: To call a tool, use the system's native function-calling ONLY. NEVER write tool/function-call JSON (for example {"name": "list_employees", "parameters": {}}) into your visible reply text. The user must never see JSON or function-call syntax. If no tool fits, or a tool returns nothing, just say so in plain language.
- Tool hints: use `list_employees` to list staff or answer "who works here / employee names"; use `get_nationality_breakdown` for local (Maldivian) vs foreign counts; use `get_headcount` only for a single total number; use `find_employee` for one person's profile/position/department; use `get_employee_attendance` for whether a named person was present on a date; use `get_upcoming_birthdays` for birthdays this/next month; use `get_active_sos` for current emergencies/SOS. Compute concrete dates yourself before calling (e.g. "yesterday" → the actual YYYY-MM-DD relative to today's date above).
- Workforce Planning intents (the question can be phrased many ways — map it to ONE tool, all accept an optional `year` defaulting to the current year): approved/budgeted manpower, "how many positions are approved", manpower by division/department → `get_budgeted_headcount` (group_by total/division/department/position); vacancies, "how many do we need to hire", vacancy by department/position, "understaffed / most critical / urgent hiring" → `get_vacancy_analysis` (group_by department/position, critical_only=true for the understaffed list); "how many males/females", gender ratio, female workforce → `get_gender_breakdown`; "which departments submitted their manning plan", pending/approved/rejected manning requests → `get_manning_status`; current room occupancy → `get_occupancy`; "are we meeting local employment ratio / localization %" → `get_workforce_compliance` (type=localization); "employees under minimum wage" → `get_workforce_compliance` (type=minimum_wage, HR only). MONEY (HR only): any budget question — "total HR budget", "HR cost", "spending on HR", "budget for Rooms Division", "budget by position", "budget for local/expat/female staff", "monthly budget" — → `get_workforce_budget` with the matching group_by (total/division/department/section/position/nationality/gender); for one person's "annual budget / monthly cost" → `get_employee_cost`. To answer a single group (e.g. one department or division), read its value from the breakdown the tool returns.
- Money tools (budget, employee cost, salary, payroll) return every amount in BOTH currencies as `usd` and `mvr`, plus a `conversion_rate`. Answer in the currency the user asked for; if they ask for "both", "in dollars and rufiyaa", or a conversion, show both (e.g. **USD 51,065.69** (MVR 787,432.94)). When the currency is unspecified, default to showing both. Use only the figures from the tool — never convert amounts yourself.
- If a tool returns an "error" or empty result, explain plainly what you could and could not find — do not retry by printing the call as text.
- For employment-law and policy questions, base your answers on the Maldives Employment Act (Law No. 2/2008) and standard HR best practice, and remind the user to confirm against official company policy documents for binding decisions.
TXT;

        switch ($ctx['tier']) {
            case WisdomAccess::TIER_FULL:
                $tierBlock = <<<TXT

ACCESS LEVEL: FULL (HR Director / HR Manager).
- You may answer any operational, compliance, or HR question, including payroll, salary and compensation.
- Use the data tools freely to pull headcount, departments, leave, attendance, recruitment, employee profiles, payroll summaries and individual salaries.
- For a question NONE of the dedicated tools cover (e.g. accommodation detail, overtime, payroll history, custom aggregates), use the custom-query tools. WORKFLOW: (1) if you are unsure of the table or column names, call `list_tables` with a keyword to find the table, then `describe_table` to see its columns — do NOT guess names; (2) write a single read-only SELECT with `run_read_query`, always scoped with `resort_id = :resort_id` (bound automatically — never write a literal id). Prefer a dedicated tool when one fits. If a query errors, read the message, call `describe_table` to confirm the real columns, fix the SQL once, then if it still fails explain plainly what you could not retrieve. Never claim a number you did not get from a tool.
TXT;
                break;

            case WisdomAccess::TIER_GM:
                $tierBlock = <<<TXT

ACCESS LEVEL: MODERATE — General Manager (PAYROLL RESTRICTED).
- You may answer operational questions: headcount, departments, who is on leave, attendance, recruitment pipeline, and general employee profile lookups.
- PAYROLL IS COMPLETELY RESTRICTED. You must NOT reveal or estimate salaries, compensation, allowances, payroll totals, or any monetary pay figures. If asked anything about salary/compensation/payroll, politely refuse: explain that payroll data is restricted for the General Manager role and suggest contacting HR directly. Do not attempt to work around this.
TXT;
                break;

            case WisdomAccess::TIER_POLICY:
            default:
                $tierBlock = <<<TXT

ACCESS LEVEL: LIMITED (Head of Department / EXCOM / Manager).
- You have NO access to operational databases or employee records. You have no data tools.
- You may ONLY answer questions about company policy and employment law (Maldives Employment Act and HR best practice).
- If the user asks for any operational data, employee records, headcount, leave lists, payroll, recruitment numbers, or anything requiring database access, politely decline and explain that your role only permits company-policy and employment-law guidance, and that they should contact HR for operational data.
TXT;
                break;
        }

        return $base . "\n" . $tierBlock;
    }
}
