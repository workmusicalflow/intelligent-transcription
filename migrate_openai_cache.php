<?php
/**
 * Migration script for OpenAI cache metrics
 * Run this script to create the necessary tables and views
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/bootstrap.php';

use Database\DatabaseManager;

echo "OpenAI Cache Metrics Migration\n";
echo "==============================\n\n";

try {
    // Check if we're using the database
    if (!defined('USE_DATABASE') || !USE_DATABASE) {
        echo "Error: Database is not enabled in config.php\n";
        exit(1);
    }
    
    // Read the schema file
    $schemaFile = __DIR__ . '/src/Database/openai_cache_schema.sql';
    if (!file_exists($schemaFile)) {
        echo "Error: Schema file not found at $schemaFile\n";
        exit(1);
    }
    
    $schema = file_get_contents($schemaFile);
    
    echo "Loading schema from: $schemaFile\n";
    
    // Split schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        
        try {
            // Execute the statement
            DatabaseManager::query($statement);
            
            // Extract what was created for logging
            if (preg_match('/CREATE\s+(TABLE|INDEX|VIEW|TRIGGER)\s+(?:IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $statement, $matches)) {
                $type = strtolower($matches[1]);
                $name = $matches[2];
                echo "✓ Created $type: $name\n";
            } else {
                echo "✓ Executed statement\n";
            }
            
            $successCount++;
        } catch (Exception $e) {
            $errorCount++;
            echo "✗ Error: " . $e->getMessage() . "\n";
            
            // If it's a "already exists" error, it's not critical
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "  (This is OK - object already exists)\n";
                $errorCount--; // Don't count as error
                $successCount++;
            }
        }
    }
    
    echo "\n";
    echo "Migration Summary:\n";
    echo "-----------------\n";
    echo "Successful operations: $successCount\n";
    echo "Failed operations: $errorCount\n";
    
    // Test the new tables
    echo "\nTesting new tables...\n";
    
    try {
        // Check if openai_cache_metrics table exists
        $sql = "SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name='openai_cache_metrics'";
        $stmt = DatabaseManager::query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "✓ Table 'openai_cache_metrics' exists\n";
            
            // Get column info
            $sql = "PRAGMA table_info(openai_cache_metrics)";
            $stmt = DatabaseManager::query($sql);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "  Columns: " . implode(', ', array_column($columns, 'name')) . "\n";
        } else {
            echo "✗ Table 'openai_cache_metrics' not found\n";
        }
        
        // Check views
        $views = ['openai_cache_hourly_stats', 'openai_cache_model_stats'];
        foreach ($views as $view) {
            $sql = "SELECT COUNT(*) as count FROM sqlite_master WHERE type='view' AND name='$view'";
            $stmt = DatabaseManager::query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo "✓ View '$view' exists\n";
            } else {
                echo "✗ View '$view' not found\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Error testing tables: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Migration completed!\n";
    echo "\nYou can now start tracking OpenAI prompt cache metrics.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}