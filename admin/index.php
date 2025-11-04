<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../helpers.php';

requireAdminAuth();
requireAdminRole('editor');

$db = getAdminDatabase();
$postsRaw = $db->getAllPosts();
$posts = array_map('mapPostForAdminTable', $postsRaw);

$flashes = consumeAdminFlash();
$currentAdmin = adminAuth();
$auditLogs = adminHasRole('admin') ? $db->getRecentAuditLogs(20) : [];

echo twig()->render('admin/dashboard.html.twig', [
    'posts' => $posts,
    'csrf_token' => adminCsrfToken(),
    'flashes' => $flashes,
    'current_admin' => $currentAdmin,
    'audit_logs' => $auditLogs,
    'admin_nav_active' => 'dashboard',
]);
