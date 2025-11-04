<?php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Initializes (or reuses) a Twig environment instance.
 *
 * @throws RuntimeException when Twig dependency is missing
 */
function twig(): Environment
{
    static $twig = null;
    if (null !== $twig) {
        return $twig;
    }

    // Attempt to load Composer autoloader when available
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (! class_exists(Environment::class)) {
        throw new RuntimeException('Twig dependency not found. Install via "composer require twig/twig".');
    }

    $loader = new FilesystemLoader(__DIR__ . '/templates');

    $twig = new Environment($loader, [
        'cache' => false, // Enable when cache directory configured on production
        'auto_reload' => true,
    ]);

    $twig->addGlobal('site', [
        'title' => '~/chernega.blog',
        'navigation' => [
            ['label' => 'posts', 'url' => '/posts.php'],
            ['label' => 'about', 'url' => '/about.php'],
            ['label' => 'contact', 'url' => '/contact.php'],
        ],
        'footer' => '© 2024 chernega.eu.org | Powered by SOLARIZED TERMINAL UI',
    ]);

    return $twig;
}
