<?php

namespace Services;

use Utils\FileUtils;
use Utils\ResponseUtils;
use Database\DatabaseManager;

/**
 * Service de transcription audio
 */
class TranscriptionService
{
    /**
     * Indique si le service utilise la base de données
     * 
     * @var bool
     */
    private $useDatabase;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->useDatabase = defined('USE_DATABASE') ? USE_DATABASE : false;
    }
    
    /**
     * Prétraite un fichier audio pour réduire sa taille
     * 
     * @param string $filePath Chemin du fichier audio
     * @param string $outputDir Répertoire de sortie
     * @param int $targetSizeMb Taille cible en Mo
     * @return array Résultat du prétraitement
     */
    public function preprocessAudio($filePath, $outputDir, $targetSizeMb = 24)
    {
        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => "Le fichier $filePath n'existe pas"
            ];
        }

        // Créer le répertoire de sortie si nécessaire
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Exécuter le script Python de prétraitement
        $pythonPath = PYTHON_PATH;
        $preprocessScript = BASE_DIR . '/preprocess_audio.py';

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($preprocessScript) . ' ' .
            '--file=' . escapeshellarg($filePath) . ' ' .
            '--output_dir=' . escapeshellarg($outputDir) . ' ' .
            '--target_size_mb=' . escapeshellarg($targetSizeMb);

        // Utiliser notre utilitaire d'exécution Python
        $result = \Utils\PythonErrorUtils::executePythonProcess($command, 'prétraitement audio');
        
        // Si l'opération a échoué, retourner l'erreur
        if (!isset($result['success']) || !$result['success']) {
            return $result;
        }

        return $result;
    }

    /**
     * Transcrit un fichier audio
     * 
     * @param string $filePath Chemin du fichier audio
     * @param string $outputPath Chemin du fichier de sortie (pour compatibilité avec ancienne version)
     * @param string|null $language Code de langue (fr, en, etc.)
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @param string|null $filename Nom de fichier original
     * @param int|null $fileSize Taille du fichier en octets
     * @param string|null $youtubeUrl URL YouTube si applicable
     * @param string|null $youtubeId ID YouTube si applicable
     * @param int|null $userId ID de l'utilisateur
     * @return array Résultat de la transcription
     */
    public function transcribeAudio($filePath, $outputPath = null, $language = null, $forceLanguage = false, $filename = null, $fileSize = null, $youtubeUrl = null, $youtubeId = null, $userId = null)
    {
        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => "Le fichier $filePath n'existe pas"
            ];
        }

        // Exécuter le script Python de transcription
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/transcribe.py';

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--file=' . escapeshellarg($filePath);
            
        // Ajouter le chemin de sortie s'il est fourni
        if ($outputPath) {
            $command .= ' --output=' . escapeshellarg($outputPath);
        }

        // Ajouter le paramètre de langue
        if ($language === 'auto' || $language === null) {
            $command .= ' --language=""';
        } else {
            $command .= ' --language=' . escapeshellarg($language);

            // Ajouter le paramètre pour forcer la traduction dans la langue spécifiée
            if ($forceLanguage) {
                $command .= ' --force-language';
            }
        }

        // Utiliser notre utilitaire d'exécution Python avec gestion des erreurs standardisée
        $result = \Utils\PythonErrorUtils::executePythonProcess(
            $command, 
            'transcription', 
            $outputPath
        );
        
        // Si l'opération a échoué, retourner l'erreur
        if (!isset($result['success']) || !$result['success']) {
            return $result;
        }
        
        // Générer un ID unique pour le résultat
        $resultId = FileUtils::generateUniqueId();
        $result['id'] = $resultId;
        
        // Si on utilise la base de données, stocker le résultat
        if ($this->useDatabase) {
            // Obtenir la durée audio si disponible
            $duration = null;
            if (file_exists($filePath)) {
                $duration = $this->getAudioDuration($filePath);
            }
            
            try {
                // Préparer les données pour l'insertion
                $params = [
                    ':id' => $resultId,
                    ':file_name' => $filename ?? basename($filePath),
                    ':file_path' => $filePath,
                    ':text' => $result['text'],
                    ':language' => $result['language'] ?? 'unknown',
                    ':original_text' => $result['original_text'] ?? null,
                    ':youtube_url' => $youtubeUrl,
                    ':youtube_id' => $youtubeId,
                    ':file_size' => $fileSize ?? (file_exists($filePath) ? filesize($filePath) : null),
                    ':duration' => $duration,
                    ':preprocessed_path' => $filePath,
                    ':user_id' => $userId
                ];
                
                // Insérer dans la base de données
                $sql = "INSERT INTO transcriptions (
                            id, file_name, file_path, text, language, original_text, 
                            youtube_url, youtube_id, file_size, duration, preprocessed_path, user_id
                        ) VALUES (
                            :id, :file_name, :file_path, :text, :language, :original_text,
                            :youtube_url, :youtube_id, :file_size, :duration, :preprocessed_path, :user_id
                        )";
                DatabaseManager::query($sql, $params);
                
                // Si on a un chemin de sortie, sauvegarder également dans un fichier pour rétrocompatibilité
                if ($outputPath) {
                    file_put_contents($outputPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } catch (\Exception $e) {
                // En cas d'erreur avec la base de données, revenir au comportement de fichier
                if ($outputPath) {
                    file_put_contents($outputPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                
                // Enregistrer l'erreur dans le log
                error_log('Erreur lors de l\'insertion dans la base de données: ' . $e->getMessage());
            }
        } else {
            // Comportement d'origine: sauvegarder dans un fichier
            if ($outputPath) {
                file_put_contents($outputPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        return $result;
    }

    /**
     * Traite un fichier audio téléchargé
     * 
     * @param array $file Fichier téléchargé ($_FILES['audio_file'])
     * @param string $language Code de langue
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @param int|null $userId ID de l'utilisateur
     * @return array Résultat du traitement
     */
    public function processUploadedFile($file, $language = 'auto', $forceLanguage = false, $userId = null)
    {
        // Valider les paramètres de transcription
        $paramsValidation = \Utils\ValidationUtils::validateTranscriptionParams([
            'language' => $language,
            'force_language' => $forceLanguage
        ]);
        
        if (!$paramsValidation['valid']) {
            return [
                'success' => false,
                'error' => $paramsValidation['error'],
                'category' => 'validation',
                'advice' => 'Veuillez vérifier les paramètres de transcription fournis.'
            ];
        }
        
        // Extraire les paramètres validés
        $language = $paramsValidation['sanitized']['language'];
        $forceLanguage = $paramsValidation['sanitized']['force_language'];
        
        // Valider le fichier téléchargé
        $fileValidation = \Utils\ValidationUtils::validateUploadedFile($file, [
            'max_size' => MAX_UPLOAD_SIZE_BYTES,
            'types' => array_merge(
                \Utils\ValidationUtils::SUPPORTED_AUDIO_TYPES,
                \Utils\ValidationUtils::SUPPORTED_VIDEO_TYPES
            )
        ]);
        
        if (!$fileValidation['valid']) {
            return [
                'success' => false,
                'error' => $fileValidation['error'],
                'category' => 'validation',
                'advice' => 'Veuillez fournir un fichier audio ou vidéo valide respectant les contraintes de taille et de format.'
            ];
        }

        // Créer le répertoire de téléchargement si nécessaire
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }

        // Générer un nom de fichier sécurisé et unique
        $filename = \Utils\ValidationUtils::generateSecureFilename($file['name'], $fileValidation['mime_type']);
        $filePath = UPLOAD_DIR . '/' . $filename;

        // Déplacer le fichier téléchargé
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => false,
                'error' => 'Impossible de déplacer le fichier téléchargé',
                'category' => 'file_access',
                'advice' => 'Vérifiez les permissions du répertoire d\'upload et l\'espace disque disponible.'
            ];
        }

        // Prétraiter le fichier audio
        $preprocessResult = $this->preprocessAudio($filePath, TEMP_AUDIO_DIR);
        if (!$preprocessResult['success']) {
            return [
                'success' => false,
                'error' => $preprocessResult['error']
            ];
        }

        // Utiliser le fichier prétraité pour la transcription
        $preprocessedFilePath = $preprocessResult['output_file'];

        // Créer le répertoire de résultats si nécessaire
        if (!is_dir(RESULT_DIR)) {
            mkdir(RESULT_DIR, 0777, true);
        }

        // Générer un chemin pour le fichier résultat (pour compatibilité)
        $resultId = FileUtils::generateUniqueId();
        $resultPath = RESULT_DIR . '/' . $resultId . '.json';

        // Transcrire le fichier audio avec toutes les informations
        $transcriptionResult = $this->transcribeAudio(
            $preprocessedFilePath, 
            $resultPath, 
            $language, 
            $forceLanguage,
            $filename,
            $file['size'],
            null,  // YouTube URL
            null,  // YouTube ID
            $userId
        );
        
        if (!$transcriptionResult['success']) {
            return [
                'success' => false,
                'error' => $transcriptionResult['error']
            ];
        }

        return [
            'success' => true,
            'result_id' => $transcriptionResult['id'] ?? $resultId,
            'text' => $transcriptionResult['text'],
            'language' => $transcriptionResult['language']
        ];
    }

    /**
     * Récupère un résultat de transcription
     * 
     * @param string $resultId ID du résultat
     * @return array Résultat de la transcription
     */
    public function getTranscriptionResult($resultId)
    {
        // Si on utilise la base de données
        if ($this->useDatabase) {
            try {
                $sql = "SELECT * FROM transcriptions WHERE id = :id LIMIT 1";
                $stmt = DatabaseManager::query($sql, [':id' => $resultId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$result) {
                    // Si pas de résultat en DB, essayer via le fichier (pour rétrocompatibilité)
                    return $this->getTranscriptionResultFromFile($resultId);
                }
                
                // Formater le résultat pour correspondre au format attendu
                return [
                    'success' => true,
                    'id' => $result['id'],
                    'text' => $result['text'],
                    'language' => $result['language'],
                    'original_text' => $result['original_text'],
                    'youtube_url' => $result['youtube_url'],
                    'youtube_id' => $result['youtube_id'],
                    'created_at' => $result['created_at'],
                    'file_size' => $result['file_size'],
                    'duration' => $result['duration'],
                    'file_name' => $result['file_name']
                ];
            } catch (\Exception $e) {
                // En cas d'erreur avec la base de données, essayer via le fichier
                error_log('Erreur lors de la récupération dans la base de données: ' . $e->getMessage());
                return $this->getTranscriptionResultFromFile($resultId);
            }
        } else {
            // Comportement d'origine: récupérer depuis un fichier
            return $this->getTranscriptionResultFromFile($resultId);
        }
    }
    
    /**
     * Récupère un résultat de transcription depuis un fichier JSON
     * Méthode utilisée pour rétrocompatibilité
     * 
     * @param string $resultId ID du résultat
     * @return array Résultat de la transcription
     */
    private function getTranscriptionResultFromFile($resultId)
    {
        $resultPath = RESULT_DIR . '/' . $resultId . '.json';

        if (!file_exists($resultPath)) {
            return [
                'success' => false,
                'error' => 'Résultat non trouvé'
            ];
        }

        $result = json_decode(file_get_contents($resultPath), true);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => 'Résultat invalide'
            ];
        }
        
        // Assurons-nous que l'ID est défini
        if (!isset($result['id'])) {
            $result['id'] = $resultId;
        }

        return $result;
    }
    
    /**
     * Récupère la durée d'un fichier audio/vidéo en secondes
     * 
     * @param string $filePath Chemin du fichier
     * @return int|null Durée en secondes ou null en cas d'erreur
     */
    private function getAudioDuration($filePath)
    {
        try {
            // Utiliser ffprobe pour obtenir la durée
            $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath);
            $output = shell_exec($command);
            
            if ($output !== null) {
                // Convertir en entier (secondes)
                return (int)floatval($output);
            }
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération de la durée: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Liste les dernières transcriptions
     * 
     * @param int $limit Nombre maximum de résultats
     * @param int $offset Décalage pour la pagination
     * @param int|null $userId ID de l'utilisateur pour filtrer les résultats
     * @return array Liste des transcriptions
     */
    public function listTranscriptions($limit = 10, $offset = 0, $userId = null)
    {
        if ($this->useDatabase) {
            try {
                $params = [
                    ':limit' => $limit,
                    ':offset' => $offset
                ];
                
                if ($userId !== null) {
                    // Si un utilisateur est spécifié, filtrer par son ID
                    $sql = "SELECT * FROM transcriptions WHERE user_id = :user_id OR user_id IS NULL 
                            ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
                    $params[':user_id'] = $userId;
                } else {
                    // Sinon, récupérer toutes les transcriptions
                    $sql = "SELECT * FROM transcriptions ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
                }
                
                $stmt = DatabaseManager::query($sql, $params);
                $transcriptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                return [
                    'success' => true,
                    'transcriptions' => $transcriptions,
                    'count' => count($transcriptions)
                ];
            } catch (\Exception $e) {
                error_log('Erreur lors de la récupération des transcriptions: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => 'Erreur lors de la récupération des transcriptions'
                ];
            }
        } else {
            // Fallback: lire les fichiers du répertoire de résultats
            $files = glob(RESULT_DIR . '/*.json');
            $files = array_slice($files, $offset, $limit);
            
            $transcriptions = [];
            foreach ($files as $file) {
                $resultId = pathinfo($file, PATHINFO_FILENAME);
                $result = $this->getTranscriptionResultFromFile($resultId);
                if ($result['success']) {
                    $transcriptions[] = $result;
                }
            }
            
            return [
                'success' => true,
                'transcriptions' => $transcriptions,
                'count' => count($transcriptions)
            ];
        }
    }
    
    /**
     * Supprime une transcription
     * 
     * @param string $resultId ID du résultat
     * @return array Résultat de la suppression
     */
    public function deleteTranscription($resultId)
    {
        if ($this->useDatabase) {
            try {
                // Récupérer les informations du fichier avant de supprimer
                $sql = "SELECT file_path, preprocessed_path FROM transcriptions WHERE id = :id LIMIT 1";
                $stmt = DatabaseManager::query($sql, [':id' => $resultId]);
                $fileInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Supprimer de la base de données
                $sql = "DELETE FROM transcriptions WHERE id = :id";
                DatabaseManager::query($sql, [':id' => $resultId]);
                
                // Supprimer les fichiers associés si ils existent
                if ($fileInfo) {
                    if (!empty($fileInfo['file_path']) && file_exists($fileInfo['file_path'])) {
                        unlink($fileInfo['file_path']);
                    }
                    if (!empty($fileInfo['preprocessed_path']) && file_exists($fileInfo['preprocessed_path'])) {
                        unlink($fileInfo['preprocessed_path']);
                    }
                }
                
                // Supprimer également le fichier JSON (pour rétrocompatibilité)
                $resultPath = RESULT_DIR . '/' . $resultId . '.json';
                if (file_exists($resultPath)) {
                    unlink($resultPath);
                }
                
                return [
                    'success' => true,
                    'message' => 'Transcription supprimée avec succès'
                ];
            } catch (\Exception $e) {
                error_log('Erreur lors de la suppression de la transcription: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => 'Erreur lors de la suppression de la transcription'
                ];
            }
        } else {
            // Fallback: supprimer le fichier JSON
            $resultPath = RESULT_DIR . '/' . $resultId . '.json';
            if (file_exists($resultPath)) {
                unlink($resultPath);
                return [
                    'success' => true,
                    'message' => 'Transcription supprimée avec succès'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Transcription non trouvée'
                ];
            }
        }
    }
}