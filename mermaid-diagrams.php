<?php

require_once __DIR__ . '/bootstrap.php';

echo twig()->render('tools/mermaid.html.twig', [
    'page_title' => 'Візуалізатор Mermaid.js',
]);
