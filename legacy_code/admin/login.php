<?php

require_once __DIR__ . '/auth.php';

if (isAdminAuthenticated()) {
    header('Location: /admin/');
    exit;
}

$error = null;
$lastUsername = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
$csrfToken = adminCsrfToken();
$flashes = consumeAdminFlash();

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ('' === $username || '' === $password) {
            $error = 'Username and password are required.';
        } else {
            $db = getAdminDatabase();
            $admin = $db->getAdminByUsername($username);
            if ($admin && password_verify($password, $admin['password'])) {
                loginAdmin($admin);
                adminAudit('login_success', null, null, ['username' => $username]);
                adminFlash('success', 'Signed in successfully.');
                header('Location: /admin/');
                exit;
            }

            adminAudit('login_failed', null, null, ['username' => $username]);
            $error = 'Invalid credentials provided.';
        }
    }
}

echo twig()->render('admin/login.html.twig', [
    'error' => $error,
    'last_username' => $lastUsername,
    'csrf_token' => $csrfToken,
    'flashes' => $flashes,
]);
