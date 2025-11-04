<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../helpers.php';

requireAdminAuth();

$db = getAdminDatabase();
$postsRaw = $db->getAllPosts();
$posts = array_map('mapPostForAdminTable', $postsRaw);

$flashes = consumeAdminFlash();


echo twig()->render('admin/dashboard.html.twig', [
    'posts' => $posts,
    'csrf_token' => adminCsrfToken(),
    'flashes' => $flashes,
]);
