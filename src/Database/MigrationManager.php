<?php

namespace Database;

/**
 * Migration Manager for database schema creation and updates
 */
class MigrationManager
{
    /**
     * Run database migrations
     * 
     * @return array Result of the migration operation
     */
    public static function migrate()
    {
        try {
            // Check if the connection can be established
            $db = DatabaseManager::getConnection();
            
            // Read the schema SQL file
            $schemaPath = __DIR__ . '/schema.sql';
            if (!file_exists($schemaPath)) {
                throw new \Exception("Schema file not found at: {$schemaPath}");
            }
            
            $schemaSql = file_get_contents($schemaPath);
            if (!$schemaSql) {
                throw new \Exception("Failed to read schema file");
            }
            
            // Execute the schema SQL
            $result = DatabaseManager::executeBatch($schemaSql);
            
            // Read and execute the cache schema SQL if it exists
            $cacheSchemaPath = __DIR__ . '/cache_schema.sql';
            if (file_exists($cacheSchemaPath)) {
                $cacheSchemaSql = file_get_contents($cacheSchemaPath);
                if ($cacheSchemaSql) {
                    // Execute the cache schema SQL
                    try {
                        $result = DatabaseManager::executeBatch($cacheSchemaSql);
                        echo "Cache schema applied successfully\n";
                    } catch (\Exception $e) {
                        // Don't fail the whole migration if cache schema fails
                        // It might have already been applied
                        echo "Note: Cache schema application produced a notice (this is often normal): " . $e->getMessage() . "\n";
                    }
                }
            }
            
            // Check for required tables using a direct query instead of tableExists method
            $requiredTables = [
                'transcriptions',
                'paraphrases',
                'chat_conversations',
                'chat_messages',
                'chat_exports'
            ];
            
            // Check for cache tables as well, but don't make them required
            $cacheTables = [
                'prompt_templates',
                'cache_analytics'
            ];
            
            $sql = "SELECT name FROM sqlite_master WHERE type='table'";
            $stmt = $db->query($sql);
            $allTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $existingRequiredTables = array_intersect($requiredTables, $allTables);
            $existingCacheTables = array_intersect($cacheTables, $allTables);
            
            $missingTables = array_diff($requiredTables, $existingRequiredTables);
            
            if (!empty($missingTables)) {
                return [
                    'success' => false,
                    'message' => 'Migration completed but some tables are missing',
                    'missing_tables' => $missingTables,
                    'existing_tables' => $existingRequiredTables,
                    'cache_tables' => $existingCacheTables
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Database migration completed successfully',
                'db_path' => DatabaseManager::getDatabasePath(),
                'tables' => $existingRequiredTables,
                'cache_tables' => $existingCacheTables
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Database migration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Run the migrate command from CLI
     */
    public static function runFromCLI()
    {
        // Add base directory to include path
        $baseDir = dirname(dirname(__DIR__));
        define('BASE_DIR', $baseDir);
        
        // Include necessary files
        require_once $baseDir . '/config.php';
        require_once __DIR__ . '/DatabaseManager.php';
        
        // Run migration
        $result = self::migrate();
        
        // Output result
        if ($result['success']) {
            echo "\033[32m" . $result['message'] . "\033[0m\n";
            echo "Database path: " . $result['db_path'] . "\n";
        } else {
            echo "\033[31m" . $result['message'] . "\033[0m\n";
            if (isset($result['missing_tables'])) {
                echo "Missing tables: " . implode(', ', $result['missing_tables']) . "\n";
            }
        }
    }
}

// Run migration if script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    MigrationManager::runFromCLI();
}