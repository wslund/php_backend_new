<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $url = getenv('DATABASE_URL');
        if (!$url) {
            throw new \RuntimeException('DATABASE_URL not set');
        }

        $p = parse_url($url);
        if (!$p || !isset($p['host'])) {
            throw new \RuntimeException('Invalid DATABASE_URL');
        }

        $dsn  = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $p['host'],
            (int)($p['port'] ?? 5432),
            ltrim($p['path'] ?? '', '/')
        );
        $user = urldecode($p['user'] ?? '');
        $pass = urldecode($p['pass'] ?? '');

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }
}
