<?php

namespace Utils;

/**
 * Classe utilitaire pour la gestion des fichiers
 */
class FileUtils
{
    /**
     * Formate la taille d'un fichier en unités lisibles
     * 
     * @param int $size Taille en octets
     * @return string Taille formatée
     */
    public static function formatFileSize($size)
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
    public static function isValidMediaFile($filePath)
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
    public static function cleanupOldFiles($directory, $maxAge = 86400)
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $count = 0;
        $now = time();

        foreach (new \DirectoryIterator($directory) as $fileInfo) {
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

    /**
     * Génère un identifiant unique
     * 
     * @return string Identifiant unique
     */
    public static function generateUniqueId()
    {
        return uniqid('', true);
    }
}
