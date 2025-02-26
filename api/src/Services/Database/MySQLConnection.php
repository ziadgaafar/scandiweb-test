<?php

namespace App\Services\Database;

use PDO;
use PDOException;
use App\Config\Environment;

/**
 * MySQL Connection Service using Singleton Pattern
 * 
 * This class ensures only one database connection exists throughout the application
 * and provides a global access point to that connection.
 */
class MySQLConnection
{
    /**
     * @var MySQLConnection|null Singleton instance
     */
    private static ?MySQLConnection $instance = null;

    /**
     * @var PDO|null Database connection
     */
    private ?PDO $connection = null;

    /**
     * Private constructor to prevent direct instantiation
     * Initializes database connection using environment variables
     * Required env variables:
     * - DB_HOST: database host (default: localhost)
     * - DB_PORT: database port (default: 3306)
     * - DB_DATABASE: database name
     * - DB_USERNAME: database username
     * - DB_PASSWORD: database password
     */
    private function __construct()
    {
        try {
            $this->connection = new PDO(
                sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=utf8",
                    Environment::get('DB_HOST', 'localhost'),
                    Environment::get('DB_PORT', '3306'),
                    Environment::get('DB_DATABASE')
                ),
                Environment::get('DB_USERNAME'),
                Environment::get('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Connection failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }

    /**
     * Get the singleton instance
     * 
     * @return MySQLConnection
     */
    public static function getInstance(): MySQLConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection instance
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
