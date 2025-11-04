<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bootstrap.php';

$db = new Database();
$postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = $db->getPost($postId);

if (! $post) {
    header('Location: index.php');
    exit;
}

$parser = markdownParser();

$postForView = mapPostForDetails($post, $parser, [
    'canonical_url' => 'https://chernega.eu.org/post.php?id=' . $postId,
    'debug_command' => 'post ' . $postId,
]);
$postForView['meta_title'] = $post['meta_title'] ?: $post['title'];

echo twig()->render('posts/show.html.twig', [
    'post' => $postForView,
    'page_title' => $postForView['meta_title'],
    'meta_description' => $postForView['meta_description'],
    'canonical_url' => $postForView['canonical_url'],
]);
