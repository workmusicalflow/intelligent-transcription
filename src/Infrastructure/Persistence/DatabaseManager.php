<?php

namespace Infrastructure\Persistence;

use Infrastructure\Persistence\SQLiteConnection;
use PDO;
use Exception;

/**
 * Gestionnaire de base de données pour Infrastructure
 */
class DatabaseManager
{
    private SQLiteConnection $connection;
    private string $migrationsPath;
    private array $migrationHistory = [];
    
    public function __construct(
        SQLiteConnection $connection,
        string $migrationsPath = null
    ) {
        $this->connection = $connection;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/migrations';
        
        $this->initializeMigrationTable();
        $this->loadMigrationHistory();
    }
    
    public function migrate(): array
    {
        $migrations = $this->findPendingMigrations();
        $results = [];
        
        foreach ($migrations as $migration) {
            try {
                $this->connection->beginTransaction();
                
                $result = $this->executeMigration($migration);
                $this->recordMigration($migration);
                
                $this->connection->commit();
                
                $results[] = [
                    'migration' => $migration,
                    'status' => 'success',
                    'result' => $result
                ];
                
            } catch (Exception $e) {
                $this->connection->rollback();
                
                $results[] = [
                    'migration' => $migration,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                
                // Arrêter en cas d'échec
                break;
            }
        }
        
        return $results;
    }
    
    public function rollback(string $migrationName = null): array
    {
        if ($migrationName) {
            return $this->rollbackSpecific($migrationName);
        }
        
        return $this->rollbackLast();
    }
    
    public function getMigrationStatus(): array
    {
        $allMigrations = $this->findAllMigrations();
        $status = [];
        
        foreach ($allMigrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'applied' => in_array($migration, $this->migrationHistory),
                'applied_at' => $this->getMigrationAppliedAt($migration)
            ];
        }
        
        return $status;
    }
    
    public function createMigration(string $name, string $description = ''): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.sql";
        $filepath = $this->migrationsPath . '/' . $filename;
        
        $template = $this->getMigrationTemplate($name, $description);
        
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        file_put_contents($filepath, $template);
        
