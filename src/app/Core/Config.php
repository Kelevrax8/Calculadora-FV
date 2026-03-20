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
define('DB_PORT',      3306);
define('DB_NAME',     'app_db');
define('DB_USER',     'app_user');
define('DB_PASSWORD', 'secret'); // ← replace with your new password after changing it
