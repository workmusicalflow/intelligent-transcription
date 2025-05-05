<?php

/**
 * Script to update the database schema with authentication tables
 */

require_once __DIR__ . '/src/bootstrap.php';

use Database\DatabaseManager;

// Check if the database exists
try {
    // Create database directory if it doesn't exist
    $dbDir = __DIR__ . '/database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
        echo "Created database directory at: $dbDir\n";
    }
    
    // Make sure DB_PATH is defined
    if (!defined('DB_PATH')) {
        define('DB_PATH', $dbDir . '/transcription.db');
        echo "Defined DB_PATH as: " . DB_PATH . "\n";
    }
    
    // Get the database file path
    $dbPath = DB_PATH;
    
    echo "Using database at: $dbPath\n";
    
    // Connect to the database
    $db = DatabaseManager::getConnection();
    
    // Initialize the database with a test table if it doesn't exist
    $initSchema = "
    CREATE TABLE IF NOT EXISTS db_version (
        version INTEGER PRIMARY KEY,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    INSERT OR IGNORE INTO db_version VALUES (1, CURRENT_TIMESTAMP);
    ";
    
    echo "Initializing database...\n";
    DatabaseManager::executeBatch($initSchema);
    
    // Load the authentication schema
    $authSchema = file_get_contents(__DIR__ . '/src/Database/auth_schema.sql');
    
    if (!$authSchema) {
        die("Failed to load authentication schema.\n");
    }
    
    echo "Loaded authentication schema.\n";
    
    // Execute the authentication schema
    echo "Updating database schema...\n";
    DatabaseManager::executeBatch($authSchema);
    
    echo "Authentication schema applied successfully!\n";
    
    // Check if tables were created
    $tables = ['users', 'user_sessions', 'password_reset_tokens', 'user_permissions'];
    $allTablesExist = true;
    
    foreach ($tables as $table) {
        if (!DatabaseManager::tableExists($table)) {
            echo "Error: Table '$table' was not created.\n";
            $allTablesExist = false;
        }
    }
    
    if ($allTablesExist) {
        echo "All authentication tables created successfully.\n";
        echo "Default admin user created with:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "\nIMPORTANT: Change the default admin password after first login!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}