<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Database.php';

function adminSessionStart(): void
{
    if (PHP_SESSION_ACTIVE !== session_status()) {
        session_start();
    }
}

function adminAuth(): array
{
    adminSessionStart();

    return $_SESSION['admin'] ?? [];
}

function isAdminAuthenticated(): bool
{
    return ! empty(adminAuth());
}

function requireAdminAuth(): void
{
    if (! isAdminAuthenticated()) {
        adminFlash('error', 'Please sign in to continue.');
        header('Location: /admin/login.php');
        exit;
    }
}

function adminRole(): string
{
    $admin = adminAuth();

    return $admin['role'] ?? 'viewer';
}

function adminRoleHierarchy(): array
{
    return [
        'viewer' => 10,
        'editor' => 20,
        'admin' => 30,
    ];
}

function adminHasRole(string $requiredRole): bool
{
    $hierarchy = adminRoleHierarchy();
    $current = adminRole();
    if (! isset($hierarchy[$requiredRole])) {
        return false;
    }

    $currentValue = $hierarchy[$current] ?? 0;
    $requiredValue = $hierarchy[$requiredRole];

    return $currentValue >= $requiredValue;
}

function requireAdminRole(string $requiredRole): void
{
    if (! adminHasRole($requiredRole)) {
        adminFlash('error', 'You do not have permission to perform this action.');
        header('Location: /admin/');
        exit;
    }
}

function adminAudit(string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
{
    $db = getAdminDatabase();
    $admin = adminAuth();
    $adminId = $admin['id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $db->logAudit($adminId, $action, $entityType, $entityId, $metadata, $ipAddress);
}
function loginAdmin(array $admin): void
{
    adminSessionStart();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id' => (int) $admin['id'],
        'username' => $admin['username'],
        'role' => $admin['role'] ?? 'editor',
    ];
}

function logoutAdmin(): void
{
    adminSessionStart();
    $admin = adminAuth();
    if (! empty($admin)) {
        adminAudit('logout', null, null, ['username' => $admin['username'] ?? '']);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function adminFlash(string $type, string $message): void
{
    adminSessionStart();
    $_SESSION['admin_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeAdminFlash(): array
{
    adminSessionStart();
    $messages = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);

    return $messages;
}

function adminCsrfToken(): string
{
    adminSessionStart();
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf'];
}

function verifyAdminCsrf(?string $token): bool
{
    adminSessionStart();
    return isset($_SESSION['admin_csrf']) && hash_equals($_SESSION['admin_csrf'], (string) $token);
}

function getAdminDatabase(): Database
{
    static $db = null;
    if (null === $db) {
        $db = new Database();
    }

    return $db;
}
