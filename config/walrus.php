<?php

return [
    'publishers' => [
        'mainnet' => rtrim((string) env('WALRUS_MAINNET_PUBLISHER_URL', ''), '/'),
        'testnet' => rtrim((string) env('WALRUS_TESTNET_PUBLISHER_URL', 'https://publisher.walrus-testnet.walrus.space'), '/'),
    ],

    'timeout' => (int) env('WALRUS_HTTP_TIMEOUT', 120),
    'max_upload_bytes' => (int) env('WALRUS_MAX_UPLOAD_BYTES', 26214400),
];
