<?php

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../helpers.php';

requireAdminAuth();
requireAdminRole('editor');

$db = getAdminDatabase();
$postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$currentPost = $db->getPost($postId);

if (! $currentPost) {
    adminFlash('error', 'Post not found.');
    header('Location: /admin/');
    exit;
}

$postData = preparePostFormData($currentPost);
$errors = [];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (! verifyAdminCsrf($_POST['_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        [$validated, $errors] = validatePostInput($_POST, $currentPost);
        if (empty($errors)) {
            $updated = $db->updatePost(
                $postId,
                $validated['title'],
                $validated['category'],
                $validated['content'],
                $validated['created_at'] ?: $currentPost['created_at'],
                $validated['article_image'] ?: null,
                $validated['slug'] ?: null,
                $validated['meta_title'] ?: null,
                $validated['meta_description'] ?: null
            );

            if ($updated) {
                adminAudit('post_updated', 'post', $postId, ['title' => $validated['title'] ?: $currentPost['title']]);
                adminFlash('success', 'Post updated successfully.');
                header('Location: /admin/');
                exit;
            }

            adminAudit('post_update_failed', 'post', $postId, ['error' => $db->getLastError()]);
            $errors[] = 'Failed to update post: ' . $db->getLastError();
        }

        $postData = array_merge($postData, $validated);
        if (! empty($validated['created_at'])) {
            $timestamp = strtotime($validated['created_at']);
            if ($timestamp) {
                $postData['created_at_local'] = gmdate('Y-m-d\TH:i', $timestamp);
            }
        }
    }
}

$csrfToken = adminCsrfToken();

echo twig()->render('admin/post_form.html.twig', [
    'form_title' => 'Edit Post',
    'submit_label' => 'Save changes',
    'post' => $postData,
    'errors' => $errors,
    'nav_active' => 'dashboard',
    'csrf_token' => $csrfToken,
    'current_admin' => adminAuth(),
]);
