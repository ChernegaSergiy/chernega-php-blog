<?php

require_once __DIR__ . '/auth.php';

if (isAdminAuthenticated()) {
    logoutAdmin();
    adminFlash('success', 'You have been signed out.');
}

header('Location: /admin/login.php');
exit;
