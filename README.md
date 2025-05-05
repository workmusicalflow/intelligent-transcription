# Intelligent Transcription

A powerful application for transcribing audio and video files to text with smart paraphrasing and contextual chat capabilities, powered by OpenAI.

## 📋 Overview

Intelligent Transcription helps you convert spoken content from audio files, video files, and YouTube videos into accurate text transcriptions. It also offers advanced features like AI-powered paraphrasing to improve readability and contextual chat to interact with the transcribed content.

### Key Features

- 🎵 **Audio/Video Transcription**: Upload files and get accurate text transcriptions
- 📺 **YouTube Integration**: Transcribe content directly from YouTube URLs (including Shorts)
- 🌍 **Multi-language Support**: Automatic language detection and translation options
- ✍️ **Smart Paraphrasing**: Improve clarity with AI-powered reformulation
- 💬 **Contextual Chat**: Ask questions about the transcribed content
- 📥 **Export Options**: Download transcriptions and conversation histories

## 🚀 Quick Start

### Prerequisites
- PHP 8.1 or higher
- Python 3.9 or higher
- SQLite3 support for PHP
- OpenAI API key
- Video Download API key (for YouTube functionality)

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://your-repository-url.git
   cd intelligent-transcription
   ```

2. **Create and configure .env file**:
   ```
   OPENAI_API_KEY=your_openai_api_key
   VIDEO_DOWNLOAD_API_KEY=your_video_download_api_key
   ```

3. **Run the setup script**:
   ```bash
   ./setup_env.sh
   ```

4. **Initialize the database**:
   ```bash
   php migrate.php
   ```

5. **Start the server**:
   ```bash
   php -S localhost:8000 -c php.ini
   ```

6. **Access the application**:
   Open your browser and go to `http://localhost:8000`

## 📚 Documentation

Our comprehensive documentation covers all aspects of the application:

- [**Detailed Project Overview**](docs/README.md) - Complete features, setup, and usage guide
- [**Backend Architecture**](docs/architecture.md) - Backend components and data flow diagrams
- [**API Documentation**](docs/api.md) - Internal and external API endpoints
- [**Frontend Architecture**](docs/frontend.md) - UI components and frontend design
- [**Database Integration**](docs/database.md) - SQLite database schema and implementation
- [**User Workflows**](docs/workflows.md) - Common usage patterns with sequence diagrams
- [**Contribution Guidelines**](docs/contributing.md) - How to contribute to the project

## 📊 Project Structure

The application follows a modified MVC architecture:

```
/
├── assets/                # CSS and JavaScript files
├── database/              # SQLite database files
├── docs/                  # Project documentation
├── results/               # Transcription results (JSON)
├── src/                   # Application source code
│   ├── Controllers/       # Request handlers
│   ├── Database/          # Database management
│   ├── Services/          # Business logic
│   ├── Utils/             # Helper functions
│   └── Template/          # Template management
├── templates/             # Twig templates
├── uploads/               # Uploaded files
├── temp_audio/            # Preprocessed audio
├── config.php             # Configuration file
├── migrate.php            # Database migration script
├── transcribe.py          # Python transcription script
├── paraphrase.py          # Python paraphrasing script
└── setup_env.sh           # Environment setup script
```

## 🔍 Usage Examples

### File Transcription
1. Go to the homepage
2. Upload an audio/video file
3. Select language options
4. Click "Transcribe"
5. View and download the transcription

### YouTube Transcription
1. Go to the homepage
2. Enter a YouTube URL
3. Select language options
4. Click "Transcribe YouTube"
5. View and download the transcription

### Using Contextual Chat
1. After transcription, click "Chat with Assistant"
2. Ask questions about the transcribed content
3. Get AI-powered responses based on the context
4. Export the conversation if needed

## ⚠️ Limitations

- Maximum file size: 100MB
- Supported formats: MP3, WAV, MP4, AVI, MOV, etc.
- API rate limits may apply

## 🔧 Troubleshooting

If you encounter issues:

- Check API keys in your .env file
- Verify Python environment setup
- Check debug logs for detailed error information
- Ensure file permissions are correct for upload directories

## 🔮 Future Development

- User authentication system
- Advanced file management
- Real-time streaming responses
- Batch processing
- Additional language options
- Data migration utilities
- Multi-user support

## 📝 License

This project is licensed under the [MIT License](LICENSE).

## 🙏 Acknowledgements

- OpenAI for the powerful AI models
- Loader.to for YouTube download functionality
- All contributors to the project