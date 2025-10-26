<?php
declare(strict_types=1);
namespace App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class Auth {
  public static function issueToken(array $claims): string {
    $secret = getenv('JWT_SECRET') ?: 'change_me';
    $payload = array_merge([
      'iss' => 'render-php-backend',
      'iat' => time(),
      'exp' => time() + 3600, // 1h
    ], $claims);
    return JWT::encode($payload, $secret, 'HS256');
  }

  public static function verifyToken(string $jwt): array {
    $secret = getenv('JWT_SECRET') ?: 'change_me';
    $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
    return json_decode(json_encode($decoded), true);
  }

  public static function bearerToken(): ?string {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/Bearer\s+(.+)/i', $h, $m) ? trim($m[1]) : null;
  }
}
