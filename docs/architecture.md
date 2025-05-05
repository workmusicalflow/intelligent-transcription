# Backend Architecture

## 🏗️ Architecture Overview

The Intelligent Transcription application follows a modified MVC (Model-View-Controller) pattern architecture. The application combines PHP and Python for different aspects of functionality, with PHP handling web requests, file management, and request routing, while Python manages AI interactions and complex processing tasks.

### Core Components

1. **Controllers**: Handle user requests and coordinate responses
2. **Services**: Implement business logic and interact with external APIs
3. **Utils**: Provide helper functions for common tasks
4. **Templates**: Manage the presentation layer using Twig
5. **Python Scripts**: Handle AI model interactions and audio processing

## 📊 Component Details

### Controllers

Controllers are responsible for handling HTTP requests, processing form data, and delegating tasks to appropriate services. They then return responses or redirect users.

**Key Controllers:**
- `TranscriptionController`: Manages audio file uploads and YouTube URL processing
- `ChatController`: Handles chat interactions and context management
- `ParaphraseController`: Manages text paraphrasing requests

Example controller flow:
```php
public function handleFileUpload() {
    // 1. Validate user input
    // 2. Process file upload using TranscriptionService
    // 3. Redirect to results page or error page
}
```

### Services

Services contain the core business logic of the application. They handle interactions with external APIs, file processing, and data manipulation.

**Key Services:**
- `TranscriptionService`: Manages audio preprocessing and transcription
- `YouTubeService`: Handles YouTube video downloading and processing
- `ChatService`: Manages chat interactions with the OpenAI API
- `ParaphraseService`: Handles text paraphrasing with OpenAI
- `CacheService`: Implements caching for prompt and conversation optimization
- `SummarizerService`: Manages conversation summarization for context length optimization
- `AnalyticsController`: Provides visualization of cache and performance metrics

Example service flow:
```php
public function transcribeAudio($filePath, $outputPath, $language = null) {
    // 1. Validate file
    // 2. Call Python script to access OpenAI Whisper API
    // 3. Process and return results
}
```

### Utils

Utility classes provide helper functions used across the application.

**Key Utils:**
- `FileUtils`: File management operations
- `ResponseUtils`: HTTP response handling
- `YouTubeUtils`: YouTube-specific functionality
- `PromptUtils`: Optimizes prompts for better caching and token efficiency

### Python Integration

Python scripts handle interactions with OpenAI APIs and complex processing tasks.

**Key Python Scripts:**
- `transcribe.py`: Interfaces with OpenAI Whisper API
- `paraphrase.py`: Uses OpenAI for text enhancement
- `preprocess_audio.py`: Optimizes audio files for API processing

## 📈 Data Flow Diagrams

### Transcription Process Flow

```mermaid
sequenceDiagram
    participant User
    participant PHP as PHP Controller
    participant Service as PHP Service
    participant Python as Python Script
    participant OpenAI as OpenAI API
    participant FileSystem as File System

    User->>PHP: Upload audio file
    PHP->>Service: Process file
    Service->>FileSystem: Save uploaded file
    Service->>Python: Call preprocess_audio.py
    Python->>FileSystem: Save preprocessed file
    Service->>Python: Call transcribe.py
    Python->>OpenAI: Send to Whisper API
    OpenAI->>Python: Return transcription
    Python->>FileSystem: Save result as JSON
    Python->>Service: Return result
    Service->>PHP: Return success/failure
    PHP->>User: Redirect to result page
```

### YouTube Download Process Flow

```mermaid
sequenceDiagram
    participant User
    participant PHP as PHP Controller
    participant Service as PHP Service
    participant YTService as YouTube Service
    participant VideoAPI as Video Download API
    participant Python as Python Script
    participant OpenAI as OpenAI API
    participant FileSystem as File System

    User->>PHP: Submit YouTube URL
    PHP->>YTService: Process YouTube URL
    YTService->>VideoAPI: Request video download
    VideoAPI->>YTService: Return download link
    YTService->>FileSystem: Save downloaded audio
    YTService->>Service: Request transcription
    Service->>Python: Call transcribe.py
    Python->>OpenAI: Send to Whisper API
    OpenAI->>Python: Return transcription
    Python->>FileSystem: Save result as JSON
    Python->>Service: Return result
    Service->>YTService: Return result
    YTService->>PHP: Return success/failure
    PHP->>User: Redirect to result page
```

### Chat Process Flow with Caching

```mermaid
sequenceDiagram
    participant User
    participant PHP as PHP Controller
    participant Service as Chat Service
    participant Cache as Cache Service
    participant Summarizer as Summarizer Service
    participant Python as Python Script
    participant OpenAI as OpenAI API
    participant DB as Database

    User->>PHP: Send chat message
    PHP->>Service: Process message with context
    Service->>DB: Get conversation history
    
    opt Long conversation detected
        Service->>Summarizer: Request summarization
        Summarizer->>DB: Get messages to summarize
        Summarizer->>Python: Generate summary
        Python->>OpenAI: Request summary
        OpenAI->>Python: Return summary
        Python->>Summarizer: Return summary
        Summarizer->>DB: Store summarized message
        Summarizer->>Service: Return optimized context
    end
    
    Service->>Cache: Check for cached response
    
    alt Cache hit
        Cache->>DB: Update cache analytics
        Cache->>Service: Return cached response
        Service->>PHP: Return cached response
        PHP->>User: Display response (fast)
    else Cache miss
        Service->>Python: Call chat_api.py
        Python->>OpenAI: Send to GPT API with context
        OpenAI->>Python: Return response
        Python->>Service: Return response
        Service->>DB: Save message
        Service->>Cache: Store in cache
        Cache->>DB: Update cache analytics
        Service->>PHP: Return response
        PHP->>User: Display response
    end
    
    opt User requests analytics
        User->>PHP: Request cache analytics
        PHP->>Service: Get analytics data
        Service->>Cache: Get cache statistics
        Cache->>DB: Query cache metrics
        DB->>Cache: Return metrics
        Cache->>Service: Return analytics
        Service->>PHP: Return visualized data
        PHP->>User: Display analytics dashboard
    end
```

## 💾 File Storage

The application uses the file system for storage, with several key directories:

- `/uploads/`: Temporary storage for original uploaded files
- `/temp_audio/`: Preprocessed audio files optimized for the API
- `/results/`: JSON files containing transcription results
- `/exports/`: Text files containing exported chat histories

Each file uses a unique identifier generated at creation time, typically combining a timestamp with a unique ID.

Example result structure:
```json
{
  "success": true,
  "text": "Transcribed content here...",
  "language": "fr",
  "original_text": "Original text if translation was applied"
}
```

## 🔄 Error Handling

The application implements comprehensive error handling:

1. **PHP Error Handlers**: Custom error and exception handlers in bootstrap.php
2. **Service Error Returns**: Consistent error response format
3. **Python Exception Handling**: Try/except blocks with JSON error responses
4. **Debug Logs**: Multiple log files for different aspects of the application

Example error response:
```json
{
  "success": false,
  "error": "Detailed error message here"
}
```

## 🔌 External Integrations

The application integrates with several external services:

1. **OpenAI API**:
   - Whisper API for transcription
   - ChatGPT API for contextual chat
   - Assistant API for paraphrasing

2. **Video Download API (loader.to)**:
   - Used for downloading audio from YouTube videos
   - Handles both regular videos and YouTube Shorts

Integration is primarily managed through Python scripts, which handle API authentication, request formatting, and response processing.