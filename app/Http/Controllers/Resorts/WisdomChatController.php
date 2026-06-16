<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        $history = session(self::SESSION_KEY, []);
        $history[] = ['role' => 'user', 'content' => $message];
        $history = $this->trim($history);

        // Persist the user turn up-front so a failed AI call can still be retried
        // against a coherent conversation.
        session([self::SESSION_KEY => $history]);

        $result = $client->chat($ctx, $history);

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Something went wrong.',
            ], 502);
        }

        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        session([self::SESSION_KEY => $this->trim($history)]);

        return response()->json([
            'success'    => true,
            'reply'      => $result['reply'],
            'tier'       => $ctx['tier'],
            'tier_label' => $ctx['tier_label'],
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

    private function trim(array $history): array
    {
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, -self::MAX_HISTORY);
        }
        return $history;
    }
}
