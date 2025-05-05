-- Authentication Schema for Intelligent Transcription Application

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Create basic chat and transcription tables if they don't exist
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

CREATE TABLE IF NOT EXISTS chat_conversations (
    id TEXT PRIMARY KEY,
    transcription_id TEXT,
    title TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id TEXT NOT NULL,
    role TEXT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS paraphrases (
    id TEXT PRIMARY KEY,
    transcription_id TEXT NOT NULL,
    original_text TEXT NOT NULL,
    paraphrased_text TEXT NOT NULL,
    language TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
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

-- Create first admin user (password is "admin123" - will be changed on first login)
INSERT OR IGNORE INTO users (username, email, password_hash, first_name, last_name, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$LuU5vZKIUpCr2TCrW2d0E.chLyGXEhP/GFshWGfwMsRW.8Ri9UDWe', 'Admin', 'User', 1);

-- User sessions table
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

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User permissions table
CREATE TABLE IF NOT EXISTS user_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    permission TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, permission)
);

-- Default permissions for admin user
INSERT OR IGNORE INTO user_permissions (user_id, permission)
SELECT id, 'admin.access' FROM users WHERE username = 'admin';

INSERT OR IGNORE INTO user_permissions (user_id, permission)
SELECT id, 'users.manage' FROM users WHERE username = 'admin';

INSERT OR IGNORE INTO user_permissions (user_id, permission)
SELECT id, 'transcriptions.all' FROM users WHERE username = 'admin';

-- Check if user_id columns exist and add them if not

-- For chat_conversations table
CREATE TABLE IF NOT EXISTS chat_conversations_temp (
    id TEXT PRIMARY KEY, 
    transcription_id TEXT, 
    title TEXT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL
);

INSERT OR IGNORE INTO chat_conversations_temp 
SELECT id, transcription_id, title, created_at, updated_at, NULL as user_id 
FROM chat_conversations;

DROP TABLE IF EXISTS chat_conversations;
ALTER TABLE chat_conversations_temp RENAME TO chat_conversations;

-- For transcriptions table
CREATE TABLE IF NOT EXISTS transcriptions_temp (
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
);

INSERT OR IGNORE INTO transcriptions_temp 
SELECT id, file_name, file_path, text, language, original_text, youtube_url, youtube_id, 
       created_at, file_size, duration, is_processed, preprocessed_path, NULL as user_id 
FROM transcriptions;

DROP TABLE IF EXISTS transcriptions;
ALTER TABLE transcriptions_temp RENAME TO transcriptions;

-- For paraphrases table
CREATE TABLE IF NOT EXISTS paraphrases_temp (
    id TEXT PRIMARY KEY,
    transcription_id TEXT NOT NULL,
    original_text TEXT NOT NULL,
    paraphrased_text TEXT NOT NULL,
    language TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE CASCADE
);

INSERT OR IGNORE INTO paraphrases_temp 
SELECT id, transcription_id, original_text, paraphrased_text, language, created_at, NULL as user_id 
FROM paraphrases;

DROP TABLE IF EXISTS paraphrases;
ALTER TABLE paraphrases_temp RENAME TO paraphrases;

-- Create indexes for authentication tables
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_user_permissions_user_id ON user_permissions(user_id);

-- Create indexes for user_id in existing tables
CREATE INDEX IF NOT EXISTS idx_transcriptions_user_id ON transcriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_user_id ON chat_conversations(user_id);
CREATE INDEX IF NOT EXISTS idx_paraphrases_user_id ON paraphrases(user_id);

-- Create trigger to update the updated_at timestamp for users
CREATE TRIGGER IF NOT EXISTS update_user_timestamp
AFTER UPDATE ON users
BEGIN
    UPDATE users 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.id;
END;

-- Create trigger to update the updated_at timestamp for chat conversations
CREATE TRIGGER IF NOT EXISTS update_chat_conversation_timestamp
AFTER INSERT ON chat_messages
BEGIN
    UPDATE chat_conversations 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.conversation_id;
END;