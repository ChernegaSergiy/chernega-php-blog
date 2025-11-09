<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../helpers.php';

requireAdminAuth();
requireAdminRole('admin');

$db = getAdminDatabase();

$defaults = [
    'blog_title' => '~/chernega.blog',
    'posts_per_page' => 5,
];

$settings = array_merge($defaults, $db->getBlogSettings() ?: []);
$settings['posts_per_page'] = (int) ($settings['posts_per_page'] ?? $defaults['posts_per_page']);

$errors = [];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $blogTitle = trim((string) ($_POST['blog_title'] ?? ''));
        $postsPerPageInput = trim((string) ($_POST['posts_per_page'] ?? ''));

        if ('' === $blogTitle) {
            $errors[] = 'Blog title cannot be empty.';
        }

        if ($postsPerPageInput === '' || ! ctype_digit($postsPerPageInput)) {
            $errors[] = 'Posts per page must be a whole number.';
            $postsPerPage = $defaults['posts_per_page'];
        } else {
            $postsPerPage = (int) $postsPerPageInput;
            if ($postsPerPage < 1 || $postsPerPage > 50) {
                $errors[] = 'Posts per page must be between 1 and 50.';
            }
        }

        $settings['blog_title'] = $blogTitle;
        $settings['posts_per_page'] = $postsPerPage ?? $settings['posts_per_page'];

        if (! $errors) {
            $saved = $db->saveBlogSettings($blogTitle, $postsPerPage);
            if ($saved) {
                adminAudit('settings_updated', 'settings', null, [
                    'blog_title' => $blogTitle,
                    'posts_per_page' => $postsPerPage,
                ]);
                adminFlash('success', 'Settings updated successfully.');
                header('Location: /admin/settings.php');
                exit;
            }

            $errors[] = 'Failed to save settings: ' . $db->getLastError();
        }
    }
}

$flashes = consumeAdminFlash();

echo twig()->render('admin/settings.html.twig', [
    'settings' => $settings,
    'errors' => $errors,
    'csrf_token' => adminCsrfToken(),
    'flashes' => $flashes,
    'current_admin' => adminAuth(),
    'admin_nav_active' => 'settings',
]);
