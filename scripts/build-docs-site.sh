#!/bin/bash

# Build Documentation Site
# Combines all documentation into a unified site

set -e

echo "🛠️  Building Documentation Site..."

# Create output directory
mkdir -p docs-dist

# Generate main documentation index
cat > docs-dist/index.html << 'EOF'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligent Transcription - Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Intelligent Transcription</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Documentation Hub</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="https://github.com/your-org/intelligent-transcription" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.374 0 0 5.373 0 12 0 17.302 3.438 21.8 8.207 23.387c.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Overview Section -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Documentation Overview</h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    Welcome to the Intelligent Transcription documentation hub. This documentation is automatically generated 
                    and updated as the codebase evolves, ensuring it always reflects the current state of the application.
                </p>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Auto-Generated Documentation</h3>
                    </div>
                    <p class="text-blue-800 dark:text-blue-200">
                        This documentation is automatically updated on every commit to the main branch. 
                        Last updated: <span id="last-updated"></span>
                    </p>
                </div>
            </div>

            <!-- Documentation Sections -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Architecture Documentation -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m2 0v-4a2 2 0 012-2h2a2 2 0 012 2v4m-3 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v8"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Architecture</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">System design & structure</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Comprehensive overview of the system architecture, including frontend, backend, and deployment strategies.
                    </p>
                    <div class="space-y-2">
                        <a href="architecture/overview.html" class="block text-blue-600 dark:text-blue-400 hover:underline">System Overview</a>
                        <a href="architecture/frontend.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Frontend Architecture</a>
                        <a href="architecture/backend.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Backend Architecture</a>
                        <a href="architecture/deployment.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Deployment Guide</a>
                    </div>
                </div>

                <!-- API Documentation -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Reference</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">REST & GraphQL APIs</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Complete API documentation including endpoints, request/response formats, and authentication.
                    </p>
                    <div class="space-y-2">
                        <a href="backend/api/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">REST API Documentation</a>
                        <a href="frontend/api/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">TypeScript API Types</a>
                        <a href="graphql/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">GraphQL Schema</a>
                    </div>
                </div>

                <!-- Component Documentation -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Components</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">UI component library</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Interactive component documentation with examples, props, and usage guidelines.
                    </p>
                    <div class="space-y-2">
                        <a href="storybook/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Storybook Components</a>
                        <a href="components/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Component API Docs</a>
                        <a href="design-system/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Design System</a>
                    </div>
                </div>

                <!-- Development Guide -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Development</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Setup & contribution guide</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Everything you need to start contributing to the project, from setup to deployment.
                    </p>
                    <div class="space-y-2">
                        <a href="setup/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Environment Setup</a>
                        <a href="contributing/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Contributing Guide</a>
                        <a href="testing/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Testing Guide</a>
                        <a href="deployment/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Deployment Guide</a>
                    </div>
                </div>

                <!-- Test Coverage -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Test Coverage</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Quality metrics</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Test coverage reports and quality metrics to ensure code reliability.
                    </p>
                    <div class="space-y-2">
                        <a href="coverage/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Coverage Report</a>
                        <a href="test-results/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Test Results</a>
                        <a href="quality/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Code Quality</a>
                    </div>
                </div>

                <!-- Decision Records -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ADRs</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Architecture decisions</p>
                        </div>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Architectural Decision Records documenting important design choices and their rationale.
                    </p>
                    <div class="space-y-2">
                        <a href="adr/index.html" class="block text-blue-600 dark:text-blue-400 hover:underline">All Decisions</a>
                        <a href="adr/template.html" class="block text-blue-600 dark:text-blue-400 hover:underline">ADR Template</a>
                        <a href="adr/process.html" class="block text-blue-600 dark:text-blue-400 hover:underline">Decision Process</a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            &copy; 2024 Intelligent Transcription. Documentation auto-generated.
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Powered by</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">GitHub Actions</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Set last updated time
        document.getElementById('last-updated').textContent = new Date().toLocaleString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Dark mode toggle (optional)
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
EOF

# Copy existing documentation
if [ -d "docs" ]; then
    echo "📁 Copying existing documentation..."
    cp -r docs/* docs-dist/ 2>/dev/null || echo "⚠️  No existing docs to copy"
fi

# Copy Storybook build if it exists
if [ -d "frontend/storybook-static" ]; then
    echo "📚 Copying Storybook build..."
    mkdir -p docs-dist/storybook
    cp -r frontend/storybook-static/* docs-dist/storybook/
fi

# Copy test coverage if it exists
if [ -d "frontend/coverage" ]; then
    echo "📊 Copying test coverage..."
    mkdir -p docs-dist/coverage
    cp -r frontend/coverage/* docs-dist/coverage/
fi

echo "✅ Documentation site built successfully!"
echo "📁 Output directory: docs-dist/"
echo "🌐 Open docs-dist/index.html to view the documentation"