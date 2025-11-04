<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../helpers.php';

requireAdminAuth();
requireAdminRole('editor');

$db = getAdminDatabase();
$postData = emptyPostFormData();
$errors = [];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        [$validated, $errors] = validatePostInput($_POST);
        if (empty($errors)) {
            $postId = $db->addPost(
                $validated['title'],
                $validated['category'],
                $validated['content'],
                $validated['article_image'] ?: null,
                $validated['slug'] ?: null,
                $validated['meta_title'] ?: null,
                $validated['meta_description'] ?: null
            );

            if ($postId) {
                adminAudit('post_created', 'post', (int) $postId, ['title' => $validated['title']]);
                adminFlash('success', 'Post created successfully.');
                header('Location: /admin/');
                exit;
            }

            adminAudit('post_create_failed', 'post', null, ['error' => $db->getLastError(), 'title' => $validated['title']]);
            $errors[] = 'Failed to create post: ' . $db->getLastError();
        }

        $postData = array_merge($postData, $validated);
    }
}

$csrfToken = adminCsrfToken();

echo twig()->render('admin/post_form.html.twig', [
    'form_title' => 'Create Post',
    'submit_label' => 'Create',
    'post' => $postData,
    'errors' => $errors,
    'nav_active' => 'create',
    'csrf_token' => $csrfToken,
    'current_admin' => adminAuth(),
]);
