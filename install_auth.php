<?php

/**
 * Script to install authentication system
 */

require_once __DIR__ . '/src/bootstrap.php';

use Database\DatabaseManager;

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
try {
    $db = DatabaseManager::getConnection();
    echo "Connected to the database.\n";
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage() . "\n");
}

// Create tables one by one
try {
    // Enable foreign keys
    DatabaseManager::query("PRAGMA foreign_keys = ON;");
    
    // Create base tables
    echo "Creating base tables...\n";
    
    // Create transcriptions table
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS transcriptions (
            id TEXT PRIMARY KEY,
            file_name TEXT NOT NULL,
            file_path TEXT,
            text TEXT NOT NULL,
            language TEXT NOT NULL,
            original_text TEXT,
            youtube_url TEXT,
            youtube_id TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            file_size INTEGER,
            duration INTEGER,
            is_processed BOOLEAN DEFAULT 1,
            preprocessed_path TEXT
        );
    ");
    
    // Create chat_conversations table
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS chat_conversations (
            id TEXT PRIMARY KEY,
            transcription_id TEXT,
            title TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    
    // Create chat_messages table
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conversation_id TEXT NOT NULL,
            role TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    
    // Create paraphrases table
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS paraphrases (
            id TEXT PRIMARY KEY,
            transcription_id TEXT NOT NULL,
            original_text TEXT NOT NULL,
            paraphrased_text TEXT NOT NULL,
            language TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    
    // Create users table
    echo "Creating users table...\n";
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP,
            is_active BOOLEAN DEFAULT 1,
            is_admin BOOLEAN DEFAULT 0
        );
    ");
    
    // Create admin user
    echo "Creating admin user...\n";
    DatabaseManager::query("
        INSERT OR IGNORE INTO users (username, email, password_hash, first_name, last_name, is_admin) 
        VALUES ('admin', 'admin@example.com', '\$2y\$10\$LuU5vZKIUpCr2TCrW2d0E.chLyGXEhP/GFshWGfwMsRW.8Ri9UDWe', 'Admin', 'User', 1);
    ");
    
    // Create user_sessions table
    echo "Creating sessions table...\n";
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id TEXT PRIMARY KEY,
            user_id INTEGER NOT NULL,
            ip_address TEXT NOT NULL,
            user_agent TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
    
    // Create password_reset_tokens table
    echo "Creating password reset table...\n";
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id TEXT PRIMARY KEY,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_used BOOLEAN DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
    
    // Create user_permissions table
    echo "Creating permissions table...\n";
    DatabaseManager::query("
        CREATE TABLE IF NOT EXISTS user_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            permission TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, permission)
        );
    ");
    
    // Add default permissions for admin
    echo "Adding default permissions...\n";
    DatabaseManager::query("
        INSERT OR IGNORE INTO user_permissions (user_id, permission)
        SELECT id, 'admin.access' FROM users WHERE username = 'admin';
    ");
    
    DatabaseManager::query("
        INSERT OR IGNORE INTO user_permissions (user_id, permission)
        SELECT id, 'users.manage' FROM users WHERE username = 'admin';
    ");
    
    DatabaseManager::query("
        INSERT OR IGNORE INTO user_permissions (user_id, permission)
        SELECT id, 'transcriptions.all' FROM users WHERE username = 'admin';
    ");
    
    // Update existing tables to add user_id
    echo "Updating existing tables...\n";
    
    // Check if user_id already exists in transcriptions
    $stmt = DatabaseManager::query("PRAGMA table_info(transcriptions);");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasUserIdColumn = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'user_id') {
            $hasUserIdColumn = true;
            break;
        }
    }
    
    if (!$hasUserIdColumn) {
        echo "Adding user_id to transcriptions table...\n";
        // SQLite ALTER TABLE has limitations, so we use a workaround
        DatabaseManager::query("
            CREATE TABLE transcriptions_temp (
                id TEXT PRIMARY KEY,
                file_name TEXT NOT NULL,
                file_path TEXT,
                text TEXT NOT NULL,
                language TEXT NOT NULL,
                original_text TEXT,
                youtube_url TEXT,
                youtube_id TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                file_size INTEGER,
                duration INTEGER,
                is_processed BOOLEAN DEFAULT 1,
                preprocessed_path TEXT,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        DatabaseManager::query("
            INSERT INTO transcriptions_temp
            SELECT id, file_name, file_path, text, language, original_text, youtube_url, youtube_id, 
                created_at, file_size, duration, is_processed, preprocessed_path, NULL 
            FROM transcriptions
        ");
        
        DatabaseManager::query("DROP TABLE transcriptions");
        DatabaseManager::query("ALTER TABLE transcriptions_temp RENAME TO transcriptions");
    }
    
    // Check if chat_conversations table exists
    if (DatabaseManager::tableExists('chat_conversations')) {
        // Check if user_id already exists in chat_conversations
        $stmt = DatabaseManager::query("PRAGMA table_info(chat_conversations);");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasUserIdColumn = false;
        
        foreach ($columns as $column) {
            if ($column['name'] === 'user_id') {
                $hasUserIdColumn = true;
                break;
            }
        }
        
        if (!$hasUserIdColumn) {
            echo "Adding user_id to chat_conversations table...\n";
            // SQLite ALTER TABLE has limitations, so we use a workaround
            DatabaseManager::query("
                CREATE TABLE chat_conversations_temp (
                    id TEXT PRIMARY KEY,
                    transcription_id TEXT,
                    title TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL
                )
            ");
            
            DatabaseManager::query("
                INSERT INTO chat_conversations_temp
                SELECT id, transcription_id, title, created_at, updated_at, NULL 
                FROM chat_conversations
            ");
            
            DatabaseManager::query("DROP TABLE chat_conversations");
            DatabaseManager::query("ALTER TABLE chat_conversations_temp RENAME TO chat_conversations");
        }
    } else {
        echo "Creating chat_conversations table...\n";
        DatabaseManager::query("
            CREATE TABLE IF NOT EXISTS chat_conversations (
                id TEXT PRIMARY KEY,
                transcription_id TEXT,
                title TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL
            )
        ");
    }
    
    // Check if paraphrases table exists
    if (DatabaseManager::tableExists('paraphrases')) {
        // Check if user_id already exists in paraphrases
        $stmt = DatabaseManager::query("PRAGMA table_info(paraphrases);");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasUserIdColumn = false;
        
        foreach ($columns as $column) {
            if ($column['name'] === 'user_id') {
                $hasUserIdColumn = true;
                break;
            }
        }
        
        if (!$hasUserIdColumn) {
            echo "Adding user_id to paraphrases table...\n";
            // SQLite ALTER TABLE has limitations, so we use a workaround
            DatabaseManager::query("
                CREATE TABLE paraphrases_temp (
                    id TEXT PRIMARY KEY,
                    transcription_id TEXT NOT NULL,
                    original_text TEXT NOT NULL,
                    paraphrased_text TEXT NOT NULL,
                    language TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE CASCADE
                )
            ");
            
            DatabaseManager::query("
                INSERT INTO paraphrases_temp
                SELECT id, transcription_id, original_text, paraphrased_text, language, created_at, NULL
                FROM paraphrases
            ");
            
            DatabaseManager::query("DROP TABLE paraphrases");
            DatabaseManager::query("ALTER TABLE paraphrases_temp RENAME TO paraphrases");
        }
    } else {
        echo "Creating paraphrases table...\n";
        DatabaseManager::query("
            CREATE TABLE IF NOT EXISTS paraphrases (
                id TEXT PRIMARY KEY,
                transcription_id TEXT NOT NULL,
                original_text TEXT NOT NULL,
                paraphrased_text TEXT NOT NULL,
                language TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE CASCADE
            )
        ");
    }
    
    // Create indexes
    echo "Creating indexes...\n";
    
    try {
        // Authentication indexes
        DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);");
        DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);");
        DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id);");
        DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_user_permissions_user_id ON user_permissions(user_id);");
        
        // Table indexes - only create if the respective tables exist
        if (DatabaseManager::tableExists('transcriptions')) {
            DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_transcriptions_user_id ON transcriptions(user_id);");
        }
        
        if (DatabaseManager::tableExists('chat_conversations')) {
            DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_chat_conversations_user_id ON chat_conversations(user_id);");
        }
        
        if (DatabaseManager::tableExists('paraphrases')) {
            DatabaseManager::query("CREATE INDEX IF NOT EXISTS idx_paraphrases_user_id ON paraphrases(user_id);");
        }
    } catch (Exception $e) {
        echo "Notice: " . $e->getMessage() . " (continuing anyway)\n";
    }
    
    // Check if chat_conversations table exists
    $chatConversationsExists = DatabaseManager::tableExists('chat_conversations');
    
    // Create triggers
    echo "Creating triggers...\n";
    
    // Drop existing triggers if they exist
    DatabaseManager::query("DROP TRIGGER IF EXISTS update_user_timestamp;");
    
    if ($chatConversationsExists) {
        DatabaseManager::query("DROP TRIGGER IF EXISTS update_chat_conversation_timestamp;");
    }
    
    // User update trigger
    DatabaseManager::query("
        CREATE TRIGGER update_user_timestamp
        AFTER UPDATE ON users
        BEGIN
            UPDATE users 
            SET updated_at = CURRENT_TIMESTAMP 
            WHERE id = NEW.id;
        END;
    ");
    
    // Chat conversation update trigger - only create if the table exists
    if ($chatConversationsExists) {
        echo "Creating chat conversation trigger...\n";
        DatabaseManager::query("
            CREATE TRIGGER update_chat_conversation_timestamp
            AFTER INSERT ON chat_messages
            BEGIN
                UPDATE chat_conversations 
                SET updated_at = CURRENT_TIMESTAMP 
                WHERE id = NEW.conversation_id;
            END;
        ");
    } else {
        echo "Skipping chat conversation trigger (table does not exist)...\n";
    }
    
    echo "Authentication system installed successfully!\n";
    echo "Default admin user created with:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\nIMPORTANT: Change the default admin password after first login!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}