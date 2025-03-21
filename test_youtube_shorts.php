<?php

/**
 * Script de test pour vérifier la prise en charge des liens YouTube Shorts
 */

// Inclure les fichiers nécessaires
require_once 'utils.php';

// Tableau des URLs à tester
$testUrls = [
    // URLs YouTube standard
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ',
    'http://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtu.be',

    // URLs YouTube Shorts
    'https://www.youtube.com/shorts/G1C3ydiPxzU',
    'https://youtube.com/shorts/G1C3ydiPxzU',
    'https://www.youtube.com/shorts/G1C3ydiPxzU?feature=share',

    // URLs invalides
    'https://www.youtube.com/channel/UC-lHJZR3Gqxm24_Vd_AJ5Yw',
    'https://www.youtu.be/invalid',
    'https://www.example.com/video',
    'not a url'
];

echo "=== Test de validation des URLs YouTube ===\n\n";

foreach ($testUrls as $url) {
    $isValid = isValidYoutubeUrl($url);
    $videoId = getYoutubeVideoId($url);

    echo "URL: $url\n";
    echo "Valide: " . ($isValid ? 'Oui' : 'Non') . "\n";
    echo "ID vidéo: " . ($videoId ?: 'Non extrait') . "\n\n";
}

echo "=== Test spécifique pour l'URL fournie ===\n\n";

$shortsUrl = 'https://www.youtube.com/shorts/G1C3ydiPxzU';
$isValid = isValidYoutubeUrl($shortsUrl);
$videoId = getYoutubeVideoId($shortsUrl);

echo "URL: $shortsUrl\n";
echo "Valide: " . ($isValid ? 'Oui' : 'Non') . "\n";
echo "ID vidéo: " . ($videoId ?: 'Non extrait') . "\n\n";

echo "Pour utiliser cette URL dans l'application, copiez-la et collez-la dans le champ URL YouTube sur la page d'accueil.\n";
