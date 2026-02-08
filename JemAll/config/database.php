<?php
/**
 * Database Configuration
 * Handles database connection using PDO with PostgreSQL
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'jemall_db');
define('DB_USER', 'postgres');
define('DB_PASS', 'bouksim');

/**
 * Get database connection
 * @return PDO Database connection object
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // Updated for PostgreSQL
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed (PostgreSQL): " . $e->getMessage());
        }
    }
    
    return $pdo;
}

