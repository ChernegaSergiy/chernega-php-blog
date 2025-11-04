<?php

require_once __DIR__ . '/bootstrap.php';

echo twig()->render('static/contact.html.twig', [
    'page_title' => 'Зв\'язок',
]);
