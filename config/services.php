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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'thegraph' => [
        'gateway_url' => env('THEGRAPH_GATEWAY_URL', 'https://gateway.thegraph.com/api'),
        'api_key' => env('THEGRAPH_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

    'smsclub' => [
        'token' => env('SMSCLUB_TOKEN'),
        'sender' => env('SMSCLUB_SENDER'),
        'endpoint' => env('SMSCLUB_ENDPOINT', 'https://im.smsclub.mobi/sms/send'),
    ],

    'sui' => [
        'zklogin_prover_url' => env('SUI_ZKLOGIN_PROVER_URL', 'https://prover-dev.mystenlabs.com/v1'),
        'verify_node_binary' => env('SUI_VERIFY_NODE_BINARY', 'node'),
    ],

    'zerion' => [
        'api_key' => env('ZERION_API_KEY'),
        'wallet_address' => env('ZERION_WALLET_ADDRESS'),
        'chain_ids' => env('ZERION_CHAIN_IDS', ''),
    ],

    'zerox' => [
        'api_key' => env('ZEROX_API_KEY'),
    ],

    'oneinch' => [
        'api_key' => env('ONEINCH_API_KEY'),
        'referrer' => env('ONEINCH_REFERRER'),
    ],

    'alchemy' => [
        'key' => env('ALCHEMY_API_KEY'),
    ],

    'coingecko' => [
        'api_key' => env('COINGECKO_API_KEY'),
    ],

    'defillama' => [
        'protocol_slug' => env('DEFILLAMA_PROTOCOL_SLUG'),
    ],

];
