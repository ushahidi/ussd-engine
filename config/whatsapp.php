<?php

return [
    'cloud_api_base_url' => 'https://graph.facebook.com/v14.0/',

    // You would usually get this from a system user in your business account
    // Ensure it has access to "Manage phone numbers ..." and "Messages" in the WABA
    'cloud_api_access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    // Configured in the app
    'cloud_api_webhook_verify_token' => strval(env('WHATSAPP_VERIFY_TOKEN', "default-token")),

    'throw_http_exceptions' => true,
];
