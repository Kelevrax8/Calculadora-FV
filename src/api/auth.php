<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Config.php';

use App\Core\AuthGuard;

function appPath(string $suffix): string
{
    return BASE_URL . $suffix;
}

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function jsonResponse(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function isAuthConfigured(): bool
{
    return AAD_TENANT_ID !== 'YOUR_TENANT_ID'
        && AAD_CLIENT_ID !== 'YOUR_CLIENT_ID'
        && AAD_CLIENT_SECRET !== 'YOUR_CLIENT_SECRET'
        && AAD_TENANT_ID !== ''
        && AAD_CLIENT_ID !== ''
        && AAD_CLIENT_SECRET !== '';
}

function base64UrlDecode(string $input): string
{
    $remainder = strlen($input) % 4;
    if ($remainder > 0) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return (string) base64_decode(strtr($input, '-_', '+/'));
}

/** @return array<string,mixed> */
function decodeJwtPayload(string $jwt): array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        throw new RuntimeException('Invalid id_token format');
    }

    $payload = json_decode(base64UrlDecode($parts[1]), true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid id_token payload');
    }

    return $payload;
}

function getCallbackUri(): string
{
    if (AAD_REDIRECT_URI !== '') {
        return AAD_REDIRECT_URI;
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($forwardedProto === 'https') {
        $scheme = 'https';
    } else {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        $scheme = $isHttps ? 'https' : 'http';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(BASE_URL, '/');
    $path = $base === '' ? '/' : $base . '/';
    return $scheme . '://' . $host . $path;
}

/** @return array<string,mixed> */
function exchangeCodeForTokens(string $code): array
{
    $tokenEndpoint = 'https://login.microsoftonline.com/' . AAD_TENANT_ID . '/oauth2/v2.0/token';

    $postBody = http_build_query([
        'client_id'     => AAD_CLIENT_ID,
        'client_secret' => AAD_CLIENT_SECRET,
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => getCallbackUri(),
        'scope'         => 'openid profile email',
    ]);

    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content'       => $postBody,
            'timeout'       => 10,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents($tokenEndpoint, false, $context);
    if ($raw === false) {
        throw new RuntimeException('Token exchange failed');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid token response');
    }

    if (isset($decoded['error'])) {
        throw new RuntimeException('Token endpoint error: ' . (string) ($decoded['error_description'] ?? $decoded['error']));
    }

    return $decoded;
}

/** @param array<string,mixed> $claims */
function validateIdTokenClaims(array $claims, string $expectedNonce): void
{
    $now = time();

    $aud = (string) ($claims['aud'] ?? '');
    if ($aud !== AAD_CLIENT_ID) {
        throw new RuntimeException('Invalid token audience');
    }

    $tid = (string) ($claims['tid'] ?? '');
    if ($tid !== AAD_TENANT_ID) {
        throw new RuntimeException('Invalid token tenant');
    }

    $iss = (string) ($claims['iss'] ?? '');
    $expectedIssuer = 'https://login.microsoftonline.com/' . AAD_TENANT_ID . '/v2.0';
    if ($iss !== $expectedIssuer) {
        throw new RuntimeException('Invalid token issuer');
    }

    $exp = (int) ($claims['exp'] ?? 0);
    if ($exp <= $now) {
        throw new RuntimeException('Expired token');
    }

    $nbf = (int) ($claims['nbf'] ?? 0);
    if ($nbf > $now + 60) {
        throw new RuntimeException('Token not active yet');
    }

    $nonce = (string) ($claims['nonce'] ?? '');
    if ($nonce === '' || !hash_equals($expectedNonce, $nonce)) {
        throw new RuntimeException('Invalid token nonce');
    }
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── GET /api/auth.php?action=login ───────────────────────────────────────
    // Starts server-side OAuth authorization code flow.
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            jsonResponse(405, ['error' => 'Method not allowed']);
        }

        if (!isAuthConfigured()) {
            redirectTo(appPath('/?auth_error=config'));
        }

        // Ensure session starts with the same security settings as AuthGuard.
        AuthGuard::currentUser();

        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
        $_SESSION['oauth_nonce'] = bin2hex(random_bytes(16));

        $authorizeQuery = http_build_query([
            'client_id'     => AAD_CLIENT_ID,
            'response_type' => 'code',
            'redirect_uri'  => getCallbackUri(),
            'response_mode' => 'query',
            'scope'         => 'openid profile email',
            'state'         => $_SESSION['oauth_state'],
            'nonce'         => $_SESSION['oauth_nonce'],
        ]);

        $authorizeUrl = 'https://login.microsoftonline.com/' . AAD_TENANT_ID . '/oauth2/v2.0/authorize?' . $authorizeQuery;
        redirectTo($authorizeUrl);

    // ── GET /api/auth.php?action=callback ────────────────────────────────────
    // Handles OAuth code callback, exchanges code for tokens, validates id_token,
    // creates PHP session, and redirects to dashboard.
    case 'callback':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            jsonResponse(405, ['error' => 'Method not allowed']);
        }

        AuthGuard::currentUser();

        if (isset($_GET['error'])) {
            redirectTo(appPath('/?auth_error=provider'));
        }

        $code  = (string) ($_GET['code'] ?? '');
        $state = (string) ($_GET['state'] ?? '');

        $expectedState = (string) ($_SESSION['oauth_state'] ?? '');
        $expectedNonce = (string) ($_SESSION['oauth_nonce'] ?? '');

        if ($code === '' || $state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            unset($_SESSION['oauth_state'], $_SESSION['oauth_nonce']);
            redirectTo(appPath('/?auth_error=state'));
        }

        try {
            $tokens  = exchangeCodeForTokens($code);
            $idToken = (string) ($tokens['id_token'] ?? '');

            if ($idToken === '') {
                throw new RuntimeException('Missing id_token');
            }

            $claims = decodeJwtPayload($idToken);
            validateIdTokenClaims($claims, $expectedNonce);

            $oid      = (string) ($claims['oid'] ?? $claims['sub'] ?? '');
            $tid      = (string) ($claims['tid'] ?? AAD_TENANT_ID);
            $username = (string) ($claims['preferred_username'] ?? $claims['upn'] ?? $claims['email'] ?? '');
            $name     = (string) ($claims['name'] ?? $username);

            AuthGuard::createUserSession([
                'homeAccountId' => ($oid !== '' ? $oid : 'user') . '@' . $tid,
                'username'      => $username,
                'name'          => $name,
            ]);

            unset($_SESSION['oauth_state'], $_SESSION['oauth_nonce']);
            redirectTo(appPath('/pages/dashboard.php'));
        } catch (Throwable $e) {
            unset($_SESSION['oauth_state'], $_SESSION['oauth_nonce']);
            redirectTo(appPath('/?auth_error=token'));
        }

    // ── POST /api/auth.php?action=logout ─────────────────────────────────────
    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(405, ['error' => 'Method not allowed']);
        }

        AuthGuard::currentUser();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        jsonResponse(200, ['ok' => true]);

    default:
        jsonResponse(400, ['error' => 'Unknown action']);
}
