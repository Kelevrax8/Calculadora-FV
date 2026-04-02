<?php
// ── URL ──────────────────────────────────────────────────────────────────────
// Leave empty ('') when hosted at the domain root (e.g. https://example.com/).
// Set to the subdirectory path when hosted in a subfolder
// (e.g. '/calculadora-fv' for https://example.com/calculadora-fv/).
define('BASE_URL', '');

// ── Database ─────────────────────────────────────────────────────────────────
// These values are used as fallbacks when DB_HOST / DB_NAME / DB_USER /
// DB_PASSWORD environment variables are not set by the server.
// On a shared host: edit the four lines below.
// On Docker / a server with env vars configured: leave them as-is.
define('DB_HOST',     'db');
define('DB_NAME',     'app_db');
define('DB_USER',     'app_user');
define('DB_PASSWORD', 'secret');

// ── Microsoft Entra ID (Web platform / confidential client) ───────────────
// Prefer environment variables in production.
define('AAD_TENANT_ID',     getenv('AAD_TENANT_ID') ?: 'YOUR_TENANT_ID');
define('AAD_CLIENT_ID',     getenv('AAD_CLIENT_ID') ?: 'YOUR_CLIENT_ID');
define('AAD_CLIENT_SECRET', getenv('AAD_CLIENT_SECRET') ?: 'YOUR_CLIENT_SECRET');
// Optional absolute URI override for OAuth callback.
// Example: https://your-domain/ or https://your-domain/calculadora-fv/
// If empty, the app computes it as current host + BASE_URL + '/'.
define('AAD_REDIRECT_URI',  getenv('AAD_REDIRECT_URI') ?: '');