<?php

namespace App\Models;

use App\Models\Abstract\AbstractModel;
use PDO;

class Category extends AbstractModel
{
    protected string $table = 'categories';
    protected array $fillable = ['name'];

    public function getCategoriesWithProducts(): array
    {
        try {
            $stmt = $this->connection->query(
                "SELECT c.*, COUNT(p.id) as product_count 
                FROM {$this->table} c 
                LEFT JOIN products p ON p.category = c.name 
                GROUP BY c.name"
            );

            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categories as &$category) {
                $this->fill($category);
            }
            return $categories;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching categories with products: " . $e->getMessage());
        }
    }

    public function findByName(string $name): ?self
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE name = :name"
            );
            $stmt->execute(['name' => $name]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $this->fill($result);
                return $this;
            }
            return null;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error finding category by name: " . $e->getMessage());
        }
    }

    public function getProductsByCategory(string $name): array
    {
        try {
            $stmt = $this->connection->prepare(
                "SELECT p.* FROM products p 
                WHERE p.category = :category"
            );
            $stmt->execute(['category' => $name]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching products by category: " . $e->getMessage());
        }
    }
}
