<?php

/**
 * Fonctions utilitaires pour l'application de transcription audio
 */

/**
 * Redirige vers une URL
 * 
 * @param string $url URL de redirection
 * @return void
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * Vérifie si une URL est une URL YouTube valide
 * 
 * @param string $url URL à vérifier
 * @return bool True si l'URL est une URL YouTube valide, false sinon
 */
function isValidYoutubeUrl($url)
{
    // Pattern pour les URLs YouTube standard et les URLs YouTube Shorts
    $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})(\S*)?$/';
    return preg_match($pattern, $url) === 1;
}

/**
 * Extrait l'ID de la vidéo YouTube à partir de l'URL
 * 
 * @param string $url URL YouTube
 * @return string|null ID de la vidéo YouTube ou null si non trouvé
 */
function getYoutubeVideoId($url)
{
    // Pattern pour les URLs YouTube standard et les URLs YouTube Shorts
    $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})(\S*)?$/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[4];
    }
    return null;
}

/**
 * Affiche un message d'erreur
 * 
 * @param string $code Code d'erreur
 * @param string|null $message Message d'erreur supplémentaire
 * @return string Message d'erreur formaté
 */
function getErrorMessage($code, $message = null)
{
    $errors = [
        'upload' => 'Erreur lors du téléchargement du fichier',
        'size' => 'Le fichier est trop volumineux',
        'move' => 'Erreur lors du déplacement du fichier',
        'preprocess' => 'Erreur lors du prétraitement du fichier audio',
        'transcription' => 'Erreur lors de la transcription',
        'paraphrase' => 'Erreur lors de la paraphrase du texte',
        'missing_id' => 'ID de résultat manquant',
        'result_not_found' => 'Résultat non trouvé',
        'invalid_result' => 'Résultat invalide',
        'invalid_file' => 'Le fichier n\'est pas un fichier audio ou vidéo valide',
        'youtube' => 'Erreur lors du téléchargement de la vidéo YouTube'
    ];

    // Si un message spécifique est fourni, l'utiliser
    if ($message) {
        return $errors[$code] ?? 'Erreur inconnue' . ': ' . $message;
    }

    // Sinon, utiliser le message par défaut
    return $errors[$code] ?? 'Erreur inconnue';
}

/**
 * Génère un identifiant unique
 * 
 * @return string Identifiant unique
 */
function generateUniqueId()
{
    return uniqid('', true);
}

/**
 * Formate la taille d'un fichier en unités lisibles
 * 
 * @param int $size Taille en octets
 * @return string Taille formatée
 */
function formatFileSize($size)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }

    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Vérifie si un fichier est un fichier audio ou vidéo valide
 * 
 * @param string $filePath Chemin du fichier
 * @return bool True si le fichier est valide, false sinon
 */
function isValidMediaFile($filePath)
{
    $mimeType = mime_content_type($filePath);
    $validTypes = [
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/x-wav',
        'audio/ogg',
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo'
    ];

    return in_array($mimeType, $validTypes);
}

/**
 * Nettoie les fichiers temporaires plus anciens qu'une certaine durée
 * 
 * @param string $directory Répertoire à nettoyer
 * @param int $maxAge Âge maximum en secondes (par défaut 24 heures)
 * @return int Nombre de fichiers supprimés
 */
function cleanupOldFiles($directory, $maxAge = 86400)
{
    if (!is_dir($directory)) {
        return 0;
    }

    $count = 0;
    $now = time();

    foreach (new DirectoryIterator($directory) as $fileInfo) {
        if ($fileInfo->isDot() || $fileInfo->isDir()) {
            continue;
        }

        if ($now - $fileInfo->getMTime() > $maxAge) {
            unlink($fileInfo->getPathname());
            $count++;
        }
    }

    return $count;
}
