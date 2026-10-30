<?php

/**
 * Autoloader for using taqvim without Composer:
 *     require 'path/to/php/autoload.php';
 * With Composer, `composer require rahimixyz/taqvim` sets this up for you.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Taqvim\\')) {
        $file = __DIR__ . '/src/' . substr($class, 7) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
