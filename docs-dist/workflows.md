# User Workflows

This document outlines the main user workflows of the Intelligent Transcription application with sequence diagrams to illustrate the interactions between the user, the application, and external services.

## 🔄 File Upload and Transcription Workflow

This workflow describes the process of uploading an audio or video file and getting it transcribed.

```mermaid
sequenceDiagram
    actor User
    participant UI as Web Interface
    participant TC as TranscriptionController
    participant TS as TranscriptionService
    participant PP as Python Process
    participant OpenAI as OpenAI API
    participant FS as File System

    User->>UI: Upload audio/video file
    User->>UI: Select language options
    User->>UI: Click "Transcribe"
    UI->>TC: Submit form (POST /transcribe.php)
    TC->>TS: processUploadedFile()
    TS->>FS: Save uploaded file
    TS->>PP: Execute preprocess_audio.py
    PP->>FS: Save preprocessed file
    TS->>PP: Execute transcribe.py
    PP->>OpenAI: Request transcription (Whisper API)
    OpenAI->>PP: Return transcription text
    PP->>FS: Save result JSON
    PP->>TS: Return result
    TS->>TC: Return result
    TC->>UI: Redirect to result page
    UI->>User: Display transcription result
    User->>UI: Download transcription (optional)
```

## 🔄 YouTube Video Transcription Workflow

This workflow describes the process of transcribing content from a YouTube URL.

```mermaid
sequenceDiagram
    actor User
    participant UI as Web Interface
    participant TC as TranscriptionController
    participant YS as YouTubeService
    participant TS as TranscriptionService
    participant YT as YouTube API
    participant PP as Python Process
    participant OpenAI as OpenAI API
    participant FS as File System

    User->>UI: Enter YouTube URL
    User->>UI: Select language options
    User->>UI: Click "Transcribe YouTube"
    UI->>TC: Submit form (POST /youtube_download.php)
    TC->>YS: downloadAndTranscribe()
    YS->>YT: Download YouTube video audio
    YT->>FS: Save audio file
    YS->>TS: preprocessAudio()
    TS->>PP: Execute preprocess_audio.py
    PP->>FS: Save preprocessed file
    YS->>PP: Execute transcribe.py
    PP->>OpenAI: Request transcription (Whisper API)
    OpenAI->>PP: Return transcription text
    PP->>FS: Save result JSON
    PP->>YS: Return result
    YS->>TC: Return result
    TC->>UI: Redirect to result page
    UI->>User: Display transcription result
    User->>UI: Download transcription (optional)
```

## 🔄 Paraphrasing Workflow

This workflow describes the process of paraphrasing a transcription to improve clarity and readability.

```mermaid
sequenceDiagram
    actor User
    participant UI as Web Interface
    participant PC as ParaphraseController
    participant PS as ParaphraseService
    participant PP as Python Process
    participant OpenAI as OpenAI Assistant API
    participant FS as File System

    User->>UI: View transcription result
    User->>UI: Click "Paraphrase"
    UI->>PC: Submit request (POST /paraphrase.php)
    PC->>PS: paraphraseText()
    PS->>PP: Execute paraphrase.py
    PP->>OpenAI: Send text to Assistant API
    OpenAI->>PP: Return paraphrased text
    PP->>PS: Return result
    PS->>PC: Return result
    PC->>UI: Return JSON response
    UI->>User: Display original and paraphrased text
    User->>UI: Select preferred version
    User->>UI: Download paraphrased result (optional)
```

## 🔄 Contextual Chat Workflow

This workflow describes the process of engaging in a contextual chat about the transcribed content.

```mermaid
sequenceDiagram
    actor User
    participant UI as Web Interface
    participant CC as ChatController
    participant CS as ChatService
    participant TS as TranscriptionService
    participant PP as Python Process
    participant OpenAI as OpenAI Chat API
    participant FS as File System

    User->>UI: View transcription result
    User->>UI: Click "Chat with Assistant"
    UI->>CC: Navigate to chat page
    CC->>UI: Load chat interface
    
    User->>UI: Type question about content
    User->>UI: Submit chat message
    UI->>CC: Send message (POST /chat.php)
    CC->>CS: sendMessage()
    CS->>TS: getTranscriptionResult()
    TS->>CS: Return transcription text
    CS->>PP: Execute chat_api.py
    PP->>OpenAI: Send message + context to GPT API
    OpenAI->>PP: Return assistant response
    PP->>CS: Return response
    CS->>CC: Return result
    CC->>UI: Return JSON response
    UI->>User: Display assistant response
    
    Note over User,UI: Conversation continues...
    
    User->>UI: Click "Export Chat"
    UI->>CC: Request export (POST /chat.php?action=export)
    CC->>CS: exportConversation()
    CS->>FS: Save formatted chat history
    CS->>CC: Return export result
    CC->>UI: Provide download link
    UI->>User: Download chat history
```

## 🔄 Complete User Journey Workflow

This diagram shows a complete user journey, combining multiple workflows.

```mermaid
graph LR
    subgraph "Input Phase"
        A[User visits homepage] --> B1[Upload audio/video file]
        A --> B2[Enter YouTube URL]
    end
    
    subgraph "Processing Phase"
        B1 --> C1[File preprocessing]
        B2 --> C2[YouTube download]
        C1 --> D[Transcription with Whisper API]
        C2 --> D
    end
    
    subgraph "Result Phase"
        D --> E[View transcription result]
        E --> F1[Download result as text]
        E --> F2[Paraphrase result]
        E --> F3[Chat about content]
    end
    
    subgraph "Interaction Phase"
        F2 --> G1[View improved text]
        F3 --> G2[Ask questions about content]
        G2 --> G3[Continue conversation]
        G3 --> G4[Export chat history]
    end
    
    style A fill:#d4f1f9,stroke:#05728f
    style D fill:#ffe6cc,stroke:#d79b00
    style E fill:#d5e8d4,stroke:#82b366
    style F3 fill:#e1d5e7,stroke:#9673a6
```

## 📋 User Story: First-time User Experience

1. **Homepage Entry**
   - User visits the application homepage
   - User sees the file upload section and YouTube URL input

2. **Input Selection**
   - User chooses to upload a local audio file
   - User selects language options (e.g., automatic detection)
   - User clicks "Transcribe" button

3. **Processing**
   - System shows loading indicator
   - File is uploaded, preprocessed, and transcribed
   - System redirects to results page

4. **Result Exploration**
   - User views transcription text
   - User explores available options (download, paraphrase, chat)

5. **Feature Discovery**
   - User clicks "Chat with Assistant"
   - User is taken to chat interface with transcription context loaded
   - User asks questions about the transcribed content
   - User receives contextually relevant answers

6. **Export and Sharing**
   - User exports chat history
   - User downloads transcription

## 📋 User Story: Regular User Experience

1. **Efficient Input**
   - User enters YouTube URL directly
   - User selects preferred language and "Force language" option
   - User initiates transcription

2. **Result Review**
   - User quickly reviews transcription for accuracy
   - User immediately clicks "Paraphrase" for improved readability

3. **Content Interaction**
   - User begins chat session with specific questions
   - User conducts focused conversation about key topics
   - User exports conversation for documentation purposes

4. **Multiple Session Management**
   - User starts a new transcription in another tab
   - User efficiently switches between multiple transcriptions
   - User leverages previously learned workflow patterns