<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Config.php';

use App\Core\AuthGuard;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── POST /api/auth.php?action=login ──────────────────────────────────────
    // Body: JSON { homeAccountId, username, name }
    // Creates a server-side PHP session for the authenticated user.
    // TODO (production): extract and verify the MSAL idToken field against
    //   Microsoft's JWKS endpoint (https://login.microsoftonline.com/{tenant}/discovery/v2.0/keys)
    //   before trusting the payload.
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $body = (string) file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!is_array($data)
            || !isset($data['homeAccountId'])
            || !is_string($data['homeAccountId'])
            || $data['homeAccountId'] === ''
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit;
        }

        // Start session and store the user — session fixation protection included.
        AuthGuard::createUserSession([
            'homeAccountId' => $data['homeAccountId'],
            'username'      => $data['username'] ?? '',
            'name'          => $data['name'] ?? '',
        ]);

        echo json_encode(['ok' => true]);
        break;

    // ── POST /api/auth.php?action=logout ─────────────────────────────────────
    case 'logout':
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
