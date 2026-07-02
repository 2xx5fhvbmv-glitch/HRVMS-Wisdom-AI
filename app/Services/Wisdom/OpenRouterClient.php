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

        // Reply budget. Kept small so the request fits a low/free OpenRouter
        // balance on the first try (the free-tier per-request budget observed
        // is ~150-250 tokens); auto-shrinks further on a 402 (see below).
        // With credits added, this can safely be raised (e.g. 800-1200).
        $maxTokens = (int) config('services.openrouter.max_tokens', 140);

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
                        // The affordable amount fluctuates downward between calls,
                        // so leave a buffer below it.
                        $maxTokens = max(60, min($maxTokens, $afford - 40));
                        $payload['max_tokens'] = $maxTokens;
                        $resp = $send($payload);
                    }
                }

                // Free models are rate-limited (429) and providers occasionally
                // return 5xx. Retry a couple of times with short backoff before
                // surfacing an error — interactive use sends one message at a
                // time, so a brief retry almost always succeeds.
                $retries = 0;
                while (($resp->status() === 429 || $resp->status() >= 500) && $retries < 2) {
                    usleep(1300000); // 1.3s
                    $resp = $send($payload);
                    $retries++;
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

        // Find a KNOWN tool name appearing as "name":"<tool>" ANYWHERE in the text,
        // regardless of key order or nesting. This catches every shape models leak:
        //   {"name":"x","parameters":{...}}
        //   {"type":"function","name":"x", ...}
        //   {"type":"function","function":{"name":"x","arguments":{...}}}
        //   ```json { ... } ```  (fenced)
        if (!preg_match_all('/"name"\s*:\s*"([a-zA-Z_]+)"/', $content, $nameMatches)) {
            return null;
        }
        $name = null;
        foreach ($nameMatches[1] as $candidate) {
            if (in_array($candidate, $names, true)) {
                $name = $candidate;
                break;
            }
        }
        if ($name === null) {
            return null;
        }

        // Pull arguments from a "parameters"/"arguments" object (tolerate one level
        // of nesting). Absent → empty args.
        $args = [];
        if (preg_match('/"(?:parameters|arguments)"\s*:\s*(\{(?:[^{}]|\{[^{}]*\})*\})/s', $content, $am)) {
            $decoded = json_decode($am[1], true);
            if (is_array($decoded)) {
                $args = $decoded;
            }
        }
        return ['name' => $name, 'args' => $args];
    }

    /**
     * Remove any leftover inline tool-call JSON / hallucinated filler so the
     * user never sees raw function-call syntax.
     */
    private function stripInlineToolCall(string $content): string
    {
        // 1) Drop fenced code blocks (```...```) that wrap a leaked tool call.
        $content = preg_replace_callback('/```[a-zA-Z]*\s*(.*?)```/s', function ($m) {
            return (strpos($m[1], '"function"') !== false
                || preg_match('/"name"\s*:\s*"[a-zA-Z_]+"/', $m[1]))
                ? '' : $m[0];
        }, $content);

        // 2) Brace-balanced removal of any remaining JSON object that looks like a
        //    tool call (handles arbitrary nesting, which regex cannot).
        $content = $this->removeToolCallObjects($content);

        // 3) Hallucinated filler that often surrounds leaked calls.
        $content = preg_replace('/(Let me try again[^.]*\.|The function call did not return the expected result\.?|Then look at[^.]*returned by the function\.?)/i', '', $content);

        return trim($content);
    }

    /**
     * Remove JSON objects that look like tool calls, matching braces by depth so
     * nested objects are handled correctly.
     */
    private function removeToolCallObjects(string $text): string
    {
        $out = '';
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] !== '{') {
                $out .= $text[$i];
                continue;
            }
            // Find the matching closing brace.
            $depth = 0;
            $j = $i;
            for (; $j < $len; $j++) {
                if ($text[$j] === '{') {
                    $depth++;
                } elseif ($text[$j] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }
            if ($depth !== 0) {
                // Unbalanced — leave the rest as-is.
                $out .= substr($text, $i);
                break;
            }
            $obj = substr($text, $i, $j - $i + 1);
            $looksLikeToolCall = strpos($obj, '"function"') !== false
                || (preg_match('/"name"\s*:\s*"[a-zA-Z_]+"/', $obj)
                    && preg_match('/"(?:parameters|arguments)"\s*:/', $obj));
            if (!$looksLikeToolCall) {
                $out .= $obj;
            }
            $i = $j; // skip the object
        }
        return $out;
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
- SCOPE — You ONLY assist with this HRVMS system and its subject matter: HR, employees, payroll/compensation, leave & attendance, recruitment & workforce planning, visa/immigration, employee relations, company policy and Maldives employment law, and how to use the HRVMS modules themselves. You are NOT a general-purpose assistant. If asked anything outside this domain — e.g. writing or explaining code (Python, SQL, etc.), general programming or math, current events, trivia, recipes, essays, translations, or other off-topic chit-chat — do NOT answer it. Politely decline in one short sentence and steer the user back to what you can help with (HR, visa and workforce questions about {$ctx['resort_name']}). The only "technical" help you give is guiding users through HRVMS features. A brief greeting or "what can you do" reply is fine; anything substantive must be on-topic.
- Be warm, concise and professional. Format answers in clean Markdown.
- FORMATTING (always): when you list people or records, use a **numbered or bulleted list** and start each item with the person's/record's **name in bold**, then the key details — NEVER output raw comma-joined field rows or JSON-looking text (e.g. 'Employee: Rani Khan, Department: ...'). For summaries or dashboards use short **bold** metric labels with bullets/sub-headings (not a wall of text), and OMIT zero/empty values unless the user explicitly asked for them.
- IMPORTANT — YOU ARE A READ-ONLY, INFORMATIONAL ASSISTANT. You can look up, summarise and explain data, policy and law, but you CANNOT make any change to the system. You cannot set or update salaries, approve or reject leave, hire/edit/delete employees, run or edit payroll, change records, send anything, or perform ANY action. If the user asks you to DO or CHANGE something (e.g. "set the salary", "approve this leave", "add this employee"), do NOT pretend you did it and do NOT just report the current value — politely explain that you are an informational AI assistant and cannot perform actions, then guide them to do it themselves: name the relevant module/page in HRVMS and the general steps (e.g. to change a salary: open the **Employee module → the employee's profile → Salary / Compensation**, edit and save; for a raise use the **Salary Increment** workflow). If you are unsure of the exact page, point them to the top **Menu** or **Search** bar to find the module, and suggest contacting HR. Be helpful and specific about the process even though you cannot execute it.
- When a question needs live data, CALL THE PROVIDED TOOLS rather than guessing. Never invent numbers, names, or records.
- CRITICAL: To call a tool, use the system's native function-calling ONLY. NEVER write tool/function-call JSON (for example {"name": "list_employees", "parameters": {}}) into your visible reply text. The user must never see JSON or function-call syntax.
- NEVER refuse a legitimate in-scope HR/operational question, and NEVER say a request "exceeds function limitations" or "requires additional functionality". If no dedicated tool fits, follow your access-level instructions to fall back (HR-tier users can query ANY resort data). Only say you could not find something AFTER you have actually tried the tools — and then say plainly what was missing.
- Tool hints: use `list_employees` to list staff or answer "who works here / employee names"; use `get_nationality_breakdown` for local (Maldivian) vs foreign counts; use `get_headcount` only for a single total number; use `find_employee` for one person's profile/position/department; use `get_employee_attendance` for whether a named person was present on a date; use `get_upcoming_birthdays` for birthdays this/next month; use `get_active_sos` for current emergencies/SOS. Compute concrete dates yourself before calling (e.g. "yesterday" → the actual YYYY-MM-DD relative to today's date above).
- Workforce Planning intents (the question can be phrased many ways — map it to ONE tool, all accept an optional `year` defaulting to the current year): approved/budgeted manpower, "how many positions are approved", manpower by division/department → `get_budgeted_headcount` (group_by total/division/department/position); vacancies, "how many do we need to hire", vacancy by department/position, "understaffed for hiring / most critical / urgent hiring" → `get_vacancy_analysis` (group_by department/position, critical_only=true) — but "short-handed / understaffed TODAY" is an attendance question, not this; "how many males/females", gender ratio, female workforce → `get_gender_breakdown`; "which departments submitted their manning plan", pending/approved/rejected manning requests → `get_manning_status`; current room occupancy → `get_occupancy`; "are we meeting local employment ratio / localization %" → `get_workforce_compliance` (type=localization); "employees under minimum wage" → `get_workforce_compliance` (type=minimum_wage, HR only). MONEY (HR only): any budget question — "total HR budget", "HR cost", "spending on HR", "budget for Rooms Division", "budget by position", "budget for local/expat/female staff", "monthly budget" — → `get_workforce_budget` with the matching group_by (total/division/department/section/position/nationality/gender); for one person's "annual budget / monthly cost" → `get_employee_cost`. To answer a single group (e.g. one department or division), read its value from the breakdown the tool returns.
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
- You have FULL data access — there is NO operational HR question you should refuse for lack of a tool. Map these common requests to the DEDICATED tool (fall back to `run_read_query` only if none fits):
  • "Today's roster / duty roster", "who is off / has a day-off tomorrow" → `get_duty_roster` (off=true for day-off). NOT recruitment.
  • "Who was present/absent on <date>", "today's attendance" → `get_attendance_register`. "Who arrived late" → `get_late_arrivals`. "Most overtime" → `get_overtime`. "Understaffed / short-handed today" → `get_understaffed_today` (NOT recruiting vacancies).
  • Accommodation: "available/free beds", "available female/male accommodation" → `get_available_beds` (gender optional); "where does X stay / X's roommates" → `get_employee_accommodation`; "which building has highest occupancy" → `get_building_occupancy`; "maintenance requests" → `get_maintenance_requests`.
  • People profile — passport number, probation status, assigned manager, date-of-birth/birthday, joining date → `find_employee` (it returns all of these). Report the probation_status field; don't guess.
  • Leave: "which leave requests are pending approval" → `get_pending_leave_approvals` (NOT who is on leave, NOT promotions/resignations); "X's leave balance" → `get_leave_balance`.
  • "Repeat offenders" → `get_repeat_offenders`; "confidential cases/grievances" → `get_confidential_cases`.
  • Recruitment: "which vacancy has the most applicants / applicants per vacancy / which vacancies are open" → `get_vacancy_applicants` (open_only=true for open list); "interviews scheduled tomorrow / upcoming interviews" → `get_scheduled_interviews`.
  • Performance: "which KPIs are approved/pending/rejected by name, target budgets" → `get_kpi_details`. L&D catalog → `get_training_programs`; one person's training → `get_employee_training`.
  • "Upcoming arrivals" → employees returning from leave + new joiners (query the leave return dates / onboarding tables) — NOT birthdays.
  • "What needs my attention / executive briefing / approvals waiting" → AGGREGATE across modules (pending approvals, upcoming interviews, expiring documents, high-severity incidents), present a prioritised list, omit zero counts.
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
