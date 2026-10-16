<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Resort;
use App\Models\Settings;
use App\Services\Wisdom\WisdomAccess;
use App\Services\Wisdom\OpenRouterClient;

/**
 * Wisdom AI — in-app HR chatbot endpoints.
 *
 * Conversations are kept in the session only (ephemeral; cleared on logout or
 * via the "clear" action). All three actions are gated by the user's tier;
 * users with no access get a 403.
 */
class WisdomChatController extends Controller
{
    /** Session key holding the running conversation. */
    const SESSION_KEY = 'wisdom_ai_history';

    /** Keep only the most recent N messages to bound prompt size. */
    const MAX_HISTORY = 20;

    /**
     * Usage limits (config services.wisdom_ai): the per-minute burst cap is the
     * 'wisdom-chat' limiter (RouteServiceProvider); the daily allowance, the
     * in-flight cap and the resort's monthly budget are checked here. Every
     * message that reaches the provider is logged to wisdom_usage — the daily
     * count is its successful rows, the budget is its summed cost.
     */
    public function chat(Request $request, OpenRouterClient $client)
    {
        $ctx = WisdomAccess::context();
        if (!$ctx) {
            return response()->json(['success' => false, 'message' => 'You do not have access to Wisdom AI.'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);
        $message = trim($validated['message']);
        if ($message === '') {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty.'], 422);
        }

        // ponytail: count-then-call, so two in-flight requests at limit-1 can overshoot by one.
        $usage = self::usage();
        if ($usage['remaining'] <= 0) {
            return self::limited('daily_limit', "You've reached today's limit of {$usage['limit']} messages. It resets at midnight.", $usage, (int) now()->diffInSeconds(now()->addDay()->startOfDay(), true));
        }

        $budget = Resort::whereKey($ctx['resort_id'])->value('wisdom_monthly_budget') ?? config('services.wisdom_ai.resort_monthly_budget');
        if ($budget > 0 && $this->monthSpend($ctx['resort_id']) >= $budget) {
            return self::limited('resort_budget', "Your resort's monthly WAI allowance has been reached. Please contact your administrator.", $usage, (int) now()->diffInSeconds(now()->addMonthNoOverflow()->startOfMonth(), true));
        }

        $userId = Auth::guard('resort-admin')->id();
        $lock = null;
        for ($slot = 0; $slot < config('services.wisdom_ai.max_in_flight', 2) && !$lock; $slot++) {
            $candidate = Cache::lock("wisdom-inflight:{$userId}:{$slot}", 300);
            $lock = $candidate->get() ? $candidate : null;
        }
        if (!$lock) {
            return self::limited('rate_limited', 'Please wait for your current question to finish before sending another.', $usage, 5);
        }

        $history = session(self::SESSION_KEY, []);
        $history[] = ['role' => 'user', 'content' => $message];
        $history = $this->trim($history);

        // Persist the user turn up-front so a failed AI call can still be retried
        // against a coherent conversation.
        session([self::SESSION_KEY => $history]);

        $started = microtime(true);
        try {
            $result = $client->chat($ctx, $history);
        } finally {
            $lock->release();
        }

        if ($client->usage) { // empty = the provider was never called (e.g. missing API key)
            try {
                DB::table('wisdom_usage')->insert([
                    'resort_id'         => $ctx['resort_id'],
                    'user_id'           => $userId,
                    'tier'              => $ctx['tier'],
                    'ok'                => $result['ok'],
                    'prompt_tokens'     => $client->usage['prompt_tokens'],
                    'completion_tokens' => $client->usage['completion_tokens'],
                    'cost'              => $client->usage['cost'],
                    'tool_calls'        => $client->usage['tool_calls'],
                    'duration_ms'       => (int) ((microtime(true) - $started) * 1000),
                    'created_at'        => now(),
                ]);
                if ($budget > 0) {
                    $this->alertBudget($ctx, (float) $budget);
                }
            } catch (\Throwable $e) {
                Log::error('Wisdom AI usage log failed', ['error' => $e->getMessage()]);
            }
        }

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Something went wrong.',
                'usage'   => self::usage(),
            ], 502);
        }

        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        session([self::SESSION_KEY => $this->trim($history)]);

