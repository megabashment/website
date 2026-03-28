<?php
/**
 * DATABASE CONNECTION SINGLETON
 *
 * Provides a single PDO instance for the entire application.
 * Call getDB() to get the connection.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                self::$pdo = new PDO(
                    $dsn,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                if (DEBUG) {
                    die('Database connection failed: ' . $e->getMessage());
                } else {
                    die('Database connection failed.');
                }
            }
        }
        return self::$pdo;
    }
}

/**
 * Convenience function to get the database connection
 */
function getDB() {
    return Database::getConnection();
}
