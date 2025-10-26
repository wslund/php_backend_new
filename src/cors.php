<?php
declare(strict_types=1);

$allowed = array_filter(array_map('trim', explode(',', getenv('FRONTEND_ORIGINS') ?: '')));
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($origin && in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header("Vary: Origin");
  if (getenv('CORS_ALLOW_CREDENTIALS') === 'true') {
    header("Access-Control-Allow-Credentials: true");
  }
}
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
if ($method === 'OPTIONS') { http_response_code(204); exit; }
