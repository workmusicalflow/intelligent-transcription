# Intelligent Transcription

## =� Overview

Intelligent Transcription is a powerful application for transcribing audio and video files into text, with additional features for paraphrasing and contextual chat. The application leverages OpenAI's APIs to provide accurate transcriptions, smart paraphrasing, and interactive conversations about the transcribed content.

### Key Features

- **File Transcription**: Upload audio/video files and get accurate text transcriptions
- **YouTube Integration**: Transcribe content directly from YouTube URLs (including Shorts)
- **Multi-language Support**: Automatic language detection with support for numerous languages
- **Smart Paraphrasing**: Improve transcription clarity with AI-powered text reformulation
- **Contextual Chat**: Ask questions about transcribed content and get intelligent responses
- **Export Options**: Download transcriptions and chat histories as text files
- **Customizable Language Options**: Force translation to a specific language

## =� Tech Stack

### Backend

- **PHP 8.1+**: Core application logic and API management
- **Python 3.9+**: AI model integration and audio processing
- **OpenAI APIs**:
  - Whisper API for speech-to-text conversion
  - GPT models for paraphrasing and contextual chat

### Frontend

- **HTML/CSS/JavaScript**: Core frontend technologies
- **Tailwind CSS**: Utility-first CSS framework for styling
- **Twig**: Template engine for rendering views

### External Services

- **OpenAI API**: For transcription, paraphrasing, and chat
- **Loader.to API**: For downloading YouTube videos

## =� Setup Instructions

### Prerequisites

- PHP 8.1 or higher
- Python 3.9 or higher
- Composer (for PHP dependencies)
- pip (for Python dependencies)
- OpenAI API key
- Video Download API key (for YouTube functionality)

### Installation

1. **Clone the repository**:

   ```bash
   git clone https://your-repository-url.git
   cd intelligent-transcription
   ```

2. **Create and configure the environment file (Secure Option)**:
   
   For enhanced security, we recommend storing sensitive configuration outside the web root:
   
   ```bash
   # Create a secure directory for environment variables
   mkdir -p ../inteligent-transcription-env
   
   # Copy the environment template
   cp .env.example ../inteligent-transcription-env/.env
   
   # Set secure permissions
   chmod 600 ../inteligent-transcription-env/.env
   
   # Edit the file with your API keys
   nano ../inteligent-transcription-env/.env
   ```
   
   Alternatively, you can create the `.env` file in the project root, but with added security precautions:
   
   ```bash
   # Copy the environment template
   cp .env.example .env
   
   # Set secure permissions
   chmod 600 .env
   
   # Edit the file with your API keys
   nano .env
   ```
   
   Required environment variables:
   ```
   OPENAI_API_KEY=your_openai_api_key
   VIDEO_DOWNLOAD_API_KEY=your_video_download_api_key
   PARAPHRASER_ASSISTANT_ID=your_assistant_id_if_you_have_one
   APP_ENV=development
   APP_DEBUG=true
   ```

3. **Set up Python environment**:

   ```bash
   ./setup_env.sh
   ```

   This script will:

   - Create a virtual environment
   - Install Python dependencies from requirements.txt
   - Configure the environment for OpenAI API access

4. **Start the application with the custom PHP configuration**:

   ```bash
   php -S localhost:8000 -c php.ini
   ```

5. **Access the application**:
   Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

### Security Notes

The application implements several security measures:

- **Environment Variables**: Securely stored, with options for placement outside web root
- **Input Validation**: Comprehensive validation for all user inputs
- **File Security**: Random filenames and nested storage for uploaded files
- **Error Handling**: Standardized error processing with detailed logging
- **Asynchronous Processing**: Background tasks for long-running operations

For detailed security documentation, see [docs/security.md](./security.md).

## =� Project Structure

### Core Directories

- `/src/`: Contains the source code
  - `/Controllers/`: Request handlers (MVC pattern)
  - `/Services/`: Business logic implementations
  - `/Utils/`: Utility functions and helpers
  - `/Template/`: Template management
- `/templates/`: Twig templates for the UI
- `/assets/`: CSS and JavaScript files
- `/uploads/`: Temporary storage for uploaded files
- `/temp_audio/`: Preprocessed audio files
- `/results/`: Transcription results (JSON format)
- `/exports/`: Exported chat histories

### Key Files

- `index.php`: Main entry point
- `transcribe.php`: Audio transcription handler
- `paraphrase.php`: Text paraphrasing handler
- `chat.php`: Contextual chat interface
- `transcribe.py`: Python script for OpenAI Whisper integration
- `paraphrase.py`: Python script for paraphrasing with OpenAI
- `config.php`: Application configuration
- `setup_env.sh`: Environment setup script

## =

Usage Guide

1. **Audio/Video File Transcription**:

   - Go to the homepage
   - Upload an audio or video file (MP3, WAV, MP4, etc.)
   - Select language options
   - Click "Transcribe"
   - View and download results

2. **YouTube Video Transcription**:

   - Go to the homepage
   - Enter a YouTube URL
   - Select language options
   - Click "Transcribe YouTube"
   - View and download results

3. **Using Paraphrasing**:

   - After transcription
   - Click "Paraphrase"
   - View the improved text version

4. **Using Contextual Chat**:
   - After transcription
   - Click "Chat with Assistant"
   - Ask questions about the transcribed content
   - Export chat history if needed

## � Limitations

- Maximum file size: 100MB
- Supported formats: MP3, WAV, MP4, AVI, MOV, and other common audio/video formats
- API rate limits may apply depending on your OpenAI account tier

## =' Troubleshooting

If you encounter issues:

1. **Check API Keys**: Ensure your OpenAI and Video Download API keys are valid
2. **Environment Setup**: Verify Python virtual environment is correctly configured
3. **File Size**: Ensure uploaded files don't exceed the 100MB limit
4. **File Format**: Verify uploaded files are in supported formats
5. **Logs**: Check debug logs for more detailed error information:
   - `debug_transcribe.log`
   - `debug_paraphrase.log`
   - `debug_upload.log`
   - `debug_youtube_download.log`
   - `php_errors.log`

## =. Future Development

- User authentication system
- Advanced file management
- Real-time streaming responses
- Batch processing for multiple files
- Additional language options and translation features
- Custom model selection for different transcription needs
