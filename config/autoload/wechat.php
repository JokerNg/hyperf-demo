<?php

use function Hyperf\Support\env;

return [
    // 微信公众号
    'official_account' => [
        'default' => [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),
            'token' => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN'),
            'aes_key' => env('WECHAT_OFFICIAL_ACCOUNT_AES_KEY'),
        ],
    ],

    // 微信小程序
    'mini_app' => [
        'default' => [
            'app_id' => env('WECHAT_MINI_APP_APPID'),
            'secret' => env('WECHAT_MINI_APP_SECRET'),
            'token' => env('WECHAT_MINI_APP_TOKEN'),
            'aes_key' => env('WECHAT_MINI_APP_AES_KEY'),
        ],
    ],

    // 微信支付
    'pay' => [
        'default' => [
            'app_id' => env('WECHAT_PAY_APPID'),
            'mch_id' => env('WECHAT_PAY_MCH_ID'),
            'private_key' => env('WECHAT_PAY_PRIVATE_KEY'),
            'certificate' => env('WECHAT_PAY_CERTIFICATE'),
            'certificate_serial_no' => env('WECHAT_PAY_CERTIFICATE_SERIAL_NO'),
            'v3_secret_key' => env('WECHAT_PAY_V3_SECRET_KEY'),
        ],
    ],
];
