<?php

return [
    'base_uri' => env('CMI_BASE_URI', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
    'client_id' => env('CMI_CLIENT_ID', ''),
    'store_key' => env('CMI_STORE_KEY', ''),
    'ok_url' => env('CMI_OK_URL', ''),
    'fail_url' => env('CMI_FAIL_URL', ''),
    'shop_url' => env('CMI_SHOP_URL', ''),
    'callback_url' => env('CMI_CALLBACK_URL', ''),
    'tran_type' => 'PreAuth', // Set your default transaction type
    'lang' => 'en', // Set your default language
    'currency' => '504', // Set your default currency code
    'hash_algorithm' => 'sha512',
    'encoding' => 'UTF-8',
    'session_timeout' => '120',
];
