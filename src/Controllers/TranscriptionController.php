<?php

namespace Controllers;

use Services\TranscriptionService;
use Services\YouTubeService;
use Services\AsyncProcessingService;
use Services\AuthService;
use Utils\ResponseUtils;
use Middleware\ValidationMiddleware;

/**
 * Contrôleur pour la transcription audio
 */
class TranscriptionController
{
    /**
     * @var TranscriptionService
     */
    private $transcriptionService;

    /**
     * @var YouTubeService
     */
    private $youtubeService;
    
    /**
     * @var AsyncProcessingService
     */
    private $asyncService;

    /**
     * Taille limite en octets pour le traitement synchrone
     * Les fichiers plus grands seront traités en asynchrone
     */
    const SYNC_PROCESSING_SIZE_LIMIT = 10 * 1024 * 1024; // 10 MB
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->transcriptionService = new TranscriptionService();
        $this->youtubeService = new YouTubeService();
        $this->asyncService = new AsyncProcessingService();
        
        // Initialize authentication
        AuthService::init();
    }

    /**
     * Traite une requête de transcription de fichier audio
     */
    public function handleFileUpload()
    {
        // Enregistrer les informations de débogage
        $debug_info = [
            'FILES' => $_FILES,
            'POST' => $_POST,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
        ];
        file_put_contents(BASE_DIR . '/debug_upload.log', print_r($debug_info, true));

        // Valider les entrées
        $validation = ValidationMiddleware::validateRoute(
            'TranscriptionController', 
            'uploadFile', 
            array_merge($_POST, $_FILES)
        );
        
        if (!$validation['success']) {
            $errorMessage = 'Validation échouée: ';
            foreach ($validation['errors'] as $field => $error) {
                $errorMessage .= "$field - $error; ";
            }
            ResponseUtils::redirectWithError('validation', $errorMessage);
        }
        
        // Extraire les données validées
        $data = $validation['sanitized'];
        
        // Récupérer les paramètres
        $file = $data['audio_file'];
        $language = $data['language'] ?? 'auto';
        $forceLanguage = $data['force_language'] ?? false;
        
        // Récupérer l'ID utilisateur si authentifié
        $userId = null;
        if (AuthService::isAuthenticated()) {
            $userId = AuthService::getCurrentUser()->getId();
        }
        
        // Déterminer si le traitement doit être asynchrone
        $useAsync = $file['size'] > self::SYNC_PROCESSING_SIZE_LIMIT || isset($_POST['async']);
        
        if ($useAsync) {
            // Préparation des répertoires
            $uploadDir = BASE_DIR . '/uploads';
            $tempDir = BASE_DIR . '/temp_audio';
            
            // Stocker le fichier avec un nom sécurisé dans une structure de répertoires imbriqués
            $fileStorage = FileUtils::storeUploadedFile($file, $uploadDir, [
                'add_timestamp' => true,
                'nested_storage' => true
            ]);
            
            // Vérifier si le stockage a réussi
            if (!$fileStorage['success']) {
                ResponseUtils::redirectWithError('upload', $fileStorage['error']);
            }
            
            // Récupérer le chemin du fichier stocké
            $filePath = $fileStorage['file_path'];
            
            // Métadonnées pour le suivi du traitement
            $metadata = [
                'filename' => basename($file['name']),
                'original_name' => $file['name'],
                'filesize' => $file['size'],
                'file_type' => $file['type'],
                'language' => $language,
                'force_language' => $forceLanguage,
                'user_id' => $userId
            ];
            
            // Créer la tâche asynchrone
            $result = $this->asyncService->createFileProcessingTask($filePath, $tempDir, $language, $forceLanguage, $metadata);
            
            // Vérifier le résultat
            if (!$result['success']) {
                ResponseUtils::redirectWithError('async', $result['error']);
            }
            
            // Rediriger vers la page de suivi du traitement
            ResponseUtils::redirect('processing.php?job_id=' . $result['job_id'] . '&type=file');
        } else {
            // Traitement synchrone pour les petits fichiers
            $result = $this->transcriptionService->processUploadedFile($file, $language, $forceLanguage, $userId);
        }

        // Vérifier si la transcription a réussi
        if (!$result['success']) {
            ResponseUtils::redirectWithError('transcription', $result['error']);
        }

        // Rediriger vers la page de résultat
        ResponseUtils::redirect('result.php?id=' . $result['result_id']);
    }

    /**
     * Traite une requête de transcription de vidéo YouTube
     */
    public function handleYouTubeDownload()
    {
        // Valider les entrées
        $validation = ValidationMiddleware::validateRoute(
            'YouTubeController', 
            'downloadAndTranscribe', 
            $_POST
        );
        
        if (!$validation['success']) {
            $errorMessage = 'Validation échouée: ';
            foreach ($validation['errors'] as $field => $error) {
                $errorMessage .= "$field - $error; ";
            }
            ResponseUtils::redirectWithError('validation', $errorMessage);
        }
        
        // Extraire les données validées
        $data = $validation['sanitized'];
        
        // Récupérer les paramètres
        $youtubeUrl = $data['youtube_url'];
        $language = $data['language'] ?? 'auto';
        $forceLanguage = $data['force_language'] ?? false;
        
        // Récupérer l'ID utilisateur si authentifié
        $userId = null;
        if (AuthService::isAuthenticated()) {
            $userId = AuthService::getCurrentUser()->getId();
        }
        
        // Les téléchargements YouTube sont toujours traités de façon asynchrone
        // car nous ne connaissons pas la taille du fichier à l'avance
        $useAsync = true;
        
        if ($useAsync) {
            // Valider l'URL YouTube
            $urlValidation = \Utils\ValidationUtils::validateYoutubeUrl($youtubeUrl);
            
            // Métadonnées pour le suivi du traitement
            $metadata = [
                'youtube_url' => $youtubeUrl,
                'youtube_id' => $urlValidation['video_id'],
                'language' => $language,
                'force_language' => $forceLanguage,
                'user_id' => $userId
            ];
            
            // Créer la tâche asynchrone
            $result = $this->asyncService->createYouTubeProcessingTask($youtubeUrl, $language, $forceLanguage, $metadata);
            
            // Vérifier le résultat
            if (!$result['success']) {
                ResponseUtils::redirectWithError('async', $result['error']);
            }
            
            // Rediriger vers la page de suivi du traitement
            ResponseUtils::redirect('processing.php?job_id=' . $result['job_id'] . '&type=youtube');
        } else {
            // Télécharger et transcrire la vidéo (ce bloc n'est jamais exécuté pour YouTube)
            $result = $this->youtubeService->downloadAndTranscribe($youtubeUrl, $language, $forceLanguage, $userId);
        }

        // Vérifier si la transcription a réussi
        if (!$result['success']) {
            ResponseUtils::redirectWithError('youtube', $result['error']);
        }

        // Rediriger vers la page de résultat
        ResponseUtils::redirect('result.php?id=' . $result['result_id']);
    }

    /**
     * Affiche le statut d'un traitement en cours
     * 
     * @return array Données de statut pour l'affichage
     */
    public function showProcessingStatus()
    {
        // Vérifier si un ID de job a été fourni
        if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
            ResponseUtils::redirectWithError('missing_id', 'ID de traitement manquant');
        }
        
        $jobId = $_GET['job_id'];
        $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';
        
        // Récupérer le type de traitement (file ou youtube)
        $type = $_GET['type'] ?? 'file';
        
        // Récupérer le statut du job
        $status = $this->processingService->getStatus($jobId);
        
        if (!$status) {
            ResponseUtils::redirectWithError('job_not_found', 'Traitement non trouvé');
        }
        
        // Extraire les informations nécessaires pour l'affichage
        $viewData = [
            'job_id' => $jobId,
            'status' => $status['status'],
            'progress' => $status['progress'],
            'current_step' => $status['current_step'],
            'file_type' => $type,
            'force_refresh' => $forceRefresh
        ];
        
        // Ajouter l'ID de résultat si disponible
        if (isset($status['result_id'])) {
            $viewData['result_id'] = $status['result_id'];
        }
        
        // Ajouter les informations d'erreur si le traitement a échoué
        if ($status['status'] === 'error') {
            $viewData['error_message'] = $status['error'];
            $viewData['error_category'] = $status['error_category'] ?? 'unknown';
            $viewData['error_advice'] = $status['error_advice'] ?? 'Veuillez réessayer ultérieurement.';
        }
        
        return $viewData;
    }
    
    /**
     * Affiche le résultat d'une transcription
     */
    public function showResult()
    {
        // Valider l'ID de résultat
        $validation = ValidationMiddleware::validateRoute(
            'TranscriptionController', 
            'getResult', 
            $_GET
        );
        
        if (!$validation['success']) {
            ResponseUtils::redirectWithError('invalid_id', 'ID de résultat invalide ou manquant');
        }
        
        // Extraire l'ID validé
        $resultId = $validation['sanitized']['result_id'];

        // Récupérer le résultat
        $result = $this->transcriptionService->getTranscriptionResult($resultId);

        // Vérifier si le résultat a été trouvé
        if (!$result['success']) {
            ResponseUtils::redirectWithError('result_not_found');
        }

        // Retourner le résultat pour l'affichage
        return $result;
    }

    /**
     * Télécharge un résultat de transcription
     */
    public function downloadResult()
    {
        // Valider l'ID de résultat
        $validation = ValidationMiddleware::validateRoute(
            'TranscriptionController', 
            'getResult', 
            $_GET
        );
        
        if (!$validation['success']) {
            ResponseUtils::redirectWithError('invalid_id', 'ID de résultat invalide ou manquant');
        }
        
        // Extraire l'ID validé
        $resultId = $validation['sanitized']['result_id'];

        // Récupérer le résultat
        $result = $this->transcriptionService->getTranscriptionResult($resultId);

        // Vérifier si le résultat a été trouvé
        if (!$result['success']) {
            ResponseUtils::redirectWithError('result_not_found');
        }

        // Définir les en-têtes pour le téléchargement
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="transcription_' . $resultId . '.txt"');
        header('Content-Length: ' . strlen($result['text']));

        // Envoyer le contenu
        echo $result['text'];
        exit;
    }
}
