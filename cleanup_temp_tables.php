<?php

/**
 * Script to clean up leftover temporary tables from aborted installation
 */

require_once __DIR__ . '/src/bootstrap.php';

use Database\DatabaseManager;

// Connect to the database
try {
    $db = DatabaseManager::getConnection();
    echo "Connected to the database.\n";
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n");
}

// Check for temporary tables
$tempTables = [
    'transcriptions_temp',
    'chat_conversations_temp',
    'paraphrases_temp',
    'users_temp',
    'user_sessions_temp',
    'user_permissions_temp',
    'password_reset_tokens_temp'
];

// Get list of all tables
$result = DatabaseManager::query("SELECT name FROM sqlite_master WHERE type='table'");
$allTables = $result->fetchAll(PDO::FETCH_COLUMN);

// Find temp tables that exist
$existingTempTables = array_intersect($tempTables, $allTables);

if (empty($existingTempTables)) {
    echo "No temporary tables found. Nothing to clean up.\n";
    exit(0);
}

echo "Found " . count($existingTempTables) . " temporary tables: " . implode(', ', $existingTempTables) . "\n";
echo "Dropping temporary tables...\n";

try {
    // Start transaction
    DatabaseManager::beginTransaction();
    
    // Drop each temporary table
    foreach ($existingTempTables as $table) {
        echo "Dropping $table...\n";
        DatabaseManager::query("DROP TABLE IF EXISTS $table");
    }
    
    // Commit transaction
    DatabaseManager::commit();
    echo "All temporary tables have been dropped successfully.\n";
    echo "You can now run install_auth.php again.\n";
} catch (Exception $e) {
    // Rollback on error
    DatabaseManager::rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
}