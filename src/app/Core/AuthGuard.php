<?php

declare(strict_types=1);

namespace App\Core;

class AuthGuard
{
    private static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Called by protected HTML pages.
     * Redirects to the app root (login) when no valid session exists.
     */
    public static function requirePage(): void
    {
        self::startSession();

        if (empty($_SESSION['user'])) {
            $base = defined('BASE_URL') ? BASE_URL : '';
            header('Location: ' . ($base !== '' ? $base : '/'));
            exit;
        }
    }

    /**
     * Called by API endpoints.
     * Returns HTTP 401 JSON when no valid session exists.
     */
    public static function requireApi(): void
    {
        self::startSession();

        if (empty($_SESSION['user'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthenticated']);
            exit;
        }
    }

    /**
     * Returns the current session user array, or null if not authenticated.
     */
    public static function currentUser(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }
}
