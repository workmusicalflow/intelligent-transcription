<?php

namespace Infrastructure\Persistence;

use PDO;
use PDOException;
use Exception;

/**
 * Gestionnaire de connexion SQLite optimisé
 */
class SQLiteConnection
{
    private ?PDO $connection = null;
    private string $databasePath;
    private array $options;
    
    public function __construct(string $databasePath, array $options = [])
    {
        $this->databasePath = $databasePath;
        $this->options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $options);
    }
    
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    private function connect(): void
    {
        try {
            $dsn = "sqlite:" . $this->databasePath;
            $this->connection = new PDO($dsn, null, null, $this->options);
            
            // Optimisations SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON');
            $this->connection->exec('PRAGMA journal_mode = WAL');
            $this->connection->exec('PRAGMA synchronous = NORMAL');
            $this->connection->exec('PRAGMA cache_size = 10000');
            $this->connection->exec('PRAGMA temp_store = MEMORY');
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    public function rollback(): bool
    {
        return $this->getConnection()->rollback();
    }
    
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }
    
    public function disconnect(): void
    {
        $this->connection = null;
    }
    
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }
    
    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }
    
    public function getStats(): array
    {
        if (!$this->isConnected()) {
            return ['connected' => false];
        }
        
        try {
            $stmt = $this->connection->query("PRAGMA database_list");
            $databases = $stmt->fetchAll();
            
            $stmt = $this->connection->query("PRAGMA compile_options");
            $options = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'connected' => true,
                'database_path' => $this->databasePath,
                'databases' => $databases,
                'compile_options' => $options,
                'sqlite_version' => $this->connection->query('SELECT sqlite_version()')->fetchColumn()
            ];
        } catch (Exception $e) {
            return [
                'connected' => true,
                'error' => $e->getMessage()
            ];
        }
    }
}