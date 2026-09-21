<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bootstrap.php';

$db = new Database();

$post = null;
if (isset($_GET['slug'])) {
    $slug = trim((string) $_GET['slug']);
    $post = $db->getPostBySlug($slug);
} elseif (isset($_GET['id'])) {
    $postId = (int) $_GET['id'];
    $post = $db->getPost($postId);
}

if (! $post) {
    header('Location: index.php');
    exit;
}

$parser = markdownParser();

$baseUrl = getBaseUrl();

$postForView = mapPostForDetails($post, $parser, [
    'canonical_url' => $baseUrl . '/post.php?slug=' . $post['slug'],
    'debug_command' => 'post_by_slug "' . $post['slug'] . '"',
]);
$postForView['meta_title'] = $post['meta_title'] ?: $post['title'];

echo twig()->render('posts/show.html.twig', [
    'post' => $postForView,
    'page_title' => $postForView['meta_title'],
    'meta_description' => $postForView['meta_description'],
    'canonical_url' => $postForView['canonical_url'],
]);
