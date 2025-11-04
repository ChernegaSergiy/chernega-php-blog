<?php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Returns application configuration values, loading them once per request.
 */
function appConfig(): array
{
    static $config = null;
    if (null !== $config) {
        return $config;
    }

    $defaults = [
        'env' => 'development',
        'base_url' => 'https://chernega.eu.org',
        'twig_cache_enabled' => false,
        'twig_cache_path' => __DIR__ . '/cache/twig',
    ];

    $configFile = __DIR__ . '/config/app.php';
    if (file_exists($configFile)) {
        $loaded = require $configFile;
        if (is_array($loaded)) {
            $config = array_merge($defaults, $loaded);
        }
    }

    if (null === $config) {
        $config = $defaults;
    }

    if ($config['twig_cache_enabled']) {
        $cacheDir = $config['twig_cache_path'];
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }
    }

    return $config;
}

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

    $config = appConfig();

    $twig = new Environment($loader, [
        'cache' => $config['twig_cache_enabled'] ? $config['twig_cache_path'] : false,
        'auto_reload' => 'production' !== $config['env'],
    ]);

    $twig->addGlobal('site', [
        'title' => '~/chernega.blog',
        'navigation' => [
            ['label' => 'posts', 'url' => '/posts.php'],
            ['label' => 'about', 'url' => '/about.php'],
            ['label' => 'contact', 'url' => '/contact.php'],
        ],
        'footer' => '© 2024 chernega.eu.org | Powered by SOLARIZED TERMINAL UI',
        'base_url' => $config['base_url'],
    ]);

    $twig->addGlobal('config', $config);

    return $twig;
}
