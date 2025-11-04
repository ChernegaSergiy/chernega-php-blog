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

if (empty($_FILES['media_file'])) {
    adminFlash('error', 'No file selected for upload.');
    header('Location: /admin/media/index.php');
    exit;
}

try {
    $processed = media_process_upload($_FILES['media_file']);
    $db = getAdminDatabase();

    $mediaId = $db->addMediaFile(
        $processed['filename'],
        $processed['original_filename'],
        $processed['relative_path'],
        $processed['mime_type'],
        $processed['size_bytes'],
        $processed['width'],
        $processed['height']
    );

    if (! $mediaId) {
        @unlink($processed['absolute_path']);
        throw new RuntimeException('Failed to save media metadata: ' . $db->getLastError());
    }

    $summary = media_cleanup_storage($db);

    adminAudit('media_uploaded', 'media', (int) $mediaId, [
        'filename' => $processed['original_filename'],
        'mime' => $processed['mime_type'],
        'size' => $processed['size_bytes'],
    ]);

    adminFlash('success', 'Media uploaded successfully.');
    if ($summary['removed_files'] || $summary['removed_records'] || $summary['removed_directories']) {
        adminFlash('info', sprintf(
            'Housekeeping removed %d file(s), %d record(s), %d folder(s).',
            $summary['removed_files'],
            $summary['removed_records'],
            $summary['removed_directories']
        ));
    }
} catch (RuntimeException $e) {
    adminFlash('error', $e->getMessage());
}

$limitParam = isset($_POST['media_limit']) ? (int) $_POST['media_limit'] : null;
$redirect = '/admin/media/index.php';
if (null !== $limitParam && $limitParam > 0) {
    $redirect .= '?limit=' . max(1, min(200, $limitParam));
}

header('Location: ' . $redirect);
exit;