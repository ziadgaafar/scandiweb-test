<?php

namespace App\GraphQL\Resolvers;

use App\Services\Database\MySQLConnection;
use PDO;

abstract class AbstractResolver
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = MySQLConnection::getInstance()->getConnection();
    }

    protected function executeQuery(string $query, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database query error: " . $e->getMessage());
        }
    }

    protected function executeSingle(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database query error: " . $e->getMessage());
        }
    }
}
