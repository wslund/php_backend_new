<?php
// ... bootstrapping ovanför (autoload, cors, header etc.)

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($method === 'HEAD') $method = 'GET';

try {
  // --- befintliga rutter ---
  if ($uri === '/' && $method === 'GET') { /* ... */ exit; }
  if ($uri === '/health' && $method === 'GET') { /* ... */ exit; }

  // ⬇️ KLIStra IN DEBUG-ROUTEN HÄR (tillfälligt)
  // GET /debug/db   (TA BORT NÄR KLART)
  if ($uri === '/debug/db' && $method === 'GET') {
    $pdo = \App\Database::pdo();
    $db  = $pdo->query('SELECT current_database()')->fetchColumn();
    $cnt = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo json_encode(['db'=>$db, 'users_count'=>(int)$cnt]);
    exit;
  }

  // (valfritt) fler rutter, t.ex. /auth/login, /auth/me ...

  // --- 404 som sista utväg ---
  http_response_code(404);
  echo json_encode(['error' => 'not found', 'path' => $uri]);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error', 'message' => $e->getMessage()]);
}
