<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../media/tools.php';

requireAdminAuth();
requireAdminRole('editor');

$db = getAdminDatabase();

$limit = isset($_GET['limit']) ? max(1, min(200, (int) $_GET['limit'])) : 30;
$offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

$mediaFiles = $db->getMediaFiles($limit, $offset);
$mediaFiles = array_map(static function (array $file) {
    $file['size_human'] = formatBytes((int) $file['size_bytes']);
    $file['url'] = '/uploads/media/' . ltrim($file['storage_path'], '/');

    return $file;
}, $mediaFiles);

$totalCount = $db->countMediaFiles();
$totalSize = $db->sumMediaSize();

$stats = [
    'total_count' => $totalCount,
    'total_size' => $totalSize,
    'total_size_human' => formatBytes($totalSize),
    'showing_count' => count($mediaFiles),
    'offset' => $offset,
    'limit' => $limit,
];

$pagination = [
    'has_previous' => $offset > 0,
    'previous_offset' => max(0, $offset - $limit),
    'has_next' => ($offset + $limit) < $totalCount,
    'next_offset' => $offset + $limit,
    'start' => $totalCount ? $offset + 1 : 0,
    'end' => min($offset + $limit, $totalCount),
    'limit' => $limit,
];

$baseQuery = '/admin/media/index.php?limit=' . $limit;
$pagination['previous_url'] = $pagination['has_previous'] ? $baseQuery . '&offset=' . $pagination['previous_offset'] : null;
$pagination['next_url'] = $pagination['has_next'] ? $baseQuery . '&offset=' . $pagination['next_offset'] : null;

$flashes = consumeAdminFlash();

$context = [
    'flashes' => $flashes,
    'media_files' => $mediaFiles,
    'media_stats' => $stats,
    'pagination' => $pagination,
    'csrf_token' => adminCsrfToken(),
    'current_admin' => adminAuth(),
    'admin_nav_active' => 'media',
];

if ($pagination['has_next'] || $pagination['has_previous']) {
    $context['media_stats']['total_pages'] = (int) ceil(max(1, $totalCount) / $limit);
}

echo twig()->render('admin/media/index.html.twig', $context);
