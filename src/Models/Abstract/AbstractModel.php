<?php

namespace App\Models\Abstract;

use App\Services\Database\MySQLConnection;
use PDO;
use PDOException;

abstract class AbstractModel
{
    protected PDO $connection;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $attributes = [];

    public function __construct()
    {
        $this->connection = MySQLConnection::getInstance()->getConnection();
    }

    public function getId(): ?int
    {
        return isset($this->attributes[$this->primaryKey])
            ? (int)$this->attributes[$this->primaryKey]
            : null;
    }

    public function getName(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function fill(array $data): void
    {
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $this->attributes[$field] = $data[$field];
            }
        }
    }

    public function find($id): ?self
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id"
            );
            $stmt->execute(['id' => $id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $this->fill($result);
                return $this;
            }
            return null;
        } catch (PDOException $e) {
            throw new \RuntimeException("Error finding record: " . $e->getMessage());
        }
    }

    public function all(): array
    {
        try {
            $stmt = $this->connection->query("SELECT * FROM {$this->table}");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Error fetching all records: " . $e->getMessage());
        }
    }

    public function create(array $data): ?int
    {
        $data = array_intersect_key($data, array_flip($this->fillable));

        if (empty($data)) {
            return null;
        }

        try {
            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));

            $stmt = $this->connection->prepare(
                "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})"
            );

            $stmt->execute($data);
            $lastId = (int)$this->connection->lastInsertId();

            $this->find($lastId);
            return $lastId;
        } catch (PDOException $e) {
            throw new \RuntimeException("Error creating record: " . $e->getMessage());
        }
    }

    public function update($id, array $data): bool
    {
        $data = array_intersect_key($data, array_flip($this->fillable));

        if (empty($data)) {
            return false;
        }

        try {
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }

            $setClause = implode(', ', $setParts);
            $data['id'] = $id;

            $stmt = $this->connection->prepare(
                "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id"
            );

            $success = $stmt->execute($data);
            if ($success) {
                $this->find($id);
            }
            return $success;
        } catch (PDOException $e) {
            throw new \RuntimeException("Error updating record: " . $e->getMessage());
        }
    }

    public function delete($id): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id"
            );

            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Error deleting record: " . $e->getMessage());
        }
    }

    public function findBy(string $field, $value): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE {$field} = :value"
            );

            $stmt->execute(['value' => $value]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Error finding records by field: " . $e->getMessage());
        }
    }

    /**
     * Execute a query and return multiple rows
     *
     * @param string $query The SQL query
     * @param array $params Query parameters
     * @return array The result set
     * @throws \RuntimeException if the query fails
     */
    protected function executeQuery(string $query, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database query error: " . $e->getMessage());
        }
    }

    /**
     * Execute a query and return a single row
     *
     * @param string $query The SQL query
     * @param array $params Query parameters
     * @return array|null The result row or null if not found
     * @throws \RuntimeException if the query fails
     */
    protected function executeSingle(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database query error: " . $e->getMessage());
        }
    }

    protected function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    protected function commit(): void
    {
        $this->connection->commit();
    }

    protected function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
