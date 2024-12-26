<?php

namespace App\Config;

/**
 * Environment Configuration Handler
 * 
 * This class provides methods to access environment variables
 * with support for default values and validation.
 */
class Environment
{
    /**
     * @var array Cached environment variables
     */
    private static array $variables = [];

    /**
     * Load environment variables from .env file
     */
    public static function load(): void
    {
        if (empty(self::$variables)) {
            $envFile = dirname(__DIR__, 2) . '/.env';

            if (!file_exists($envFile)) {
                throw new \RuntimeException('.env file not found');
            }

            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }

                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Remove quotes if they exist
                if (preg_match('/^(["\']).*\1$/', $value)) {
                    $value = substr($value, 1, -1);
                }

                self::$variables[$name] = $value;
            }
        }
    }

    /**
     * Get an environment variable
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     * @throws \RuntimeException If required variable is not found
     */
    public static function get(string $key, $default = null)
    {
        self::load();

        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }

        if ($default === null) {
            throw new \RuntimeException("Environment variable {$key} not found");
        }

        return $default;
    }

    /**
     * Check if an environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has(string $key): bool
    {
        self::load();
        return isset(self::$variables[$key]);
    }
}
