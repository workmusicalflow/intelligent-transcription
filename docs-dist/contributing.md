# Contributing Guidelines

Thank you for your interest in contributing to the Intelligent Transcription project! This document provides guidelines and instructions for contributing to the project.

## 🔍 Getting Started

### Environment Setup

1. **Fork and Clone**:
   - Fork the repository on GitHub
   - Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/intelligent-transcription.git
   cd intelligent-transcription
   ```

2. **Set Up Development Environment**:
   - Configure environment variables in `.env`
   - Run the setup script:
   ```bash
   ./setup_env.sh
   ```

3. **Start the Development Server**:
   ```bash
   php -S localhost:8000 -c php.ini
   ```

## 🌱 Development Workflow

### Branch Strategy

- **`master` branch**: Production-ready code
- **Feature branches**: Create for new features (`feature/feature-name`)
- **Bug fix branches**: Create for bug fixes (`fix/issue-description`)

### Commit Guidelines

- Use clear, descriptive commit messages
- Begin with a verb (Add, Fix, Update, Refactor, etc.)
- Keep commits focused on single tasks/changes
- Reference issue numbers when applicable

Example commit messages:
```
Add YouTube Shorts support
Fix language detection in transcription results
Update OpenAI API integration to latest version
```

### Pull Request Process

1. Ensure your code follows the project's coding standards
2. Update documentation to reflect any changes
3. Include tests when adding new features
4. Ensure all tests pass
5. Submit PR to the `master` branch

## 📝 Coding Standards

### PHP Standards

- Follow PSR-12 coding standards
- Use proper namespacing (`namespace Controllers;`)
- Document all classes and methods with PHPDoc comments
- Use type hints where applicable

Example:
```php
/**
 * Transcribes an audio file
 * 
 * @param string $filePath Path to the audio file
 * @param string|null $language Language code
 * @return array Transcription result
 */
public function transcribeAudio(string $filePath, ?string $language = null): array
{
    // Implementation
}
```

### Python Standards

- Follow PEP 8 style guide
- Document functions with docstrings
- Use type hints in Python 3.6+
- Handle exceptions properly

Example:
```python
def transcribe_audio(file_path: str, language: str = None) -> dict:
    """
    Transcribes an audio file using OpenAI Whisper.
    
    Args:
        file_path: Path to the audio file
        language: Optional language code
        
    Returns:
        Dict containing transcription result
    """
    # Implementation
```

### JavaScript Standards

- Use ES6+ syntax
- Format with 2-space indentation
- Use descriptive variable and function names
- Add comments for complex logic

### HTML/CSS Standards

- Follow HTML5 standards
- Use semantic HTML elements
- Follow BEM methodology for CSS class naming
- Ensure responsive design

## 🧪 Testing

### Testing Guidelines

- Test new features before submitting
- Maintain existing tests when changing code
- Write both unit and integration tests when applicable

### Manual Testing Checklist

- Test on multiple browsers (Chrome, Firefox, Safari)
- Test on both desktop and mobile views
- Test with various file types and sizes
- Verify language detection and translation
- Test error scenarios (e.g., invalid files, API failures)

## 📚 Documentation

### Documentation Requirements

- Update README.md when adding features
- Document new API endpoints
- Update workflow diagrams if processes change
- Add JSDoc comments for JavaScript functions
- Add PHPDoc comments for PHP methods

## 🔄 Updating Dependencies

When updating dependencies:

1. Document the change and reason
2. Test thoroughly after upgrade
3. Update requirements.txt or composer.json
4. Update setup instructions if needed

## ⚠️ Common Issues

### Troubleshooting

- **API Key Issues**: Verify `.env` file has correct API keys
- **File Permission Errors**: Ensure upload directories are writable
- **Python Environment Issues**: Check venv activation and dependencies

## 📊 Project Roadmap

Key areas for contribution:

1. **Performance Optimization**:
   - Implement proper caching
   - Optimize file processing algorithms

2. **Feature Extensions**:
   - Add batch processing capabilities
   - Implement database storage for results
   - Add user authentication system

3. **UI Improvements**:
   - Enhance mobile experience
   - Add real-time progress indicators
   - Implement dark mode

4. **Testing**:
   - Increase test coverage
   - Add automated testing

## 🙏 Code of Conduct

- Be respectful and inclusive
- Give constructive feedback
- Maintain professionalism
- Respect the project's goals and decisions
- Help others learn and grow

## 📫 Contact

For questions or assistance:
- Open an issue on GitHub
- Reach out to the project maintainers at [contact email]

Thank you for contributing to make Intelligent Transcription better!