<?php

declare(strict_types=1);

use Luttje\Mollie\MolliePaymentGateway;

return [
    'gateway' => [
        'register' => true,
        'id' => MolliePaymentGateway::DEFAULT_ID
    ],
    'bind' => env('MOLLIE_BIND', true),
    'apiKey' => env('MOLLIE_API_KEY'),
];
