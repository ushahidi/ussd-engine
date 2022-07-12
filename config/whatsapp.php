<?php

return [
    'cloud_api_base_url' => 'https://graph.facebook.com/v14.0/',
    'cloud_api_access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'cloud_api_webhook_verify_token' => strval(env('WHATSAPP_VERIFY_TOKEN', "default-token")),
    'throw_http_exceptions' => true,
];
