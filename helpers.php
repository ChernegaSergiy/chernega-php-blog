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
    if (! empty($post['article_image'])) {
        $articleImage = $post['article_image'];
        if (! preg_match('#^https?://#i', $articleImage)) {
            $articleImage = 'https://chernega.eu.org/' . ltrim($articleImage, '/');
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
