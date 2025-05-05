<?php

namespace Database;

/**
 * Database Manager class for SQLite database connection and operations
 */
class DatabaseManager
{
    /**
     * @var \PDO The PDO connection instance
     */
    private static $instance = null;
    
    /**
     * @var string Database file path
     */
    private static $dbPath;
    
    /**
     * Get a PDO database connection
     * 
     * @return \PDO The PDO connection instance
     * @throws \Exception If the database connection fails
     */
    public static function getConnection()
    {
        if (self::$instance === null) {
            try {
                // Set the database file path
                self::$dbPath = defined('DB_PATH') ? DB_PATH : dirname(dirname(__DIR__)) . '/database/transcription.db';
                
                // Create the directory if it doesn't exist
                $dbDir = dirname(self::$dbPath);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0777, true);
                }
                
                // Initialize the PDO connection
                self::$instance = new \PDO('sqlite:' . self::$dbPath);
                
                // Set error mode to exception
                self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                
                // Enable foreign keys
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                
                // Return the database connection
                return self::$instance;
            } catch (\PDOException $e) {
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Execute a SQL query
     * 
     * @param string $sql SQL query to execute
     * @param array $params Optional parameters for prepared statement
     * @return \PDOStatement The PDO statement
     * @throws \Exception If the query execution fails
     */
    public static function query($sql, $params = [])
    {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception('Query execution failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string The last inserted ID
     */
    public static function lastInsertId()
    {
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success
     */
    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success
     */
    public static function commit()
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success
     */
    public static function rollback()
    {
        return self::getConnection()->rollBack();
    }
    
    /**
     * Get the database path
     * 
     * @return string The database file path
     */
    public static function getDatabasePath()
    {
        return self::$dbPath;
    }
    
    /**
     * Check if a table exists in the database
     * 
     * @param string $tableName The table name to check
     * @return bool True if the table exists
     */
    public static function tableExists($tableName)
    {
        $result = self::query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
            [$tableName]
        );
        
        return $result->rowCount() > 0;
    }
    
    /**
     * Execute multiple SQL statements
     * 
     * @param string $sql Multiple SQL statements separated by semicolons
     * @return bool True on success
     * @throws \Exception If execution fails
     */
    public static function executeBatch($sql)
    {
        try {
            $db = self::getConnection();
            $result = $db->exec($sql);
            return $result !== false;
        } catch (\PDOException $e) {
            throw new \Exception('Batch execution failed: ' . $e->getMessage());
        }
    }
}