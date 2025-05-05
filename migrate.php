<?php

/**
 * Database Migration Script
 * 
 * Run this script to create or update the database schema
 * Usage: php migrate.php
 */

// Include required files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Database/DatabaseManager.php';
require_once __DIR__ . '/src/Database/MigrationManager.php';

// Use the Database namespace
use Database\MigrationManager;

// Add database path to config if not defined
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/database/transcription.db');
}

echo "Starting database migration...\n";
echo "Database path: " . DB_PATH . "\n";

// Run the migration
$result = MigrationManager::migrate();

// Output the result
if ($result['success']) {
    echo "\033[32mSuccess: " . $result['message'] . "\033[0m\n";
} else {
    echo "\033[31mError: " . $result['message'] . "\033[0m\n";
    if (isset($result['missing_tables'])) {
        echo "Missing tables: " . implode(', ', $result['missing_tables']) . "\n";
    }
}