<?php

require_once __DIR__ . '/bootstrap.php';

echo twig()->render('tools/license-generator.html.twig', [
    'page_title' => 'Офіційний генератор CSSM Unlimited License v2.0',
    'current_year' => date('Y'),
    'default_holder' => 'Serhii Cherneha',
]);
