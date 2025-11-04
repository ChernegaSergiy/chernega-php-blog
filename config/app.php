<?php

return [
    'env' => getenv('APP_ENV') ?: 'development',
    'base_url' => rtrim(getenv('APP_URL') ?: 'https://chernega.eu.org', '/'),
    'twig_cache_enabled' => (getenv('APP_ENV') ?: 'development') === 'production',
    'twig_cache_path' => __DIR__ . '/../cache/twig',
    'default_admin' => [
        'username' => getenv('ADMIN_USERNAME') ?: 'admin',
        'password' => getenv('ADMIN_PASSWORD') ?: 'admin123',
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: null,
        'role' => getenv('ADMIN_ROLE') ?: 'admin',
    ],
];
