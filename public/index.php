<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database;

require __DIR__ . '/../src/cors.php'; // skickar CORS headers + hanterar OPTIONS

// Ladda .env lokalt (Render använder env-variablerna direkt)
if (is_file(__DIR__ . '/../.env')) {
  Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Routing (superenkel)
try {
  if ($uri === '/health') {
    echo json_encode(['status' => 'ok']);
    exit;
  }

  // /api/items
  if (preg_match('#^/api/items/?$#', $uri)) {
    $pdo = Database::pdo();

    if ($method === 'GET') {
      $stmt = $pdo->query('SELECT id, name, created_at FROM items ORDER BY id DESC');
      $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode($items);
      exit;
    }

    if ($method === 'POST') {
      $input = json_decode(file_get_contents('php://input') ?: '[]', true);
      $name = trim((string)($input['name'] ?? ''));
      if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'name is required']);
        exit;
      }
      $stmt = $pdo->prepare('INSERT INTO items (name) VALUES (:name) RETURNING id, name, created_at');
      $stmt->execute(['name' => $name]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      http_response_code(201);
      echo json_encode($row);
      exit;
    }

    // andra metoder
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
  }

  // 404
  http_response_code(404);
  echo json_encode(['error' => 'not found', 'path' => $uri]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'server error', 'message' => $e->getMessage()]);
}
