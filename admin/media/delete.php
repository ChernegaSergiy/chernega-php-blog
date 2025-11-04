<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../media/tools.php';

requireAdminAuth();
requireAdminRole('editor');

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    header('Location: /admin/media/index.php');
    exit;
}

if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
    adminFlash('error', 'Invalid security token.');
    header('Location: /admin/media/index.php');
    exit;
}

$mediaId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($mediaId <= 0) {
    adminFlash('error', 'Invalid media identifier.');
    header('Location: /admin/media/index.php');
    exit;
}

$limitParam = isset($_POST['limit']) ? max(1, min(200, (int) $_POST['limit'])) : null;
$offsetParam = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : null;

$db = getAdminDatabase();
$media = $db->getMediaById($mediaId);
if (! $media) {
    adminFlash('error', 'Media entry not found.');
    header('Location: /admin/media/index.php');
    exit;
}

$absolutePath = media_relative_to_absolute($media['storage_path']);
$errors = [];

if (file_exists($absolutePath) && ! @unlink($absolutePath)) {
    $errors[] = 'Unable to remove file from storage.';
}

if (! $db->deleteMedia($mediaId)) {
    $errors[] = 'Failed to remove media record: ' . $db->getLastError();
}

if (empty($errors)) {
    adminAudit('media_deleted', 'media', $mediaId, ['filename' => $media['original_filename']]);
    adminFlash('success', 'Media deleted successfully.');
} else {
    adminAudit('media_delete_failed', 'media', $mediaId, ['errors' => $errors]);
    adminFlash('error', implode(' ', $errors));
}

$query = [];
if (null !== $limitParam) {
    $query['limit'] = $limitParam;
}
if (null !== $offsetParam) {
    $query['offset'] = max(0, $offsetParam);
}

$redirect = '/admin/media/index.php' . (empty($query) ? '' : '?' . http_build_query($query));

header('Location: ' . $redirect);
exit;