<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Database\Migration\Migration;
use App\Database\Seeder\DatabaseSeeder;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    // Create PDO instance
    $config = Database::getInstance()->getConfig();
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );

    // Create database if not exists
    $dbName = $config['dbname'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci");

    echo "Database '$dbName' created successfully.\n";

    // Connect to the new database
    $pdo->exec("USE `$dbName`");

    // Run migrations
    $migration = new Migration($pdo);
    $migration->createTables();

    // Load JSON data
    $jsonFile = __DIR__ . '/../data.json';
    if (!file_exists($jsonFile)) {
        throw new \RuntimeException("Data file not found: $jsonFile");
    }

    $jsonData = json_decode(file_get_contents($jsonFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException("Invalid JSON data: " . json_last_error_msg());
    }

    // Run seeder
    $seeder = new DatabaseSeeder($pdo, $jsonData);
    $seeder->seed();

    echo "Database setup completed successfully.\n";
} catch (\PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
