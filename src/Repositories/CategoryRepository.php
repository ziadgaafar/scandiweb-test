<?php

namespace App\Repositories;

use PDO;
use App\Services\Database\MySQLConnection;

class CategoryRepository
{
    private PDO $connection;
    private string $table = 'categories';

    public function __construct()
    {
        $this->connection = MySQLConnection::getInstance()->getConnection();
    }

    /**
     * Get all categories with optional product count
     *
     * @param bool $withProductCount Include product count in results
     * @return array Array of categories
     */
    public function getAll(bool $withProductCount = false): array
    {
        $query = $withProductCount
            ? "SELECT c.*, COUNT(p.id) as product_count 
               FROM {$this->table} c 
               LEFT JOIN products p ON p.category = c.name 
               GROUP BY c.name"
            : "SELECT * FROM {$this->table}";

        try {
            $stmt = $this->connection->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching categories: " . $e->getMessage());
        }
    }

    /**
     * Get a single category by name
     *
     * @param string $name Category name
     * @return array|null Category data or null if not found
     */
    public function getByName(string $name): ?array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE name = :name"
            );
            $stmt->execute(['name' => $name]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching category: " . $e->getMessage());
        }
    }

    /**
     * Get products in a specific category
     *
     * @param string $categoryName Category name
     * @return array Array of products in the category
     */
    public function getProducts(string $categoryName): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT p.* 
                FROM products p 
                WHERE p.category = :category"
            );
            $stmt->execute(['category' => $categoryName]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching category products: " . $e->getMessage());
        }
    }

    /**
     * Create a new category
     *
     * @param string $name Category name
     * @return bool Success status
     */
    public function create(string $name): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO {$this->table} (name) VALUES (:name)"
            );
            return $stmt->execute(['name' => $name]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error creating category: " . $e->getMessage());
        }
    }

    /**
     * Update a category name
     *
     * @param string $oldName Current category name
     * @param string $newName New category name
     * @return bool Success status
     */
    public function update(string $oldName, string $newName): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "UPDATE {$this->table} SET name = :newName WHERE name = :oldName"
            );
            return $stmt->execute([
                'oldName' => $oldName,
                'newName' => $newName
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error updating category: " . $e->getMessage());
        }
    }

    /**
     * Delete a category
     *
     * @param string $name Category name
     * @return bool Success status
     */
    public function delete(string $name): bool
    {
        try {
            $stmt = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE name = :name"
            );
            return $stmt->execute(['name' => $name]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error deleting category: " . $e->getMessage());
        }
    }
}
