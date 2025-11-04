<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../media/tools.php';

requireAdminAuth();
requireAdminRole('admin');

if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    header('Location: /admin/media/index.php');
    exit;
}

if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
    adminFlash('error', 'Invalid security token.');
    header('Location: /admin/media/index.php');
    exit;
}

$limitParam = isset($_POST['limit']) ? max(1, min(200, (int) $_POST['limit'])) : null;
$offsetParam = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : null;

$db = getAdminDatabase();
$summary = media_cleanup_storage($db);

adminAudit('media_housekeeping', 'media', null, $summary);
adminFlash('success', sprintf(
    'Housekeeping completed: %d file(s) removed, %d record(s) dropped, %d folder(s) cleaned.',
    $summary['removed_files'],
    $summary['removed_records'],
    $summary['removed_directories']
));

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