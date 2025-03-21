<?php

/**
 * Autoloader pour charger automatiquement les classes
 */
spl_autoload_register(function ($class) {
    // Convertir le namespace en chemin de fichier
    $prefix = '';
    $baseDir = __DIR__ . '/';

    // Vérifier si la classe utilise le préfixe
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Non, passer au prochain autoloader enregistré
        return;
    }

    // Obtenir le chemin relatif de la classe
    $relativeClass = substr($class, $len);

    // Remplacer les namespace par des séparateurs de répertoire
    // Ajouter .php à la fin
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Si le fichier existe, le charger
    if (file_exists($file)) {
        require $file;
    }
});
