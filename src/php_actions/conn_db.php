<?php
// ─────────────────────────────────────────────────────────────────────────────
//  DB helper — returns a singleton MySQLi connection
// ─────────────────────────────────────────────────────────────────────────────
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli('db', 'app_user', 'secret', 'app_db');
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]);
            exit;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
