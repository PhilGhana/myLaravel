<?php

return [
    'telebot' => [
        'enable'       => env('TELEBOT_ENABLE', false),
        'public_key'   => env('TELEBOT_PUBLIC_KEY'),
        'bot_uid'      => env('TELEBOT_BOT_UID'),
        'callback_url' => env('TELEBOT_CALLBACK_URL'),
        'key'  => env('TELEBOT_KEY'),
        'iv'   => env('TELEBOT_IV'),
        'host' => env('TELEBOT_HOST'),
    ],
];
