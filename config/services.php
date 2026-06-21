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
        'vision_model' => env('OPENROUTER_VISION_MODEL', 'google/gemini-2.0-flash-001'),
    ],
];
