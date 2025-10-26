<?php
declare(strict_types=1);

// 1) Autoload MÅSTE vara först
require __DIR__ . '/../vendor/autoload.php';

// 2) Ladda CORS (hanterar OPTIONS)
require __DIR__ . '/../src/cors.php';

// 3) (valfritt lokalt) .env — på Render används Environment istället
if (is_file(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($method === 'HEAD') $method = 'GET';

try {
    // Root
    if ($uri === '/' && $method === 'GET') {
        echo json_encode([
            'name' => 'php-backend',
            'status' => 'ok',
            'endpoints' => ['/health', '/auth/login', '/auth/me']
        ]);
        exit;
    }

    // Health
    if ($uri === '/health' && $method === 'GET') {
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // ===== DEBUG ENDPOINTS (TA BORT NÄR KLART) =====

    // Autoload-check
    if ($uri === '/debug/autoload' && $method === 'GET') {
        $ok = class_exists(\App\Database::class);
        echo json_encode([
            'autoload_present' => file_exists(__DIR__ . '/../vendor/autoload.php'),
            'class_exists_App\\Database' => $ok
        ]);
        exit;
    }

    // DB-check
    if ($uri === '/debug/db' && $method === 'GET') {
        $pdo = \App\Database::pdo();
        $db  = $pdo->query('SELECT current_database()')->fetchColumn();
        $cnt = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        echo json_encode(['db' => $db, 'users_count' => (int)$cnt]);
        exit;
    }

    // Användar-check
    if ($uri === '/debug/user' && $method === 'GET') {
        $u = isset($_GET['u']) ? (string)$_GET['u'] : '';
        $pdo = \App\Database::pdo();
        $st = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE LOWER(username)=LOWER(:u) LIMIT 1');
        $st->execute(['u' => $u]);
        $user = $st->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['found' => false, 'query' => $u]);
            exit;
        }
        $stored = (string)$user['password_hash'];

        echo json_encode([
            'found' => true,
            'username' => $user['username'],
            'hash_prefix' => substr($stored, 0, 10),
            'hash_length' => strlen($stored),
            'starts_with_$2' => str_starts_with($stored, '$2'),
            'password_verify_admin123' => (str_starts_with($stored, '$2') ? password_verify('admin123', $stored) : false),
        ]);
        exit;
    }

    // ===== AUTH =====

    // POST /auth/login
    if ($uri === '/auth/login' && $method === 'POST') {
        $pdo = \App\Database::pdo();

        $in = json_decode(file_get_contents('php://input') ?: '[]', true);
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Användarnamn och lösenord krävs']);
            exit;
        }

        $st = $pdo->prepare('
            SELECT id, username, password_hash
            FROM users
            WHERE LOWER(username) = LOWER(:u)
            LIMIT 1
        ');
        $st->execute(['u' => $username]);
        $user = $st->fetch(\PDO::FETCH_ASSOC);

        $ok = false;
        if ($user) {
            $stored = (string)$user['password_hash'];
            if (str_starts_with($stored, '$2')) {
                // bcrypt
                $ok = password_verify($password, $stored);
            } else {
                // TILLFÄLLIG fallback för ev. klartext i DB
                $ok = hash_equals($stored, $password);
                if ($ok) {
                    // MIGRERA direkt till bcrypt
                    $new = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                    $up->execute(['h' => $new, 'id' => $user['id']]);
                }
            }
        }

        if (!$ok) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Fel användarnamn eller lösenord']);
            exit;
        }

        $token = \App\Auth::issueToken([
            'sub' => (string)$user['id'],
            'username' => $user['username'],
        ]);

        echo json_encode(['success' => true, 'token' => $token]);
        exit;
    }

    // GET /auth/me
    if ($uri === '/auth/me' && $method === 'GET') {
        $jwt = \App\Auth::bearerToken();
        if (!$jwt) {
            http_response_code(401);
            echo json_encode(['error' => 'missing bearer token']);
            exit;
        }
        try {
            $claims = \App\Auth::verifyToken($jwt);
            echo json_encode(['ok' => true, 'claims' => $claims]);
        } catch (\Throwable $e) {
            http_response_code(401);
            echo json_encode(['error' => 'invalid token']);
        }
        exit;
    }

    // 404
    http_response_code(404);
    echo json_encode(['error' => 'not found', 'path' => $uri]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error', 'message' => $e->getMessage()]);
}