        return response()->json([
            'success'    => true,
            'reply'      => $result['reply'],
            'tier'       => $ctx['tier'],
            'tier_label' => $ctx['tier_label'],
            'usage'      => self::usage(),
        ]);
    }

    public function history(Request $request)
    {
        $ctx = WisdomAccess::context();
        if (!$ctx) {
            return response()->json(['success' => false, 'message' => 'No access.'], 403);
        }

        return response()->json([
            'success'    => true,
            'messages'   => array_values(session(self::SESSION_KEY, [])),
            'tier'       => $ctx['tier'],
            'tier_label' => $ctx['tier_label'],
            'user_name'  => $ctx['user_name'],
            'can_db'     => $ctx['can_db'],
            'can_payroll'=> $ctx['can_payroll'],
            'usage'      => self::usage(),
        ]);
    }

    public function clear(Request $request)
    {
        if (!WisdomAccess::hasAccess()) {
            return response()->json(['success' => false, 'message' => 'No access.'], 403);
        }

        session()->forget(self::SESSION_KEY);
        return response()->json(['success' => true]);
    }

    /** Today's allowance for the logged-in user, shared by both bots. The app clock is Maldives time, so "today" ends at Maldives midnight. */
    public static function usage(): array
    {
        $user  = Auth::guard('resort-admin')->user();
        $limit = (int) (Resort::whereKey($user->resort_id)->value('wisdom_daily_limit')
            ?: (config('services.wisdom_ai.daily_limits')[WisdomAccess::tier()] ?? 50));
        $used  = DB::table('wisdom_usage')->where('resort_id', $user->resort_id)->where('user_id', $user->id)
            ->where('ok', true)->where('created_at', '>=', today())->count();

        return [
            'limit'     => $limit,
            'used'      => min($used, $limit),
            'remaining' => max(0, $limit - $used),
            'resets_at' => today()->addDay()->toIso8601String(),
        ];
    }

    /** The 429 body the chat screens read: `message` is shown as the assistant's reply. */
    public static function limited(string $code, string $message, array $usage, int $retryAfter)
    {
        $retryAfter = max(1, $retryAfter);
        return response()->json([
            'success'     => false,
            'code'        => $code,
            'message'     => $message,
            'retry_after' => $retryAfter,
            'usage'       => $usage,
        ], 429, ['Retry-After' => $retryAfter]);
    }

    private function monthSpend($resortId): float
    {
        return (float) DB::table('wisdom_usage')->where('resort_id', $resortId)
            ->where('created_at', '>=', now()->startOfMonth())->sum('cost');
    }

    /** Mail the platform owner once per resort per month when spend crosses 80% of the budget. */
    private function alertBudget(array $ctx, float $budget): void
    {
        $spent = $this->monthSpend($ctx['resort_id']);
        if ($spent < 0.8 * $budget || !Cache::add("wisdom-budget-alert:{$ctx['resort_id']}:" . now()->format('Y-m'), 1, now()->endOfMonth())) {
            return;
        }
        $to = config('services.wisdom_ai.budget_alert_email') ?: optional(Settings::first())->admin_email;
        if (!$to) {
            Log::warning('Wisdom AI budget alert has no recipient', ['resort_id' => $ctx['resort_id']]);
            return;
        }
        Mail::raw(sprintf(
            "Wisdom AI spend for %s (resort id %d) is USD %.2f this month, %d%% of its USD %.2f monthly budget. AI calls stop at 100%%; raise the budget on the resort's edit page in the admin console.",
            $ctx['resort_name'], $ctx['resort_id'], $spent, (int) floor($spent / $budget * 100), $budget
        ), fn ($m) => $m->to($to)->subject("Wisdom AI budget 80% reached — {$ctx['resort_name']}"));
    }

    private function trim(array $history): array
    {
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }
        return $history;
    }
}
