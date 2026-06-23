<?php

$bootstrapAdmins = array_values(array_filter([
    [
        'name' => env('BOOTSTRAP_ADMIN_NAME', 'Admin Nexo'),
        'email' => env('BOOTSTRAP_ADMIN_EMAIL', 'admin@nexo.local'),
        'password' => env('BOOTSTRAP_ADMIN_PASSWORD', 'Nexo'),
    ],
], static fn (array $admin): bool => filled($admin['email']) && filled($admin['password'])));

return [
    'bootstrap_admins' => $bootstrapAdmins,

    'bootstrap_admin_emails' => array_values(array_map(
        static fn (array $admin): string => $admin['email'],
        $bootstrapAdmins
    )),

    'seed_demo_users' => (bool) env('SEED_DEMO_USERS', false),
];
