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
    
    /**
     * Génère un nom de fichier sécurisé basé sur un nom original
     * 
     * Cette méthode remplace le nom original par un nom aléatoire tout en
     * conservant l'extension d'origine.
     * 
     * @param string $originalName Nom de fichier original
     * @param bool $includeTimestamp Ajouter un timestamp au nom généré
     * @return string Nom de fichier sécurisé
     */
    public static function secureFileName($originalName, $includeTimestamp = true)
    {
        // Extraire l'extension du fichier original
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        
        // Assurons-nous que l'extension est valide et sécurisée
        if (empty($extension) || !preg_match('/^[a-zA-Z0-9]{1,5}$/', $extension)) {
            $extension = 'bin'; // Extension par défaut si non valide
        }
        
        // Générer un identifiant aléatoire pour le fichier
        $randomPart = bin2hex(random_bytes(8)); // 16 caractères hexadécimaux
        
        // Optionnellement, ajouter un timestamp
        $timestamp = $includeTimestamp ? '_' . date('Ymd_His') : '';
        
        // Assembler le nom de fichier sécurisé
        return $randomPart . $timestamp . '.' . $extension;
    }
    
    /**
     * Génère un chemin de stockage sécurisé pour un fichier
     * 
     * Cette méthode crée une structure de répertoires basée sur un hash de nom de fichier
     * pour éviter d'avoir trop de fichiers dans un même répertoire.
     * 
     * @param string $baseDir Répertoire de base
     * @param string $fileName Nom du fichier sécurisé
     * @param bool $createDirs Créer les sous-répertoires si nécessaire
     * @return string Chemin complet vers le fichier
     */
    public static function getSecureStoragePath($baseDir, $fileName, $createDirs = true)
    {
        // Générer un hash du nom de fichier
        $hash = md5($fileName);
        
        // Utiliser les 2 premiers caractères du hash comme sous-répertoire
        $subDir1 = substr($hash, 0, 2);
        $subDir2 = substr($hash, 2, 2);
        
        // Construire le chemin complet
        $storagePath = $baseDir . '/' . $subDir1 . '/' . $subDir2;
        
        // Créer les répertoires si demandé
        if ($createDirs && !is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        // Retourner le chemin complet vers le fichier
        return $storagePath . '/' . $fileName;
    }
    
    /**
     * Enregistre un fichier téléchargé de manière sécurisée
     * 
     * @param array $fileData Données du fichier téléchargé ($_FILES['field'])
     * @param string $destinationDir Répertoire de destination
     * @param array $options Options supplémentaires
     *                      - add_timestamp: Ajouter un timestamp au nom (défaut: true)
     *                      - nested_storage: Utiliser une structure de dossiers imbriqués (défaut: true)
     * @return array Résultat de l'opération avec 'success', 'file_path' et éventuellement 'error'
     */
    public static function storeUploadedFile($fileData, $destinationDir, $options = [])
    {
        // Options par défaut
        $options = array_merge([
            'add_timestamp' => true,
            'nested_storage' => true
        ], $options);
        
        // Vérifier si le répertoire de destination existe
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                return [
                    'success' => false,
                    'error' => 'Impossible de créer le répertoire de destination',
                    'category' => 'file_access',
                    'advice' => 'Vérifiez les permissions du serveur pour créer des répertoires.'
                ];
            }
        }
        
        // Vérifier les erreurs de téléchargement
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite définie dans php.ini (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite définie dans le formulaire HTML (MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
                UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
            ];
            
            return [
                'success' => false,
                'error' => $errorMessages[$fileData['error']] ?? 'Erreur inconnue lors du téléchargement',
                'category' => 'upload_error',
                'advice' => 'Vérifiez que le fichier n\'est pas trop volumineux et réessayez.'
            ];
        }
        
        // Générer un nom de fichier sécurisé
        $secureFileName = self::secureFileName($fileData['name'], $options['add_timestamp']);
        
        // Déterminer le chemin final du fichier
        if ($options['nested_storage']) {
            $filePath = self::getSecureStoragePath($destinationDir, $secureFileName, true);
        } else {
            $filePath = $destinationDir . '/' . $secureFileName;
        }
        
        // Déplacer le fichier téléchargé
        if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
            return [
                'success' => false,
                'error' => 'Impossible de déplacer le fichier téléchargé',
                'category' => 'file_access',
                'advice' => 'Vérifiez les permissions du serveur pour les fichiers téléchargés.'
            ];
        }
        
        // Définir les permissions correctes
        chmod($filePath, 0644);
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'original_name' => $fileData['name'],
            'secure_name' => $secureFileName,
            'size' => $fileData['size'],
            'mime_type' => $fileData['type']
        ];
    }
}
