<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Auth;

require __DIR__ . '/../src/cors.php';

if (is_file(__DIR__ . '/../.env')) {
  Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($method === 'HEAD') $method = 'GET';

try {
  if ($uri === '/' || $uri === '') {
    echo json_encode(['name'=>'php-backend','status'=>'ok','endpoints'=>['/health','/auth/login','/auth/me']]);
    exit;
  }

  if ($uri === '/health') {
    echo json_encode(['status' => 'ok']);
    exit;
  }

  // POST /auth/login
  if ($uri === '/auth/login' && $method === 'POST') {
    $pdo = Database::pdo();
    $input = json_decode(file_get_contents('php://input') ?: '[]', true);
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($username === '' || $password === '') {
      http_response_code(400);
      echo json_encode(['success'=>false,'message'=>'Användarnamn och lösenord krävs']);
      exit;
    }

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :u LIMIT 1');
    $stmt->execute(['u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
      http_response_code(401);
      echo json_encode(['success'=>false,'message'=>'Fel användarnamn eller lösenord']);
      exit;
    }

    $token = Auth::issueToken(['sub'=>(string)$user['id'],'username'=>$user['username']]);
    echo json_encode(['success'=>true,'token'=>$token]);
    exit;
  }

  // GET /auth/me (exempel skyddad)
  if ($uri === '/auth/me' && $method === 'GET') {
    $jwt = Auth::bearerToken();
    if (!$jwt) { http_response_code(401); echo json_encode(['error'=>'missing bearer token']); exit; }
    try {
      $claims = Auth::verifyToken($jwt);
      echo json_encode(['ok'=>true,'claims'=>$claims]);
    } catch (\Throwable $e) {
      http_response_code(401); echo json_encode(['error'=>'invalid token']);
    }
    exit;
  }

  http_response_code(404);
  echo json_encode(['error'=>'not found','path'=>$uri]);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'server error','message'=>$e->getMessage()]);
}
