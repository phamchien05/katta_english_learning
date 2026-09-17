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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Gemini dùng cho: dịch nghĩa từ vựng hàng loạt (vocab:translate-gemini),
    // và về sau là AI Chat / chấm điểm dịch / phát âm (mục 11, 5, 15).
    // Đây là key mặc định lấy từ .env cho các lệnh artisan; key riêng của từng user
    // (nhập ở trang Cài đặt) sẽ được mã hoá lưu trong DB, không dùng key này.
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],

];
