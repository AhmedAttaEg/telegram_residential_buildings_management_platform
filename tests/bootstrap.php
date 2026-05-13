<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$basePath = dirname(__DIR__);

if (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) === 'testing' && file_exists($basePath.'/.env.testing')) {
    Dotenv::createMutable($basePath, '.env.testing')->safeLoad();
}

if (($_ENV['DB_CONNECTION'] ?? null) === 'mysql' && ! empty($_ENV['DB_DATABASE'] ?? null)) {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $_ENV['DB_HOST'] ?? '127.0.0.1',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    );

    try {
        $pdo = new \PDO(
            $dsn,
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );

        $database = str_replace('`', '``', (string) $_ENV['DB_DATABASE']);
        $collation = $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci';

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE {$collation}");
    } catch (\Throwable) {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_URL=');
        putenv('TEST_DATABASE_FALLBACK=sqlite');

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['DB_URL'] = '';
        $_ENV['TEST_DATABASE_FALLBACK'] = 'sqlite';

        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_URL'] = '';
        $_SERVER['TEST_DATABASE_FALLBACK'] = 'sqlite';
    }
}
