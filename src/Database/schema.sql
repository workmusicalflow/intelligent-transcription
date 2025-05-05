-- SQLite Database Schema for Intelligent Transcription Application

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Transcriptions table
CREATE TABLE IF NOT EXISTS transcriptions (
    id TEXT PRIMARY KEY, -- Unique transcription ID
    file_name TEXT NOT NULL, -- Original file name
    file_path TEXT, -- Path to file (could be NULL for YouTube videos)
    text TEXT NOT NULL, -- Transcribed text
    language TEXT NOT NULL, -- Language code or 'auto'
    original_text TEXT, -- Original text if translation was applied
    youtube_url TEXT, -- YouTube URL (NULL for direct uploads)
    youtube_id TEXT, -- YouTube video ID (NULL for direct uploads)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Creation timestamp
    file_size INTEGER, -- Size of the file in bytes
    duration INTEGER, -- Duration in seconds
    is_processed BOOLEAN DEFAULT 1, -- 1 if processing is complete
    preprocessed_path TEXT -- Path to preprocessed file
);

-- Paraphrases table
CREATE TABLE IF NOT EXISTS paraphrases (
    id TEXT PRIMARY KEY, -- Unique paraphrase ID
    transcription_id TEXT NOT NULL, -- Related transcription ID
    original_text TEXT NOT NULL, -- Original text
    paraphrased_text TEXT NOT NULL, -- Paraphrased text
    language TEXT NOT NULL, -- Language code
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Creation timestamp
    FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE CASCADE
);

-- Chat conversations table
CREATE TABLE IF NOT EXISTS chat_conversations (
    id TEXT PRIMARY KEY, -- Unique conversation ID
    transcription_id TEXT, -- Related transcription ID (can be NULL for general conversations)
    title TEXT, -- Conversation title
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Creation timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Last update timestamp
    FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE SET NULL
);

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- Message ID
    conversation_id TEXT NOT NULL, -- Related conversation ID
    role TEXT NOT NULL, -- Message role (user/assistant)
    content TEXT NOT NULL, -- Message content
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Creation timestamp
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);

-- Chat exports table
CREATE TABLE IF NOT EXISTS chat_exports (
    id TEXT PRIMARY KEY, -- Unique export ID
    conversation_id TEXT NOT NULL, -- Related conversation ID
    file_path TEXT NOT NULL, -- Path to exported file
    file_name TEXT NOT NULL, -- Exported file name
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Creation timestamp
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);

-- Create indexes for frequently queried columns
CREATE INDEX IF NOT EXISTS idx_transcriptions_created_at ON transcriptions(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_chat_messages_conversation_id ON chat_messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_paraphrases_transcription_id ON paraphrases(transcription_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_transcription_id ON chat_conversations(transcription_id);

-- Create trigger to update the updated_at timestamp for chat conversations
CREATE TRIGGER IF NOT EXISTS update_chat_conversation_timestamp
AFTER INSERT ON chat_messages
BEGIN
    UPDATE chat_conversations 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.conversation_id;
END;