<?php

declare(strict_types=1);

require_once __DIR__ . '/Config.php';

namespace App\Core;

use PDO;
use PDOException;

/**
 * Wraps a single PDO connection for the application.
 * Use Database::getInstance() anywhere a connection is needed.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(
        private readonly string $host,
        private readonly string $dbname,
        private readonly string $user,
        private readonly string $password,
        private readonly int    $port    = 3306,
        private readonly string $charset = 'utf8mb4',
    ) {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Returns the single application-wide Database instance,
     * creating it on the first call.
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static(
                host:     getenv('DB_HOST')     ?: DB_HOST,
                dbname:   getenv('DB_NAME')     ?: DB_NAME,
                user:     getenv('DB_USER')     ?: DB_USER,
                password: getenv('DB_PASSWORD') ?: DB_PASSWORD,
                port:     (int)(getenv('DB_PORT') ?: DB_PORT),
            );
        }

        return static::$instance;
    }

    /**
     * Returns the underlying PDO connection.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // Prevent cloning and unserialization of the singleton
    private function __clone() {}
    public function __wakeup(): never
    {
        throw new \RuntimeException('Cannot unserialize a singleton.');
    }
}
