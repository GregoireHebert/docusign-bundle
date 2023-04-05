<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

date_default_timezone_set('UTC');

// PHPUnit's autoloader
if (!file_exists($phpUnitAutoloaderPath = __DIR__.'/../vendor/bin/.phpunit/phpunit/vendor/autoload.php')) {
    exit('PHPUnit is not installed. Please run vendor/bin/simple-phpunit --version to install it');
}

$phpunitLoader = require $phpUnitAutoloaderPath;
// Don't register the PHPUnit autoloader before the normal autoloader to prevent weird issues
$phpunitLoader->unregister();
$phpunitLoader->register();

require __DIR__.'/../vendor/autoload.php';

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
if (is_array($env = @include __DIR__.'/.env.local.php')) {
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_ENV[$k] ?? (isset($_SERVER[$k]) && !str_starts_with($k, 'HTTP_') ? $_SERVER[$k] : $v);
    }
} else {
    // load all the .env files
    if (method_exists(Dotenv::class, 'loadEnv')) {
        (method_exists(Dotenv::class, 'usePutenv') ? (new Dotenv())->usePutenv(false) : new Dotenv())->loadEnv(__DIR__.'/.env');
    } else {
        (new Dotenv())->load(__DIR__.'/.env');
    }
}

$_SERVER += $_ENV;
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], \FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
