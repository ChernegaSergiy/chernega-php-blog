<?php

/**
 * Returns Parsedown instance configured for safe HTML output.
 */
function markdownParser(): Parsedown
{
    static $parsedown = null;
    if (null === $parsedown) {
        if (! class_exists(Parsedown::class)) {
            require_once __DIR__ . '/Parsedown.php';
        }
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
    }

    return $parsedown;
}

/**
 * Returns the configured application base URL.
 */
function getBaseUrl(): string
{
    if (function_exists('appConfig')) {
        $config = appConfig();

        return rtrim($config['base_url'] ?? 'https://chernega.eu.org', '/');
    }

    $configFile = __DIR__ . '/config/app.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
        if (is_array($config) && isset($config['base_url'])) {
            return rtrim($config['base_url'], '/');
        }
    }

    return 'https://chernega.eu.org';
}

/**
 * Generates preview HTML from markdown content.
 */
function generatePreviewHtml(string $content, Parsedown $parser): string
{
    $htmlContent = $parser->text($content);
    $cleanText = strip_tags($htmlContent);
    $noNewLines = str_replace(["\r", "\n"], ' ', $cleanText);
    $trimmedText = trim($noNewLines);
    $truncatedText = mb_substr($trimmedText, 0, 200, 'UTF-8');

    if ('' === $truncatedText) {
        return 'Попередній перегляд недоступний.';
    }

    return nl2br(htmlspecialchars($truncatedText . '...', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * Formats UTC datetime into Europe/Kiev date string.
 */
function formatDateToKiev(string $datetime): string
{
    $date = new DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Kiev'));

    return $date->format('Y-m-d');
}

function formatBytes(int $bytes, int $precision = 1): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = (int) floor(log($bytes, 1024));
    $power = max(0, min($power, count($units) - 1));
    $value = $bytes / (1024 ** $power);

    $decimals = $power >= 2 ? 2 : $precision;

    return number_format($value, $decimals) . ' ' . $units[$power];
}

/**
 * Normalizes post data for list views.
 */
function mapPostForList(array $post, Parsedown $parser): array
{
    return [
        'id' => (int) $post['id'],
        'title' => $post['title'],
        'slug' => $post['slug'],
        'url' => '/' . ltrim($post['slug'], '/'),
        'category' => $post['category'],
        'category_url' => '/posts.php?category=' . urlencode($post['category']),
        'date' => formatDateToKiev($post['created_at']),
        'preview_html' => generatePreviewHtml($post['content'], $parser),
    ];
}

/**
 * Normalizes post data for single view templates.
 */
function mapPostForDetails(array $post, Parsedown $parser, array $overrides = []): array
{
    $contentHtml = $parser->text($post['content']);
    $articleImage = null;
    $baseUrl = getBaseUrl();
    if (! empty($post['article_image'])) {
        $articleImage = $post['article_image'];
        if (! preg_match('#^https?://#i', $articleImage)) {
            $articleImage = $baseUrl . '/' . ltrim($articleImage, '/');
        }
    }

    $base = [
        'id' => (int) $post['id'],
        'title' => $post['title'],
        'slug' => $post['slug'],
        'category' => $post['category'],
        'category_url' => '/posts.php?category=' . urlencode($post['category']),
        'date' => formatDateToKiev($post['created_at']),
        'content_html' => $contentHtml,
        'meta_title' => $post['meta_title'] ?: $post['title'],
        'meta_description' => $post['meta_description'] ?: mb_substr(strip_tags($contentHtml), 0, 160, 'UTF-8'),
        'article_image' => $articleImage,
        'date_published' => date('c', strtotime($post['created_at'])),
        'date_modified' => date('c', strtotime($post['updated_at'] ?? $post['created_at'])),
    ];

    return array_merge($base, $overrides);
}

function mapPostForAdminTable(array $post): array
{
    $createdAt = isset($post['created_at']) ? date('Y-m-d H:i', strtotime($post['created_at'])) : '';
    $updatedAtSource = $post['updated_at'] ?? $post['created_at'] ?? null;
    $updatedAt = $updatedAtSource ? date('Y-m-d H:i', strtotime($updatedAtSource)) : '';

    return [
        'id' => (int) $post['id'],
        'title' => $post['title'],
        'category' => $post['category'],
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ];
}

function emptyPostFormData(): array
{
    return [
        'id' => null,
        'title' => '',
        'slug' => '',
        'category' => '',
        'meta_title' => '',
        'meta_description' => '',
        'article_image' => '',
        'content' => '',
        'created_at' => null,
        'created_at_local' => null,
    ];
}

function preparePostFormData(array $post): array
{
    $data = emptyPostFormData();
    foreach (array_keys($data) as $key) {
        if (array_key_exists($key, $post)) {
            $data[$key] = $post[$key];
        }
    }

    $createdAt = $post['created_at'] ?? null;
    if ($createdAt) {
        $timestamp = strtotime($createdAt);
        if ($timestamp) {
            $data['created_at_local'] = gmdate('Y-m-d\TH:i', $timestamp);
        }
    }

    return $data;
}

function validatePostInput(array $input, ?array $existing = null): array
{
    $data = [
        'title' => trim((string) ($input['title'] ?? '')),
        'slug' => trim((string) ($input['slug'] ?? '')),
        'category' => trim((string) ($input['category'] ?? '')),
        'meta_title' => trim((string) ($input['meta_title'] ?? '')),
        'meta_description' => trim((string) ($input['meta_description'] ?? '')),
        'article_image' => trim((string) ($input['article_image'] ?? '')),
        'content' => trim((string) ($input['content'] ?? '')),
    ];

    $errors = [];

    if ('' === $data['title']) {
        $errors[] = 'Title is required.';
    }

    if ('' === $data['category']) {
        $errors[] = 'Category is required.';
    }

    if ('' === $data['content']) {
        $errors[] = 'Content is required.';
    }

    if ('' !== $data['slug']) {
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $data['slug']));
        $normalized = trim(preg_replace('/-+/', '-', $normalized), '-');
        if ('' === $normalized) {
            $errors[] = 'Slug contains invalid characters.';
        } else {
            $data['slug'] = $normalized;
        }
    }

    if ('' !== $data['meta_description'] && mb_strlen($data['meta_description']) > 255) {
        $errors[] = 'Meta description should be 255 characters or fewer.';
    }

    if ('' !== $data['article_image'] && mb_strlen($data['article_image']) > 2048) {
        $errors[] = 'Article image URL looks too long.';
    }

    $createdAt = $existing['created_at'] ?? null;
    $createdAtInput = trim((string) ($input['created_at'] ?? ''));
    if ('' !== $createdAtInput) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $createdAtInput, new DateTimeZone('UTC'));
        if ($dt instanceof DateTime) {
            $createdAt = $dt->format('Y-m-d H:i:s');
        } else {
            $errors[] = 'Created at has invalid format.';
        }
    }

    $data['created_at'] = $createdAt;

    return [$data, $errors];
}
