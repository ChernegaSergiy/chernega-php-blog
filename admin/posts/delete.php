<?php

require_once __DIR__ . '/../auth.php';

requireAdminAuth();

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    header('Location: /admin/');
    exit;
}

if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
    adminFlash('error', 'Invalid security token.');
    header('Location: /admin/');
    exit;
}

$postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($postId <= 0) {
    adminFlash('error', 'Invalid post identifier.');
    header('Location: /admin/');
    exit;
}

$db = getAdminDatabase();

if ($db->deletePost($postId)) {
    adminFlash('success', 'Post deleted successfully.');
} else {
    adminFlash('error', 'Failed to delete post: ' . $db->getLastError());
}

header('Location: /admin/');
exit;
