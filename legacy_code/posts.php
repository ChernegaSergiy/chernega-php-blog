<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bootstrap.php';

function buildPageUrl(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;

    return '/posts.php?' . http_build_query($params);
}

$db = new Database();
$parser = markdownParser();

$blogSettings = $db->getBlogSettings();
$postsPerPage = (int) ($blogSettings['posts_per_page'] ?? 5);

$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$currentPage = max(1, $currentPage);
$searchQuery = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? trim((string) $_GET['category']) : '';

$categoriesRaw = $db->getAllCategories();
$posts = [];
$totalPosts = 0;
$commandDisplay = 'view all_posts';

if ('' !== $searchQuery) {
    $totalPosts = $db->countSearchPosts($searchQuery);
    $offset = ($currentPage - 1) * $postsPerPage;
    $posts = $db->searchPosts($searchQuery, $postsPerPage, $offset);
    $commandDisplay = sprintf('search "%s"', $searchQuery);
} elseif ('' !== $categoryFilter) {
    $totalPosts = $db->countPostsByCategory($categoryFilter);
    $offset = ($currentPage - 1) * $postsPerPage;
    $posts = $db->getPostsByCategory($categoryFilter, $postsPerPage, $offset);
    $commandDisplay = sprintf('view category "%s"', $categoryFilter);
} else {
    $totalPosts = $db->countAllPosts();
    $offset = ($currentPage - 1) * $postsPerPage;
    $posts = $db->getAllPosts($postsPerPage, $offset);
}

$totalPages = (int) ceil($totalPosts / max(1, $postsPerPage));
if ($totalPages > 0 && $currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$postsForView = array_map(static function (array $post) use ($parser) {
    return mapPostForList($post, $parser);
}, $posts);

$categories = array_map(static function ($category) use ($categoryFilter) {
    return [
        'label' => $category,
        'url' => '/posts.php?category=' . urlencode($category),
        'is_active' => $category === $categoryFilter,
    ];
}, $categoriesRaw ?: []);

$pagination = null;
if ($totalPages > 1) {
    $pages = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        $pages[] = [
            'label' => $i,
            'url' => buildPageUrl($i),
            'is_current' => $i === $currentPage,
        ];
    }

    $pagination = [
        'previous' => $currentPage > 1 ? ['url' => buildPageUrl($currentPage - 1)] : null,
        'next' => $currentPage < $totalPages ? ['url' => buildPageUrl($currentPage + 1)] : null,
        'pages' => $pages,
    ];
}

echo twig()->render('posts/index.html.twig', [
    'posts' => $postsForView,
    'pagination' => $pagination,
    'search_query' => $searchQuery,
    'category_filter' => $categoryFilter,
    'categories' => $categories,
    'command_display' => $commandDisplay,
    'page_title' => 'Дописи' . ($categoryFilter ? ' (Категорія: ' . $categoryFilter . ')' : ''),
]);
