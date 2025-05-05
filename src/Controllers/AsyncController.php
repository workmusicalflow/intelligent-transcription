<?php

namespace Controllers;

use Services\AsyncProcessingService;
use Utils\ResponseUtils;
use Middleware\ValidationMiddleware;

/**
 * Contrôleur pour les opérations asynchrones
 */
class AsyncController
{
    /**
     * @var AsyncProcessingService
     */
    private $asyncService;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->asyncService = new AsyncProcessingService();
    }
    
    /**
     * Lance une tâche asynchrone de transcription de fichier audio
     * 
     * @param array $request Données de la requête
     * @return array Résultat du lancement de la tâche
     */
    public function startFileProcessing($request)
    {
        // Valider les entrées
        $validation = ValidationMiddleware::validateRoute('TranscriptionController', 'uploadFile', $request);
        
        if (!$validation['success']) {
            return ResponseUtils::error(400, 'Données invalides', $validation['errors']);
        }
        
        // Extraire les données validées
        $data = $validation['sanitized'];
        
        // Récupérer le fichier et les paramètres
        $file = $data['audio_file'];
        $language = $data['language'] ?? 'auto';
        $forceLanguage = $data['force_language'] ?? false;
        
        // Préparation des répertoires
        $uploadDir = BASE_DIR . '/uploads';
        $tempDir = BASE_DIR . '/temp_audio';
        
        // Stocker le fichier avec un nom sécurisé dans une structure de répertoires imbriqués
        $fileStorage = \Utils\FileUtils::storeUploadedFile($file, $uploadDir, [
            'add_timestamp' => true,
            'nested_storage' => true
        ]);
        
        // Vérifier si le stockage a réussi
        if (!$fileStorage['success']) {
            return ResponseUtils::error(500, $fileStorage['error'], [
                'category' => $fileStorage['category'] ?? 'file_access',
                'advice' => $fileStorage['advice'] ?? 'Vérifiez les permissions du répertoire d\'upload et l\'espace disque disponible.'
            ]);
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
            'force_language' => $forceLanguage
        ];
        
        // Créer la tâche asynchrone
        $result = $this->asyncService->createFileProcessingTask($filePath, $tempDir, $language, $forceLanguage, $metadata);
        
        // Vérifier le résultat
        if (!$result['success']) {
            return ResponseUtils::error(500, $result['error'], [
                'category' => $result['category'] ?? 'unknown',
                'advice' => $result['advice'] ?? 'Veuillez réessayer ultérieurement.'
            ]);
        }
        
        // Retourner le résultat
        return ResponseUtils::success([
            'job_id' => $result['job_id'],
            'message' => $result['message']
        ]);
    }
    
    /**
     * Lance une tâche asynchrone de transcription de vidéo YouTube
     * 
     * @param array $request Données de la requête
     * @return array Résultat du lancement de la tâche
     */
    public function startYouTubeProcessing($request)
    {
        // Valider les entrées
        $validation = ValidationMiddleware::validateRoute('YouTubeController', 'downloadAndTranscribe', $request);
        
        if (!$validation['success']) {
            return ResponseUtils::error(400, 'Données invalides', $validation['errors']);
        }
        
        // Extraire les données validées
        $data = $validation['sanitized'];
        
        // Récupérer les paramètres
        $youtubeUrl = $data['youtube_url'];
        $language = $data['language'] ?? 'auto';
        $forceLanguage = $data['force_language'] ?? false;
        
        // Métadonnées pour le suivi du traitement
        $urlValidation = \Utils\ValidationUtils::validateYoutubeUrl($youtubeUrl);
        $metadata = [
            'youtube_url' => $youtubeUrl,
            'youtube_id' => $urlValidation['video_id'],
            'language' => $language,
            'force_language' => $forceLanguage
        ];
        
        // Créer la tâche asynchrone
        $result = $this->asyncService->createYouTubeProcessingTask($youtubeUrl, $language, $forceLanguage, $metadata);
        
        // Vérifier le résultat
        if (!$result['success']) {
            return ResponseUtils::error(500, $result['error'], [
                'category' => $result['category'] ?? 'unknown',
                'advice' => $result['advice'] ?? 'Veuillez réessayer ultérieurement.'
            ]);
        }
        
        // Retourner le résultat
        return ResponseUtils::success([
            'job_id' => $result['job_id'],
            'message' => $result['message']
        ]);
    }
    
    /**
     * Vérifie l'état d'une tâche
     * 
     * @param array $request Données de la requête
     * @return array État de la tâche
     */
    public function checkTaskStatus($request)
    {
        // Valider l'ID de tâche
        if (!isset($request['job_id']) || empty($request['job_id'])) {
            return ResponseUtils::error(400, 'ID de tâche manquant', [
                'category' => 'validation',
                'advice' => 'Veuillez fournir un ID de tâche valide.'
            ]);
        }
        
        $jobId = $request['job_id'];
        
        // Récupérer l'état de la tâche
        $result = $this->asyncService->getTaskStatus($jobId);
        
        // Vérifier le résultat
        if (!$result['success']) {
            return ResponseUtils::error(404, $result['error'], [
                'category' => $result['category'] ?? 'unknown',
                'advice' => $result['advice'] ?? 'Vérifiez l\'ID de la tâche et réessayez.'
            ]);
        }
        
        // Extraire les informations pertinentes
        $status = null;
        $progress = 0;
        $resultId = null;
        $error = null;
        
        if (isset($result['job'])) {
            $job = $result['job'];
            $status = $job['status'];
            $progress = $job['progress'];
            $resultId = $job['result_id'] ?? null;
            $error = $job['error'] ?? null;
        } elseif (isset($result['task'])) {
            $task = $result['task'];
            $status = $task['status'];
            $progress = $task['result']['progress'] ?? 0;
            $resultId = $task['result']['result_id'] ?? null;
            $error = $task['result']['error'] ?? null;
        }
        
        // Retourner les informations d'état
        return ResponseUtils::success([
            'job_id' => $jobId,
            'status' => $status,
            'progress' => $progress,
            'result_id' => $resultId,
            'error' => $error
        ]);
    }
    
    /**
     * Annule une tâche en cours
     * 
     * @param array $request Données de la requête
     * @return array Résultat de l'annulation
     */
    public function cancelTask($request)
    {
        // Valider l'ID de tâche
        if (!isset($request['job_id']) || empty($request['job_id'])) {
            return ResponseUtils::error(400, 'ID de tâche manquant', [
                'category' => 'validation',
                'advice' => 'Veuillez fournir un ID de tâche valide.'
            ]);
        }
        
        $jobId = $request['job_id'];
        
        // Annuler la tâche
        $result = $this->asyncService->cancelTask($jobId);
        
        // Vérifier le résultat
        if (!$result['success']) {
            return ResponseUtils::error(500, $result['error'], [
                'category' => $result['category'] ?? 'unknown',
                'advice' => $result['advice'] ?? 'Vérifiez l\'ID de la tâche et réessayez.'
            ]);
        }
        
        // Retourner le résultat
        return ResponseUtils::success([
            'message' => $result['message']
        ]);
    }
}