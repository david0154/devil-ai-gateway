<?php
require_once __DIR__ . '/config.php';
jsonResponse([
    'currency' => DEFAULT_CURRENCY,
    'plans' => [
        [
            'plan_name' => 'Starter',
            'subscription_amount_inr' => 499,
            'speed_tier' => 'slow',
            'rpm_limit' => 10,
            'limit_per_day' => 200,
            'timeout_seconds' => 90,
            'valid_days' => 30
        ],
        [
            'plan_name' => 'Growth',
            'subscription_amount_inr' => 1499,
            'speed_tier' => 'normal',
            'rpm_limit' => 30,
            'limit_per_day' => 1000,
            'timeout_seconds' => 60,
            'valid_days' => 30
        ],
        [
            'plan_name' => 'Pro',
            'subscription_amount_inr' => 3499,
            'speed_tier' => 'fast',
            'rpm_limit' => 60,
            'limit_per_day' => 5000,
            'timeout_seconds' => 45,
            'valid_days' => 30
        ],
        [
            'plan_name' => 'Enterprise',
            'subscription_amount_inr' => 9999,
            'speed_tier' => 'ultra',
            'rpm_limit' => 120,
            'limit_per_day' => 20000,
            'timeout_seconds' => 30,
            'valid_days' => 30
        ]
    ]
]);
