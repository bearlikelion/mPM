<?php

return [
    'enabled' => env('PUBLIC_DEMO_ENABLED', true),
    'organization_slug' => env('PUBLIC_DEMO_ORG_SLUG', 'public-demo'),
    'organization_name' => env('PUBLIC_DEMO_ORG_NAME', 'mPM Public Demo'),
    'user_email' => env('PUBLIC_DEMO_USER_EMAIL', 'demo@example.test'),
    'user_name' => env('PUBLIC_DEMO_USER_NAME', 'Demo Visitor'),
];
