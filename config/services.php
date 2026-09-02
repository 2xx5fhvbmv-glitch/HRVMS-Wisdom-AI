<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'websocket' => [
        'url' => env('WEBSOCKET_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (mobile push notifications)
    |--------------------------------------------------------------------------
    | Common::FCMTokenPushNotification()/sendPushNotificationForMobile() used
    | to call env('FCM_...') directly instead of going through here — env()
    | outside a config/*.php file returns null after `php artisan
    | config:cache` (a completely standard deploy step, since Laravel stops
    | reading .env at all once the config cache exists), silently breaking
    | every push notification with no visible error beyond an FCM 401 in the
    | log. Routing through config() here fixes that regardless of caching.
    */
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_email' => env('FCM_SERVICE_ACCOUNT_EMAIL'),
        'private_key' => env('FCM_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter (Wisdom AI chatbot)
    |--------------------------------------------------------------------------
    | Powers the in-app "Wisdom AI" HR assistant. The model is an
    | OpenAI-compatible chat-completions endpoint that supports tool calling.
    */
    'openrouter' => [
        'key'      => env('OPENROUTER_API_KEY'),
        'model'    => env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 800),
        // Vision/PDF-capable model for in-app visa document extraction
        // (replaces the external PaddleOCR service). Must accept PDF/image input.
        'vision_model' => env('OPENROUTER_VISION_MODEL', 'google/gemini-2.5-flash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI work-details extraction (Visa document upload)
    |--------------------------------------------------------------------------
    | Same env()-outside-config bug as 'fcm' above — RenewalController,
    | XpactEmployeeController and FetchDataAiController all called
    | env('AI_extract_work_details_URL') directly, which returns null once
    | config:cache runs in prod. The concatenated URL then collapsed to just
    | the doc-type suffix (e.g. "passport"), and curl tried to resolve that
    | bare string as a hostname — "Could not resolve host: passport" in prod,
    | while working fine locally where config is never cached.
    */
    'ai_extract' => [
        'url' => env('AI_extract_work_details_URL'),
        'base_url' => env('AI_URL', 'http://localhost:8001/'),
    ],
];
