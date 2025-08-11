<?php

return [
    'api_key'              => env('SMS_API_KEY'),
    'api_secret'           => env('SMS_API_SECRET'),
    'base_url'             => env('SMS_API_BASE_URL', 'https://portal.adnsms.com/api/v1/secure'),
    'default_request_type' => 'SINGLE_SMS', // fallback
];
