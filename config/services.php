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

    'circle_cctp' => [
        'proxy_url' => env('CCTP_IRIS_PROXY_URL', ''),
    ],

    'opendatabot' => [
        'transport_url' => env('OPENDATABOT_TRANSPORT_URL', 'https://opendatabot.com/api/v4/transport'),
        'api_token' => env('OPENDATABOT_API_TOKEN', ''),
        'timeout' => (int) env('OPENDATABOT_TIMEOUT', 12),
    ],

    'autoria' => [
        'base_url' => env('AUTORIA_BASE_URL', 'https://auto.ria.com'),
        'vehicle_check_path' => env('AUTORIA_VEHICLE_CHECK_PATH', '/cars-verifyings/api/vin-verifyings/{vehicleInfo}'),
        'timeout' => (int) env('AUTORIA_TIMEOUT', 15),
        'cache_ttl' => (int) env('AUTORIA_CACHE_TTL', 1800),
    ],

    'sumsub' => [
        'base_url' => env('SUMSUB_BASE_URL', 'https://api.sumsub.com'),
        'app_token' => env('SUMSUB_APP_TOKEN', ''),
        'secret_key' => env('SUMSUB_SECRET_KEY', ''),
        'level_name' => env('SUMSUB_LEVEL_NAME', 'basic-kyc-level'),
        'token_ttl' => (int) env('SUMSUB_TOKEN_TTL', 600),
    ],

    'smsclub' => [
        'token' => env('SMSCLUB_TOKEN'),
        'sender' => env('SMSCLUB_SENDER'),
        'endpoint' => env('SMSCLUB_ENDPOINT', 'https://im.smsclub.mobi/sms/send'),
        'balance_endpoint' => env('SMSCLUB_BALANCE_ENDPOINT', 'https://im.smsclub.mobi/sms/balance'),
    ],

    'email_provider' => [
        'provider' => env('EMAIL_PROVIDER', 'resend'),
        'api_key' => env('EMAIL_PROVIDER_API_KEY', ''),
        'from_email' => env('EMAIL_PROVIDER_FROM_EMAIL', ''),
        'from_name' => env('EMAIL_PROVIDER_FROM_NAME', env('APP_NAME', 'AV8Capital')),
    ],

    'telegram_orders' => [
        'bot_token' => env('TELEGRAM_ORDER_BOT_TOKEN') ?: env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_ORDER_CHAT_ID')
            ?: (env('TELEGRAM_OPERATOR_CHAT_ID')
                ?: (env('TELEGRAM_WEBCHAT_AUTOAGENT_CHAT_ID')
                    ?: env('TELEGRAM_WEBCHAT_DEFAULT_CHAT_ID', ''))),
        'thread_id' => env('TELEGRAM_ORDER_THREAD_ID', ''),
        'timeout' => (int) env('TELEGRAM_ORDER_TIMEOUT', 10),
    ],

    'nova_poshta' => [
        'api_key' => env('NOVA_POSHTA_API_KEY', ''),
        'endpoint' => env('NOVA_POSHTA_ENDPOINT', 'https://api.novaposhta.ua/v2.0/json/'),
        'timeout' => (int) env('NOVA_POSHTA_TIMEOUT', 10),
    ],

    'sui' => [
        'zklogin_prover_url' => env('SUI_ZKLOGIN_PROVER_URL', 'https://prover-dev.mystenlabs.com/v1'),
        'verify_node_binary' => env('SUI_VERIFY_NODE_BINARY', 'node'),
        /** Fullnode JSON-RPC URL (same network as the SPA). Required for local gas sponsorship. */
        'rpc_url' => env('SUI_RPC_URL', 'https://sui-testnet-rpc.publicnode.com'),
        'mainnet_rpc_url' => env('SUI_MAINNET_RPC_URL', 'https://fullnode.mainnet.sui.io:443'),
        /** Bech32 export `suiprivkey1...` (Ed25519 or Secp256k1). Keep in .env only; funds this hot wallet with SUI for gas. */
        'gas_sponsor_private_key' => env('SUI_GAS_SPONSOR_PRIVATE_KEY', ''),
        'defi_protocols' => [
            'suilend' => [
                'type_markers' => env('SUI_SUILEND_TYPE_MARKERS', 'suilend,obligation,strategyownercap,ctoken,lending_market'),
            ],
            'navi' => [
                'type_markers' => env('SUI_NAVI_TYPE_MARKERS', 'navi,account_cap,incentive_v3,lending_core'),
            ],
        ],
    ],

    'av8_capital' => [
        /** Deployed av8_capital package id used by /invest and fund:pools:* commands. */
        'package_id' => env('AV8_CAPITAL_PACKAGE_ID', '0x254d11439c351f25f8bb7c896c7e1a5355de7231fc9bb963b358d6453018f514'),
        'payment_receiver_address' => env('AV8_PAYMENT_RECEIVER_ADDRESS', '0xb1a698b321dd94ba0ad955888d4f9a94262f9ddeb07964d228fcd788f08c5062'),
        'pool_registry_id' => env('AV8_CAPITAL_POOL_REGISTRY_ID', ''),
        'pool_admin_cap_id' => env('AV8_CAPITAL_POOL_ADMIN_CAP_ID', ''),
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

    'manager_ai' => [
        'enabled' => env('MANAGER_AI_ENABLED', false),
        'url' => env('MANAGER_AI_URL', 'http://host.docker.internal:3100'),
        'forwarded_host' => env('MANAGER_AI_FORWARDED_HOST', 'localhost:3100'),
        'laravel_api_url' => env('MANAGER_AI_LARAVEL_API_URL', env('APP_URL', '')),
        'company_id' => env('MANAGER_AI_COMPANY_ID', ''),
        'bridge_secret' => env('MANAGER_AI_BRIDGE_SECRET', ''),
        'webchat_issue_id' => env('MANAGER_AI_WEBCHAT_ISSUE_ID', ''),
        'timeout' => (int) env('MANAGER_AI_TIMEOUT', 10),
        'fallback_to_local' => env('MANAGER_AI_FALLBACK_TO_LOCAL', true),
    ],

    'telegram_webchat' => [
        'enabled' => env('TELEGRAM_WEBCHAT_ENABLED', false),
        'bot_token' => env('TELEGRAM_WEBCHAT_BOT_TOKEN') ?: env('TELEGRAM_BOT_TOKEN', ''),
        'webhook_secret' => env('TELEGRAM_WEBCHAT_WEBHOOK_SECRET') ?: env('WEBHOOK_SECRET', ''),
        'operator_chat_id' => env('TELEGRAM_WEBCHAT_DEFAULT_CHAT_ID') ?: env('TELEGRAM_OPERATOR_CHAT_ID', ''),
        'timeout' => (int) env('TELEGRAM_WEBCHAT_TIMEOUT', 10),
        'sites' => [
            'av8' => [
                'enabled' => env('TELEGRAM_WEBCHAT_AV8_ENABLED', env('TELEGRAM_WEBCHAT_ENABLED', false)),
                'domains' => array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_WEBCHAT_AV8_DOMAINS', 'av8.fund,www.av8.fund')))),
                'chat_id' => env('TELEGRAM_WEBCHAT_AV8_CHAT_ID') ?: (env('TELEGRAM_WEBCHAT_DEFAULT_CHAT_ID') ?: env('TELEGRAM_OPERATOR_CHAT_ID', '')),
                'thread_id' => env('TELEGRAM_WEBCHAT_AV8_THREAD_ID', ''),
            ],
            'autoagent' => [
                'enabled' => env('TELEGRAM_WEBCHAT_AUTOAGENT_ENABLED', env('TELEGRAM_WEBCHAT_ENABLED', false)),
                'domains' => array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_WEBCHAT_AUTOAGENT_DOMAINS', 'autoagent.in.ua,www.autoagent.in.ua')))),
                'chat_id' => env('TELEGRAM_WEBCHAT_AUTOAGENT_CHAT_ID') ?: (env('TELEGRAM_WEBCHAT_DEFAULT_CHAT_ID') ?: env('TELEGRAM_OPERATOR_CHAT_ID', '')),
                'thread_id' => env('TELEGRAM_WEBCHAT_AUTOAGENT_THREAD_ID', ''),
            ],
            'gosnomera' => [
                'enabled' => env('TELEGRAM_WEBCHAT_GOSNOMERA_ENABLED', env('TELEGRAM_WEBCHAT_ENABLED', false)),
                'domains' => array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_WEBCHAT_GOSNOMERA_DOMAINS', 'gosnomera.net.ua,www.gosnomera.net.ua')))),
                'chat_id' => env('TELEGRAM_WEBCHAT_GOSNOMERA_CHAT_ID') ?: (env('TELEGRAM_WEBCHAT_DEFAULT_CHAT_ID') ?: env('TELEGRAM_OPERATOR_CHAT_ID', '')),
                'thread_id' => env('TELEGRAM_WEBCHAT_GOSNOMERA_THREAD_ID', ''),
            ],
        ],
    ],

    'news_publish' => [
        'token' => env('NEWS_PUBLISH_TOKEN', ''),
    ],

    'goods_publish' => [
        'token' => env('GOODS_PUBLISH_TOKEN', ''),
    ],

    'autoagent_sitemap' => [
        'script_path' => env('AUTOAGENT_SITEMAP_SCRIPT_PATH', ''),
        'script_path_template' => env('AUTOAGENT_SITEMAP_SCRIPT_PATH_TEMPLATE', ''),
        'script_base_path' => env('AUTOAGENT_SITEMAP_SCRIPT_BASE_PATH', '/var/www'),
        'script_relative_path' => env('AUTOAGENT_SITEMAP_SCRIPT_RELATIVE_PATH', 'scripts/build-sitemap.mjs'),
        'source_url' => env('AUTOAGENT_SITEMAP_SOURCE_URL', 'https://av8.fund/sitemap.xml?fid=12'),
        'output_path' => env('AUTOAGENT_SITEMAP_OUTPUT_PATH', ''),
        'output_path_template' => env('AUTOAGENT_SITEMAP_OUTPUT_PATH_TEMPLATE', ''),
        'output_base_path' => env('AUTOAGENT_SITEMAP_OUTPUT_BASE_PATH', '/var/www'),
        'output_relative_path' => env('AUTOAGENT_SITEMAP_OUTPUT_RELATIVE_PATH', 'html/sitemap.xml'),
        'node_binary' => env('AUTOAGENT_SITEMAP_NODE_BINARY', 'node'),
        'timeout' => (int) env('AUTOAGENT_SITEMAP_TIMEOUT', 60),
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

    'chainalysis' => [
        'enabled' => env('CHAINALYSIS_ENABLED', true),
        'api_key' => env('CHAINALYSIS_API_KEY', ''),
        'base_url' => env('CHAINALYSIS_API_BASE_URL', 'https://api.chainalysis.com/api/kyt/v2'),
        'mock_mode' => env('CHAINALYSIS_MOCK_MODE', true),
        'fail_open' => env('CHAINALYSIS_FAIL_OPEN', false),
        'cache_minutes' => (int) env('CHAINALYSIS_CACHE_MINUTES', 15),
        'platform_deposit_address' => env('CHAINALYSIS_PLATFORM_DEPOSIT_ADDRESS', ''),
        'mock_blocklist' => env('CHAINALYSIS_MOCK_BLOCKLIST', ''),
        'blocked_risk_levels' => array_values(array_filter(array_map(
            static fn (string $level): string => strtoupper(trim($level)),
            explode(',', (string) env('CHAINALYSIS_BLOCKED_RISK_LEVELS', 'HIGH,SEVERE,CRITICAL')),
        ))),
    ],

    'defillama' => [
        'protocol_slug' => env('DEFILLAMA_PROTOCOL_SLUG'),
    ],

];
