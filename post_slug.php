<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bootstrap.php';

function renderNotFound(): void
{
    header('HTTP/1.0 404 Not Found');
    echo twig()->render('static/404.html.twig', [
        'page_title' => '404 - Сторінку не знайдено',
    ]);
    exit;
}

$db = new Database();

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
if ('' === $slug) {
    header('Location: index.php');
    exit;
}

$systemFilesSlugs = [
    'post_slug.php',
    'post_slug',
    'post.php',
    'post',
    'index.php',
    'index',
    'admin_panel.php',
    'admin_panel',
    'posts.php',
    'posts',
    'about.php',
    'about',
    'contact.php',
    'contact',
    'mermaid-diagrams.php',
    'mermaid-diagrams',
    'ul-generator.php',
    'ul-generator',
    'styles.css',
    'uploads',
];

if (in_array($slug, $systemFilesSlugs, true) ||
    in_array($slug . '.php', $systemFilesSlugs, true) ||
    in_array($slug . '.html', $systemFilesSlugs, true)) {
    renderNotFound();
}

$post = $db->getPostBySlug($slug);

if (! $post) {
    renderNotFound();
}

$parser = markdownParser();
$baseUrl = getBaseUrl();
$commandSlug = addcslashes($slug, "\"\\");

$postForView = mapPostForDetails($post, $parser, [
    'canonical_url' => $baseUrl . '/' . ltrim($post['slug'], '/'),
    'debug_command' => sprintf('post_by_slug "%s"', $commandSlug),
]);
$postForView['meta_title'] = $post['meta_title'] ?: $post['title'];

echo twig()->render('posts/show.html.twig', [
    'post' => $postForView,
    'page_title' => $postForView['meta_title'],
    'meta_description' => $postForView['meta_description'],
    'canonical_url' => $postForView['canonical_url'],
]);
