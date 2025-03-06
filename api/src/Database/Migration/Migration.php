<?php
// src/Database/Migration/Migration.php

namespace App\Database\Migration;

class Migration
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createTables(): void
    {
        // Create migrations table first
        $this->createMigrationsTable();

        // Array of migration SQL commands in correct order
        $migrations = [
            'create_categories_table' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(191) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_currencies_table' => "
                CREATE TABLE IF NOT EXISTS currencies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    label VARCHAR(10) NOT NULL UNIQUE,
                    symbol VARCHAR(5) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_products_table' => "
                CREATE TABLE IF NOT EXISTS products (
                    id VARCHAR(191) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    brand VARCHAR(255) NOT NULL,
                    description TEXT,
                    category VARCHAR(191) NOT NULL,
                    inStock BOOLEAN DEFAULT true,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (category) REFERENCES categories(name) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_product_gallery_table' => "
                CREATE TABLE IF NOT EXISTS product_gallery (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id VARCHAR(191) NOT NULL,
                    image_url TEXT NOT NULL,
                    position INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_attribute_sets_table' => "
                CREATE TABLE IF NOT EXISTS attribute_sets (
                    id VARCHAR(191) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    type ENUM('text', 'swatch') NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_attribute_items_table' => "
                CREATE TABLE IF NOT EXISTS attribute_items (
                    id VARCHAR(191) NOT NULL,
                    attribute_set_id VARCHAR(191) NOT NULL,
                    display_value VARCHAR(255) NOT NULL,
                    value VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id, attribute_set_id),
                    FOREIGN KEY (attribute_set_id) REFERENCES attribute_sets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_product_attributes_table' => "
                CREATE TABLE IF NOT EXISTS product_attributes (
                    product_id VARCHAR(191) NOT NULL,
                    attribute_set_id VARCHAR(191) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (product_id, attribute_set_id),
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (attribute_set_id) REFERENCES attribute_sets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_prices_table' => "
                CREATE TABLE IF NOT EXISTS prices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id VARCHAR(191) NOT NULL,
                    currency_id INT NOT NULL,
                    amount DECIMAL(10, 2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_orders_table' => "
                CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                    currency_id INT NOT NULL,
                    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (currency_id) REFERENCES currencies(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",

            'create_order_items_table' => "
                CREATE TABLE IF NOT EXISTS order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id VARCHAR(191) NOT NULL,
                    quantity INT NOT NULL,
                    unit_price DECIMAL(10, 2) NOT NULL,
                    selected_attributes TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            "
        ];

        // Execute each migration
        foreach ($migrations as $name => $sql) {
            if (!$this->hasMigrationRun($name)) {
                try {
                    $this->pdo->exec($sql);
                    $this->recordMigration($name);
                    echo "Migration $name completed successfully.\n";
                } catch (\PDOException $e) {
                    echo "Error running migration $name: " . $e->getMessage() . "\n";
                    throw $e;
                }
            }
        }
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $this->pdo->exec($sql);
    }

    private function hasMigrationRun(string $migration): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        return (bool) $stmt->fetchColumn();
    }

    private function recordMigration(string $migration): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }
}
