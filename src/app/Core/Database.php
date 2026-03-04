<?php

declare(strict_types=1);

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
        private readonly string $charset = 'utf8mb4',
    ) {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

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
                host:     $_ENV['DB_HOST']     ?? 'db',
                dbname:   $_ENV['DB_NAME']     ?? 'app_db',
                user:     $_ENV['DB_USER']     ?? 'app_user',
                password: $_ENV['DB_PASSWORD'] ?? 'secret',
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
