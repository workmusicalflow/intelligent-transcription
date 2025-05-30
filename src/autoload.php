<?php

/**
 * Autoloader pour charger automatiquement les classes
 */

// Load Composer's autoloader first (for external dependencies like Twig)
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Then register our custom autoloader for application classes
spl_autoload_register(function ($class) {
    // Only try to autoload classes we expect to be in our application
    // Add additional namespaces here as needed
    $supportedNamespaces = [
        'Controllers\\',
        'Database\\',
        'Middleware\\',
        'Models\\',
        'Services\\',
        'Template\\',
        'Utils\\',
        'App\\Services\\',
        'App\\'
    ];
    
    $namespaceFound = false;
    foreach ($supportedNamespaces as $namespace) {
        if (strpos($class, $namespace) === 0) {
            $namespaceFound = true;
            break;
        }
    }
    
    if (!$namespaceFound) {
        return; // Not our namespace, let the next autoloader handle it
    }
    
    // Base directory for the application
    $baseDir = __DIR__ . '/';
    
    // Handle App namespace mapping to src directory
    if (strpos($class, 'App\\') === 0) {
        // Remove 'App\' prefix and map to src directory
        $class = substr($class, 4);
    }
    
    // Replace namespace separators with directory separators
    // Add .php to the end
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    
    // If the file exists, load it
    if (file_exists($file)) {
        require $file;
    }
});
