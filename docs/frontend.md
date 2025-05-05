# Frontend Architecture

## 🎨 Overview

The frontend of the Intelligent Transcription application is built using Twig as the templating engine, with Tailwind CSS for styling and vanilla JavaScript for interactivity. The interface is designed to be responsive, intuitive, and accessible across different devices.

## 🧩 Template Structure

### Template Organization

The application uses Twig templates organized in a hierarchical structure:

```
/templates/
├── base/
│   └── layout.twig        # Base layout template with common elements
├── home/
│   └── index.twig         # Homepage template with upload forms
├── result/
│   └── show.twig          # Result display template
├── chat/
│   └── index.twig         # Chat interface template
└── processing/
    └── show.twig          # Processing status template
```

### Template Inheritance

Templates follow an inheritance pattern with `layout.twig` serving as the base template. Each page extends this base template and overrides specific blocks:

```twig
{% extends 'base/layout.twig' %}

{% block title %}Specific Page Title{% endblock %}

{% block content %}
    <!-- Page-specific content here -->
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    <!-- Page-specific scripts here -->
{% endblock %}
```

## 📱 UI Components

### Core UI Components

The application includes several key UI components:

#### 1. **File Upload Form**

- **Location**: `templates/home/index.twig`
- **Description**: Form for uploading audio/video files
- **Features**: 
  - Drag-and-drop file upload
  - Language selection dropdown
  - Force language translation option

**Key HTML Structure:**
```html
<form method="post" action="transcribe.php" enctype="multipart/form-data" class="upload-form">
    <div class="dropzone">
        <!-- File drop area -->
    </div>
    <div class="language-options">
        <!-- Language selection -->
    </div>
    <button type="submit" class="btn-primary">Transcrire</button>
</form>
```

#### 2. **YouTube URL Form**

- **Location**: `templates/home/index.twig`
- **Description**: Form for entering YouTube URLs
- **Features**:
  - URL input field
  - Language selection (shared with file upload form)
  - Support for YouTube Shorts

**Key HTML Structure:**
```html
<form method="post" action="youtube_download.php" class="youtube-form">
    <div class="input-group">
        <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
    </div>
    <div class="language-options">
        <!-- Language selection -->
    </div>
    <button type="submit" class="btn-primary">Transcrire YouTube</button>
</form>
```

#### 3. **Transcription Result Panel**

- **Location**: `templates/result/show.twig`
- **Description**: Panel displaying transcription results
- **Features**:
  - Formatted text display
  - Copy to clipboard functionality
  - Download as text file option
  - Paraphrase button
  - Chat with context button

**Key HTML Structure:**
```html
<div class="result-panel">
    <div class="result-header">
        <h2>Résultat de la transcription</h2>
        <div class="result-actions">
            <!-- Action buttons -->
        </div>
    </div>
    <div class="result-content">
        <!-- Transcription text -->
    </div>
    <div class="result-footer">
        <!-- Additional actions -->
    </div>
</div>
```

#### 4. **Chat Interface**

- **Location**: `templates/chat/index.twig`
- **Description**: Interactive chat interface for contextual conversations
- **Features**:
  - Message history display
  - Message input form
  - Export chat button
  - Context indicators

**Key HTML Structure:**
```html
<div class="chat-container">
    <div class="chat-messages" id="chatMessages">
        <!-- Message history -->
    </div>
    <form id="chatForm" class="chat-input-form">
        <textarea name="message" placeholder="Posez une question sur le contenu transcrit..."></textarea>
        <button type="submit" class="btn-send">Envoyer</button>
    </form>
    <div class="chat-actions">
        <!-- Export button -->
    </div>
</div>
```

#### 5. **Navigation Bar**

- **Location**: `templates/base/layout.twig`
- **Description**: Main navigation bar
- **Features**:
  - Responsive design (mobile menu)
  - Active page indicators

**Key HTML Structure:**
```html
<nav class="bg-gray-800 text-white shadow-md">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-3">
            <div class="font-semibold text-xl">{{ app_name }}</div>
            <button class="md:hidden p-2" id="mobile-menu-button">
                <!-- Mobile menu icon -->
            </button>
            <ul class="hidden md:flex space-x-4" id="main-menu">
                <!-- Navigation links -->
            </ul>
        </div>
    </div>
    <div class="md:hidden" id="mobile-menu">
        <!-- Mobile navigation -->
    </div>
</nav>
```

## 🎭 CSS Architecture

### Tailwind CSS Integration

The application uses Tailwind CSS, a utility-first CSS framework. The main CSS files are:

- `assets/css/tailwind.css`: Core Tailwind imports
- `assets/css/style.css`: Custom styles and Tailwind extensions

**Key Tailwind Configuration:**
```js
// tailwind.config.js
module.exports = {
  content: [
    "./templates/**/*.twig",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      // Custom theme extensions
    }
  },
  plugins: [
    // Additional plugins
  ]
}
```

### Custom Component Styles

Custom component styles are defined in `style.css` and follow this pattern:

```css
/* Example component styles */
.alert-error {
  @apply bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition-colors;
}

.dropzone {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors;
}
```

## 📊 JavaScript Components

### Core JavaScript Modules

The application uses vanilla JavaScript for frontend interactivity:

#### 1. **Upload Form Handler**

- **Location**: `assets/js/app.js`
- **Description**: Handles file uploads and form validation
- **Features**:
  - Drag and drop functionality
  - File type validation
  - File size validation
  - Form submission handling

#### 2. **Chat Interface Manager**

- **Location**: `assets/js/app.js`
- **Description**: Manages the chat interface functionality
- **Features**:
  - Message submission
  - Response display
  - Message history management
  - Chat export

#### 3. **UI Utilities**

- **Location**: `assets/js/app.js`
- **Description**: Utility functions for UI management
- **Features**:
  - Copy to clipboard functionality
  - Mobile menu toggle
  - Form validation
  - Error message display

## 🔄 State Management

### Session-based State

The application uses PHP sessions for state management:

- **Chat History**: Stored in `$_SESSION['chat_history']`
- **Transcription Context**: Stored in `$_SESSION['transcription_context']`
- **Error Messages**: Passed via URL parameters and session flash messages

### Local State Management

Client-side state is managed through JavaScript:

- DOM manipulation for UI updates
- Form data handling
- Temporary storage of chat messages before submission

## 🌐 Responsive Design

The application implements responsive design principles using Tailwind's responsive utilities:

- Mobile-first approach
- Breakpoint-specific classes (`md:`, `lg:`, etc.)
- Flexible layouts using Flexbox and Grid
- Responsive typography
- Touch-friendly interaction elements for mobile users

**Example responsive pattern:**
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- Content adapts to screen size -->
</div>
```

## 🔄 Page Transitions and Loading States

The application includes visual indicators for loading states:

- Loading spinner for transcription processing
- Chat message sending indicators
- Button state changes during form submission

**Loading Spinner Example:**
```html
<div class="loading-spinner">
    <div class="spinner"></div>
    <p>Transcription en cours...</p>
</div>
```

## 📱 Mobile Considerations

The mobile experience includes these optimizations:

- Simplified forms on smaller screens
- Touch-friendly button sizes (min 44×44px)
- Collapsible navigation for small screens
- Responsive font sizes
- Limited horizontal scrolling