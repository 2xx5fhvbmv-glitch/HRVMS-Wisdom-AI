<?php

namespace App\Services\Visa;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * In-app visa-document extraction.
 *
 * Sends the uploaded PDF straight to an OpenRouter vision/PDF model (OCR +
 * field extraction in a single call), replacing the external PaddleOCR/FastAPI
 * service. Returns the exact shape the xpact-sync finalize step consumes:
 *
 *   ['status' => 'done',  'extracted_fields' => [...]]   // success
 *   ['status' => 'error', 'message' => '...']            // failure
 *
 * For the 'xpat_sync' doc, extracted_fields is an associative object; for the
 * 'payment_schedule' doc it is an ordered list of payment rows.
 */
class OpenRouterDocExtractor
{
    public function extract(UploadedFile $file, string $docType): array
    {
        $key   = config('services.openrouter.key');
        $model = config('services.openrouter.vision_model', 'google/gemini-2.5-flash');
        $base  = rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');

        if (empty($key)) {
            return ['status' => 'error', 'message' => 'OpenRouter API key is not configured.'];
        }

        $raw = @file_get_contents($file->getRealPath());
        if ($raw === false || $raw === '') {
            return ['status' => 'error', 'message' => 'Could not read the uploaded file.'];
        }
        $dataUrl = 'data:application/pdf;base64,' . base64_encode($raw);

        $payload = [
            'model'       => $model,
            'temperature' => 0,
            'max_tokens'  => 1500,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a precise document data-extraction engine. Read the attached document and output ONLY valid JSON — no prose, no markdown code fences. For any field you cannot read, use the string "Unavailable". Copy dates exactly as printed on the document.',
                ],
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->promptFor($docType)],
                        ['type' => 'file', 'file' => [
                            'filename'  => $file->getClientOriginalName() ?: 'document.pdf',
                            'file_data' => $dataUrl,
                        ]],
                    ],
                ],
            ],
        ];

        try {
            $resp = Http::withToken($key)
                ->withHeaders([
                    'HTTP-Referer' => (string) config('app.url', 'https://app.thewisdom.ai'),
                    'X-Title'      => 'HRVMS Visa Extraction',
                ])
                ->timeout(90)
                ->post($base . '/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::error('Visa doc extract request failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Could not reach the extraction service. Please try again.'];
        }

        if ($resp->status() === 402) {
            return ['status' => 'error', 'message' => 'OpenRouter credits are exhausted. Top up at openrouter.ai/settings/credits.'];
        }
        if (!$resp->successful()) {
            $detail = trim((string) $resp->json('error.message', ''));
            Log::error('Visa doc extract non-200', ['status' => $resp->status(), 'model' => $model, 'body' => $resp->body()]);
            $msg = "The extraction model '{$model}' returned HTTP {$resp->status()}";
            $msg .= $detail !== '' ? ": {$detail}" : '.';
            if ($resp->status() === 404) {
                $msg .= ' (Set OPENROUTER_VISION_MODEL in .env to a valid model id from openrouter.ai/models.)';
            }
            return ['status' => 'error', 'message' => $msg];
        }

        $content = (string) $resp->json('choices.0.message.content', '');
        $parsed  = $this->parseJson($content);
        if ($parsed === null) {
            Log::warning('Visa doc extract unparseable reply', ['content' => mb_substr($content, 0, 500)]);
            return ['status' => 'error', 'message' => 'Could not parse the extracted data from the document.'];
        }

        return ['status' => 'done', 'extracted_fields' => $parsed];
    }

    /** Doc-type-specific extraction instruction. */
    private function promptFor(string $docType): string
    {
        if ($docType === 'payment_schedule') {
            return 'This is a Quota Slot Fee payment schedule. Extract EVERY payment row, in the exact order they appear top to bottom. '
                . 'Return a JSON ARRAY where each element is an object with exactly these keys: '
                . '"Month" (the month/period label as printed), '
                . '"State" (the payment status as printed, e.g. "FULLY PAID" or "PENDING"), '
                . '"DatePaymentDueOn" (the due date exactly as printed). '
                . 'Output ONLY the JSON array.';
        }

        // Default: the xpat_sync summary document (visa / work permit / insurance / quota).
        return 'This document contains an expatriate employee\'s visa and work-permit details. '
            . 'Return a single JSON OBJECT with EXACTLY these keys '
            . '(use "Unavailable" for any value you cannot read):'
            . "\n{"
            . '"Employee Name": "", '
            . '"Employee\'s Passport Number": "", '
            . '"Work Permit Number": "", '
            . '"Work Permit Expiry Date (Expiry On)": "", '
            . '"Visa Issued Date": "", '
            . '"Visa Expiry Date": "", '
            . '"Quota Slot Number": "", '
            . '"Insurance Expiry Date": ""'
            . '}. '
            // The Work Permit Expiry is the field that is most often misread, so
            // anchor it explicitly to the single field labelled "Expiry On".
            . 'IMPORTANT for "Work Permit Expiry Date (Expiry On)": use ONLY the date printed against '
            . 'the work permit\'s "Expiry On" (or "Expiry" / "Expiry Date") label — the WORK PERMIT\'s '
            . 'own expiry. Do NOT use "Contract Exp"/"Contract Expiry On", "Insurance Exp", '
            . '"Visa Expiry On", "Visa Issued On", "Issued On" or "Arrived On"; those are separate '
            . 'fields that are commonly confused with it. Read the value next to the literal "Expiry '
            . 'On" label only — do NOT just pick the latest date on the page. '
            . 'IMPORTANT for "Visa Issued Date" and "Visa Expiry Date": many Expat System work-permit '
            . 'cards have no separate visa section at all — just one "Issued On" and one "Expiry On" '
            . 'for the whole permit. When there is no distinct visa section, use that SAME "Issued On" '
            . 'date for "Visa Issued Date" and that SAME "Expiry On" date for "Visa Expiry Date" — do '
            . 'not leave them "Unavailable" just because there is no separate visa label. '
            . 'Return every date exactly as printed on the document. Output ONLY the JSON object.';
    }

    /** Extract a JSON value from the model reply (tolerates code fences / stray prose). */
    private function parseJson(string $content)
    {
        $content = trim($content);
        // Strip ```json ... ``` fences if present.
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $decoded = json_decode(trim($content), true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Fallback: grab the first {...} object or [...] array block.
        if (preg_match('/(\{.*\}|\[.*\])/s', $content, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }
}
