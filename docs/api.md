# API Documentation

This document outlines the internal and external API endpoints used by the Intelligent Transcription application.

## 🔄 Internal API Endpoints

These endpoints are used within the application for processing requests.

### Transcription API

#### File Upload Processing

- **URL**: `/transcribe.php`
- **Method**: `POST`
- **Description**: Processes an uploaded audio/video file for transcription

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `audio_file` | File | Yes | Audio/video file to transcribe |
| `language` | String | No | Language code (e.g., 'en', 'fr', 'auto') |
| `force_language` | Checkbox | No | Force translation to selected language |

**Response:**
- Redirects to `result.php?id={result_id}` on success
- Redirects to index with error query param on failure

#### YouTube Video Processing

- **URL**: `/youtube_download.php`
- **Method**: `POST`
- **Description**: Processes a YouTube URL for downloading and transcription

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `youtube_url` | String | Yes | Valid YouTube video URL |
| `language` | String | No | Language code (e.g., 'en', 'fr', 'auto') |
| `force_language` | Checkbox | No | Force translation to selected language |

**Response:**
- Redirects to `result.php?id={result_id}` on success
- Redirects to index with error query param on failure

#### Result Retrieval

- **URL**: `/result.php`
- **Method**: `GET`
- **Description**: Retrieves and displays a transcription result

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | String | Yes | Result ID from transcription process |

**Response:**
- HTML page with transcription result
- Redirects to index with error on invalid ID

#### Result Download

- **URL**: `/download.php`
- **Method**: `GET`
- **Description**: Downloads a transcription result as a text file

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | String | Yes | Result ID from transcription process |

**Response:**
- Text file download with transcription content
- Redirects to index with error on invalid ID

### Chat API

#### Chat Processing

- **URL**: `/chat.php`
- **Method**: `POST`
- **Description**: Processes a chat message with contextual awareness

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `message` | String | Yes | User's chat message |
| `transcription_id` | String | No | Associated transcription ID |

**Response:**
- JSON object with assistant's response
- Error message on failure

#### Chat Export

- **URL**: `/chat.php?action=export`
- **Method**: `POST`
- **Description**: Exports chat history to a text file

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `messages` | Array | Yes | Chat history to export |

**Response:**
- Text file download with formatted chat history
- Error message on failure

### Paraphrase API

- **URL**: `/paraphrase.php`
- **Method**: `POST`
- **Description**: Paraphrases text to improve clarity and quality

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `text` | String | Yes | Text to paraphrase |
| `language` | String | No | Language code (defaults to 'fr') |

**Response:**
- JSON object with paraphrased text
- Error message on failure

## 🔌 External API Integrations

The application integrates with external APIs to provide its functionality.

### OpenAI APIs

#### Whisper Transcription API

- **Purpose**: Transcribes audio to text
- **Implementation**: Via `transcribe.py` Python script
- **Authentication**: OpenAI API key

**Request Format:**
```python
response = client.audio.transcriptions.create(
    model="whisper-1",
    file=audio_file,
    language=language
)
```

**Response Format:**
```json
{
  "text": "Transcribed text content"
}
```

#### ChatGPT API

- **Purpose**: Provides contextual chat capabilities
- **Implementation**: Via `chat_api.py` Python script
- **Authentication**: OpenAI API key

**Request Format:**
```python
response = client.chat.completions.create(
    model="gpt-4.1-nano",
    messages=[
        {"role": "system", "content": "System prompt"},
        {"role": "user", "content": "User message with context"}
    ]
)
```

**Response Format:**
```json
{
  "choices": [
    {
      "message": {
        "content": "Assistant response",
        "role": "assistant"
      }
    }
  ]
}
```

#### OpenAI Assistant API

- **Purpose**: Provides paraphrasing capabilities
- **Implementation**: Via `paraphrase.py` Python script
- **Authentication**: OpenAI API key and Assistant ID

**Setup:**
```python
assistant = client.beta.assistants.create(
    name="Paraphraser",
    instructions="Paraphrasing instructions...",
    model="gpt-4.1-nano",
)
```

**Usage Flow:**
1. Create/retrieve assistant
2. Create thread
3. Add message to thread
4. Run assistant on thread
5. Stream response

### Video Download API (loader.to)

- **Purpose**: Downloads audio from YouTube videos
- **Implementation**: Via `utils/YouTubeUtils.php`
- **Authentication**: Video Download API key

**Download Process Flow:**
1. Submit YouTube URL to loader.to API
2. Retrieve unique file identifier
3. Poll progress endpoint until download is complete
4. Download audio file using provided URL

**Request Format:**
```
GET https://loader.to/ajax/download.php?url={youtube_url}&format=mp3&key={api_key}
```

**Response Format:**
```json
{
  "success": true,
  "id": "unique_file_id",
  "process_url": "progress_check_url"
}
```

## 💾 Data Formats

### Transcription Result Format

Results are stored as JSON files with the following structure:

```json
{
  "success": true,
  "text": "Transcribed content here...",
  "language": "fr",
  "original_text": "Original text if translation was applied",
  "youtube_url": "https://www.youtube.com/watch?v=xyz" // If from YouTube
}
```

### Chat Message Format

Chat messages are stored in session and exported with the following structure:

```json
[
  {
    "role": "user",
    "content": "User message"
  },
  {
    "role": "assistant",
    "content": "Assistant response"
  }
]
```

### Paraphrase Result Format

```json
{
  "success": true,
  "original_text": "Original text content",
  "paraphrased_text": "Improved version of the text",
  "language": "fr"
}
```

## 🔒 Error Handling

All APIs return consistent error formats:

```json
{
  "success": false,
  "error": "Specific error message"
}
```

Common error scenarios:
1. Invalid file format
2. File size exceeds limits
3. API authentication failure
4. Network connectivity issues
5. Invalid YouTube URL format
6. Resource not found (invalid ID)