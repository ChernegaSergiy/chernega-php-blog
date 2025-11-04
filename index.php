<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bootstrap.php';

$db = new Database();
$parser = markdownParser();

$blogSettings = $db->getBlogSettings();
$postsToShowOnHomepage = (int) ($blogSettings['posts_per_page'] ?? 5);

$rawPosts = $db->getRecentPosts($postsToShowOnHomepage);
$totalPosts = $db->countAllPosts();

$posts = array_map(static function (array $post) use ($parser) {
    return mapPostForList($post, $parser);
}, $rawPosts);

echo twig()->render('home.html.twig', [
    'posts' => $posts,
    'show_all_posts_link' => $totalPosts > $postsToShowOnHomepage,
]);
