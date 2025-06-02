<?php

/**
 * Autoloader simple pour les services de traduction
 * En production, utiliser Composer autoload
 */

spl_autoload_register(function ($className) {
    // Convertir namespace en chemin fichier
    $className = str_replace('App\\', '', $className);
    $className = str_replace('\\', '/', $className);
    
    $file = __DIR__ . '/' . $className . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Charger dépendances essentielles si pas disponibles
if (!interface_exists('Psr\Log\LoggerInterface')) {
    // Définir interface logger simple
    eval('
    namespace Psr\Log {
        interface LoggerInterface {
            public function debug(string $message, array $context = []): void;
            public function info(string $message, array $context = []): void;
            public function warning(string $message, array $context = []): void;
            public function error(string $message, array $context = []): void;
        }
        
        class NullLogger implements LoggerInterface {
            public function debug(string $message, array $context = []): void {}
            public function info(string $message, array $context = []): void {}
            public function warning(string $message, array $context = []): void {}
            public function error(string $message, array $context = []): void {}
        }
    }
    ');
}