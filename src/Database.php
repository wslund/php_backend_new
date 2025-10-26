<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
  private static ?PDO $pdo = null;

  public static function pdo(): PDO
  {
    if (self::$pdo instanceof PDO) return self::$pdo;

    $url = getenv('DATABASE_URL'); // Render Postgres External Database URL
    if (!$url) {
      throw new \RuntimeException('DATABASE_URL not set');
    }

    // postgres://user:pass@host:port/dbname
    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) {
      throw new \RuntimeException('Invalid DATABASE_URL');
    }

    $host = $parts['host'];
    $port = (int)($parts['port'] ?? 5432);
    $user = urldecode($parts['user'] ?? '');
    $pass = urldecode($parts['pass'] ?? '');
    $db   = ltrim($parts['path'] ?? '', '/');

    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $db);
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    self::$pdo = new PDO($dsn, $user, $pass, $options);
    return self::$pdo;
  }
}
