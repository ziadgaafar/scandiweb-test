<?php
// src/Database/Seeder/DatabaseSeeder.php

namespace App\Database\Seeder;

class DatabaseSeeder
{
    private \PDO $pdo;
    private array $data;

    public function __construct(\PDO $pdo, array $data)
    {
        $this->pdo = $pdo;
        $this->data = $data;
    }

    public function seed(): void
    {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Seed categories
            $this->seedCategories();

            // Seed currencies
            $this->seedCurrencies();

            // Seed products and related data
            $this->seedProducts();

            // Commit transaction
            $this->pdo->commit();
            echo "Data seeding completed successfully.\n";
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            echo "Error seeding data: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function seedCategories(): void
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");

        foreach ($this->data['data']['categories'] as $category) {
            $stmt->execute([$category['name']]);
        }
        echo "Categories seeded successfully.\n";
    }

    private function seedCurrencies(): void
    {
        // Get unique currencies from products
        $currencies = [];
        foreach ($this->data['data']['products'] as $product) {
            foreach ($product['prices'] as $price) {
                $currency = $price['currency'];
                $currencies[$currency['label']] = $currency;
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO currencies (label, symbol) 
            VALUES (?, ?)
        ");

        foreach ($currencies as $currency) {
            $stmt->execute([$currency['label'], $currency['symbol']]);
        }
        echo "Currencies seeded successfully.\n";
    }

    private function seedProducts(): void
    {
        // Prepare statements
        $productStmt = $this->pdo->prepare("
            INSERT INTO products (id, name, brand, description, category, inStock) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $galleryStmt = $this->pdo->prepare("
            INSERT INTO product_gallery (product_id, image_url, position) 
            VALUES (?, ?, ?)
        ");

        $attributeSetStmt = $this->pdo->prepare("
            INSERT IGNORE INTO attribute_sets (id, name, type) 
            VALUES (?, ?, ?)
        ");

        $attributeItemStmt = $this->pdo->prepare("
            INSERT IGNORE INTO attribute_items (id, attribute_set_id, display_value, value) 
            VALUES (?, ?, ?, ?)
        ");

        $productAttributeStmt = $this->pdo->prepare("
            INSERT INTO product_attributes (product_id, attribute_set_id) 
            VALUES (?, ?)
        ");

        $productAttributeItemStmt = $this->pdo->prepare("
            INSERT INTO product_attribute_items (product_id, attribute_set_id, attribute_item_id)
            VALUES (?, ?, ?)
        ");

        $priceStmt = $this->pdo->prepare("
            INSERT INTO prices (product_id, currency_id, amount) 
            VALUES (?, (SELECT id FROM currencies WHERE label = ?), ?)
        ");

        foreach ($this->data['data']['products'] as $product) {
            // Convert boolean to integer for MySQL
            $inStock = $product['inStock'] ? 1 : 0;

            // Insert product
            $productStmt->execute([
                $product['id'],
                $product['name'],
                $product['brand'],
                $product['description'],
                $product['category'],
                $inStock
            ]);

            // Insert gallery images
            foreach ($product['gallery'] as $position => $imageUrl) {
                $galleryStmt->execute([
                    $product['id'],
                    $imageUrl,
                    $position
                ]);
            }

            // Insert attributes
            if (isset($product['attributes'])) {
                foreach ($product['attributes'] as $attribute) {
                    // Insert attribute set
                    $attributeSetStmt->execute([
                        $attribute['id'],
                        $attribute['name'],
                        $attribute['type']
                    ]);

                    // Insert attribute items
                    foreach ($attribute['items'] as $item) {
                        $attributeItemStmt->execute([
                            $item['id'],
                            $attribute['id'],
                            $item['displayValue'],
                            $item['value']
                        ]);
                    }

                    // Link product to attribute
                    $productAttributeStmt->execute([
                        $product['id'],
                        $attribute['id']
                    ]);

                    // Link product to specific attribute items
                    foreach ($attribute['items'] as $item) {
                        $productAttributeItemStmt->execute([
                            $product['id'],
                            $attribute['id'],
                            $item['id']
                        ]);
                    }
                }
            }

            // Insert prices
            foreach ($product['prices'] as $price) {
                $priceStmt->execute([
                    $product['id'],
                    $price['currency']['label'],
                    $price['amount']
                ]);
            }
        }
        echo "Products and related data seeded successfully.\n";
    }
}
