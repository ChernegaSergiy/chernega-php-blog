<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../media/tools.php';

requireAdminAuth();
requireAdminRole('editor');

$db = getAdminDatabase();
$mediaFiles = $db->getMediaFiles(30);
$flashes = consumeAdminFlash();

$context = [
    'flashes' => $flashes,
    'media_files' => $mediaFiles,
    'csrf_token' => adminCsrfToken(),
    'current_admin' => adminAuth(),
    'admin_nav_active' => 'media',
];

echo twig()->render('admin/media/index.html.twig', $context);