        return $filename;
    }
    
    public function optimizeDatabase(): array
    {
        $pdo = $this->connection->getConnection();
        $results = [];
        
        try {
            // Analyser toutes les tables
            $tables = $this->getAllTables();
            foreach ($tables as $table) {
                $pdo->exec("ANALYZE {$table}");
                $results['analyzed'][] = $table;
            }
            
            // Optimiser la base de données
            $pdo->exec("VACUUM");
            $results['vacuum'] = 'completed';
            
            // Mettre à jour les statistiques
            $pdo->exec("PRAGMA optimize");
            $results['optimize'] = 'completed';
            
            $results['status'] = 'success';
            
        } catch (Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    public function getSchemaInfo(): array
    {
        $pdo = $this->connection->getConnection();
        $info = [];
        
        // Tables
        $tables = $this->getAllTables();
        $info['tables'] = [];
        
        foreach ($tables as $table) {
            $tableInfo = [
                'name' => $table,
                'columns' => $this->getTableColumns($table),
                'indexes' => $this->getTableIndexes($table),
                'row_count' => $this->getTableRowCount($table)
            ];
            
            $info['tables'][$table] = $tableInfo;
        }
        
        // Informations générales
        $info['database'] = [
            'size_bytes' => $this->getDatabaseSize(),
            'page_size' => $this->getPageSize(),
            'total_pages' => $this->getTotalPages(),
            'foreign_keys' => $this->getForeignKeysStatus()
        ];
        
        return $info;
    }
    
    private function initializeMigrationTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS schema_migrations (
                migration TEXT PRIMARY KEY,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->connection->getConnection()->exec($sql);
    }
    
    private function loadMigrationHistory(): void
    {
        $stmt = $this->connection->getConnection()->query("SELECT migration FROM schema_migrations ORDER BY applied_at");
        $this->migrationHistory = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
    
    private function findAllMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = glob($this->migrationsPath . '/*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.sql');
        }
        
        sort($migrations);
        return $migrations;
    }
    
    private function findPendingMigrations(): array
    {
        $allMigrations = $this->findAllMigrations();
        
        return array_diff($allMigrations, $this->migrationHistory);
    }
    
    private function executeMigration(string $migration): array
    {
        $filepath = $this->migrationsPath . '/' . $migration . '.sql';
        
        if (!file_exists($filepath)) {
            throw new Exception("Migration file not found: {$filepath}");
        }
        
        $sql = file_get_contents($filepath);
        $statements = $this->parseSqlStatements($sql);
        
        $results = [];
        foreach ($statements as $statement) {
            if (trim($statement)) {
                $this->connection->getConnection()->exec($statement);
                $results[] = "Executed: " . substr(trim($statement), 0, 50) . "...";
            }
        }
        
        return $results;
    }
    
    private function recordMigration(string $migration): void
    {
        $stmt = $this->connection->getConnection()->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
        
        $this->migrationHistory[] = $migration;
    }
    
    private function rollbackLast(): array
    {
        if (empty($this->migrationHistory)) {
            return ['status' => 'no_migrations_to_rollback'];
        }
        
        $lastMigration = end($this->migrationHistory);
        return $this->rollbackSpecific($lastMigration);
    }
    
    private function rollbackSpecific(string $migrationName): array
    {
        // Chercher un fichier de rollback
        $rollbackFile = $this->migrationsPath . '/' . $migrationName . '_rollback.sql';
        
        if (!file_exists($rollbackFile)) {
            return [
                'status' => 'failed',
                'error' => "No rollback file found for {$migrationName}"
            ];
        }
        
        try {
            $this->connection->beginTransaction();
            
            $sql = file_get_contents($rollbackFile);
            $statements = $this->parseSqlStatements($sql);
            
            foreach ($statements as $statement) {
                if (trim($statement)) {
                    $this->connection->getConnection()->exec($statement);
                }
            }
            
            // Supprimer de l'historique
            $stmt = $this->connection->getConnection()->prepare("DELETE FROM schema_migrations WHERE migration = ?");
            $stmt->execute([$migrationName]);
            
            $this->connection->commit();
            
            // Recharger l'historique
            $this->loadMigrationHistory();
            
            return ['status' => 'success', 'migration' => $migrationName];
            
        } catch (Exception $e) {
            $this->connection->rollback();
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
    
    private function parseSqlStatements(string $sql): array
    {
        // Diviser le SQL en déclarations individuelles
        return array_filter(
            explode(';', $sql),
            fn($stmt) => trim($stmt) !== ''
        );
    }
    
    private function getMigrationTemplate(string $name, string $description): string
    {
        return "-- Migration: {$name}
-- Description: {$description}
-- Created: " . date('Y-m-d H:i:s') . "

-- UP Migration
-- Add your migration SQL here

-- Example:
-- CREATE TABLE example (
--     id INTEGER PRIMARY KEY,
--     name TEXT NOT NULL
-- );

-- CREATE INDEX idx_example_name ON example(name);
";
    }
    
    private function getMigrationAppliedAt(string $migration): ?string
    {
        $stmt = $this->connection->getConnection()->prepare("SELECT applied_at FROM schema_migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        
        return $stmt->fetchColumn() ?: null;
    }
    
    private function getAllTables(): array
    {
        $stmt = $this->connection->getConnection()->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
    
    private function getTableColumns(string $table): array
    {
        $stmt = $this->connection->getConnection()->query("PRAGMA table_info({$table})");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    private function getTableIndexes(string $table): array
    {
        $stmt = $this->connection->getConnection()->query("PRAGMA index_list({$table})");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    private function getTableRowCount(string $table): int
    {
        $stmt = $this->connection->getConnection()->query("SELECT COUNT(*) FROM {$table}");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }
    
    private function getDatabaseSize(): int
    {
        $stmt = $this->connection->getConnection()->query("PRAGMA page_count");
        $pageCount = $stmt ? (int) $stmt->fetchColumn() : 0;
        
        $stmt = $this->connection->getConnection()->query("PRAGMA page_size");
        $pageSize = $stmt ? (int) $stmt->fetchColumn() : 0;
        
        return $pageCount * $pageSize;
    }
    
    private function getPageSize(): int
    {
        $stmt = $this->connection->getConnection()->query("PRAGMA page_size");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }
    
    private function getTotalPages(): int
    {
        $stmt = $this->connection->getConnection()->query("PRAGMA page_count");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }
    
    private function getForeignKeysStatus(): bool
    {
        $stmt = $this->connection->getConnection()->query("PRAGMA foreign_keys");
        return $stmt ? (bool) $stmt->fetchColumn() : false;
    }
    
    public function getStats(): array
    {
        return [
            'migrations_applied' => count($this->migrationHistory),
            'pending_migrations' => count($this->findPendingMigrations()),
            'database_size_mb' => round($this->getDatabaseSize() / 1024 / 1024, 2),
            'total_tables' => count($this->getAllTables()),
            'foreign_keys_enabled' => $this->getForeignKeysStatus()
        ];
    }
}