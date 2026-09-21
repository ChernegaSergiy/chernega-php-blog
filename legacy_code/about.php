<?php

require_once __DIR__ . '/bootstrap.php';

echo twig()->render('static/about.html.twig', [
    'page_title' => 'Про цей блог',
]);
