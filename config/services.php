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
        /** Fullnode JSON-RPC URL (same network as the SPA). Required for local gas sponsorship. */
        'rpc_url' => env('SUI_RPC_URL', ''),
        /** Bech32 export `suiprivkey1...` (Ed25519 or Secp256k1). Keep in .env only; funds this hot wallet with SUI for gas. */
        'gas_sponsor_private_key' => env('SUI_GAS_SPONSOR_PRIVATE_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shinami (Sui zkLogin prover + Gas Station)
    |--------------------------------------------------------------------------
    |
    | Wallet access key: Dashboard → API keys with Wallet / zkProver scope.
    | Gas access key: key tied to a Gas Station fund on the target network.
    | Region in SHINAMI_API_BASE must match the region where keys were created.
    |
    */
    'shinami' => [
        'api_base' => env('SHINAMI_API_BASE', 'https://api.us1.shinami.com'),
        'wallet_access_key' => env(
            'SHINAMI_WALLET_ACCESS_KEY',
            env('SHINAMI_ZKPROVER_ACCESS_KEY', env('SHINAMI_ACCESS_KEY', env('SHINAMI_API_KEY', '')))
        ),
        'gas_access_key' => env('SHINAMI_GAS_ACCESS_KEY', ''),
    ],

    'atoma' => [
        'api_base' => env('ATOMA_API_BASE', 'https://api.atoma.network'),
        'api_key' => env('ATOMA_API_KEY', ''),
        'model' => env('ATOMA_MODEL', 'openai/gpt-4o-mini'),
        'timeout' => (int) env('ATOMA_TIMEOUT', 60),
    ],

    'openai' => [
        'api_base' => env('OPENAI_API_BASE', 'https://api.openai.com'),
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-5-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        'tts_voice' => env('OPENAI_TTS_VOICE', 'nova'),
        'tts_model' => env('OPENAI_TTS_MODEL', 'tts-1'),
        'stt_model' => env('OPENAI_STT_MODEL', 'whisper-1'),
    ],

    'deepseek' => [
        'api_base' => env('DEEPSEEK_API_BASE', 'https://api.deepseek.com'),
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 60),
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
