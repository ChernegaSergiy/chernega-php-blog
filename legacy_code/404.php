<?php

require_once __DIR__ . '/bootstrap.php';

http_response_code(404);

echo twig()->render('static/404.html.twig', [
    'page_title' => '404 - Сторінку не знайдено',
]);
