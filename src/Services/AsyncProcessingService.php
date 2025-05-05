<?php

namespace Services;

use Utils\FileUtils;

/**
 * Service pour gérer les traitements asynchrones
 */
class AsyncProcessingService
{
    /**
     * Répertoire pour les fichiers de background tasks
     */
    private $tasksDir;
    
    /**
     * Répertoire pour les logs d'exécution
     */
    private $logsDir;
    
    /**
     * Instance du service de traitement
     */
    private $processingService;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->tasksDir = BASE_DIR . '/cache/jobs';
        $this->logsDir = BASE_DIR . '/logs/async';
        $this->processingService = new ProcessingService();
        
        // Créer les répertoires s'ils n'existent pas
        if (!is_dir($this->tasksDir)) {
            mkdir($this->tasksDir, 0755, true);
        }
        
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0755, true);
        }
    }
    
    /**
     * Crée une tâche asynchrone pour le traitement d'un fichier audio
     * 
     * @param string $filePath Chemin du fichier à traiter
     * @param string $outputDir Répertoire de sortie pour le fichier prétraité
     * @param string $language Code de langue
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @param array $metadata Métadonnées additionnelles
     * @return array Résultat de la création de tâche
     */
    public function createFileProcessingTask($filePath, $outputDir, $language = 'auto', $forceLanguage = false, $metadata = [])
    {
        // Valider les paramètres
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'Le fichier à traiter n\'existe pas',
                'category' => 'file_access',
                'advice' => 'Vérifiez le chemin du fichier et réessayez.'
            ];
        }
        
        // Créer le répertoire de sortie si nécessaire
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Générer un ID unique pour la tâche
        $jobId = $this->processingService->startJob('file', array_merge($metadata, [
            'filePath' => $filePath,
            'outputDir' => $outputDir,
            'language' => $language,
            'force_language' => $forceLanguage
        ]));
        
        // Créer la tâche en arrière-plan
        $taskData = [
            'id' => $jobId,
            'type' => 'file_processing',
            'created_at' => time(),
            'status' => 'pending',
            'parameters' => [
                'filePath' => $filePath,
                'outputDir' => $outputDir,
                'language' => $language,
                'forceLanguage' => $forceLanguage
            ],
            'metadata' => $metadata
        ];
        
        // Sauvegarder la tâche dans un fichier
        $taskFile = $this->tasksDir . '/' . $jobId . '.task';
        file_put_contents($taskFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Démarrer le traitement en arrière-plan (fork ou via une commande système)
        $this->executeTaskInBackground($jobId);
        
        return [
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Tâche de traitement créée avec succès'
        ];
    }
    
    /**
     * Crée une tâche asynchrone pour le traitement d'une vidéo YouTube
     * 
     * @param string $youtubeUrl URL YouTube
     * @param string $language Code de langue
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @param array $metadata Métadonnées additionnelles
     * @return array Résultat de la création de tâche
     */
    public function createYouTubeProcessingTask($youtubeUrl, $language = 'auto', $forceLanguage = false, $metadata = [])
    {
        // Valider l'URL YouTube
        $urlValidation = \Utils\ValidationUtils::validateYoutubeUrl($youtubeUrl);
        
        if (!$urlValidation['valid']) {
            return [
                'success' => false,
                'error' => $urlValidation['error'],
                'category' => 'validation',
                'advice' => 'Veuillez fournir une URL YouTube valide.'
            ];
        }
        
        // Générer un ID unique pour la tâche
        $jobId = $this->processingService->startJob('youtube', array_merge($metadata, [
            'youtubeUrl' => $youtubeUrl,
            'youtubeId' => $urlValidation['video_id'],
            'language' => $language,
            'force_language' => $forceLanguage
        ]));
        
        // Créer la tâche en arrière-plan
        $taskData = [
            'id' => $jobId,
            'type' => 'youtube_processing',
            'created_at' => time(),
            'status' => 'pending',
            'parameters' => [
                'youtubeUrl' => $youtubeUrl,
                'youtubeId' => $urlValidation['video_id'],
                'language' => $language,
                'forceLanguage' => $forceLanguage
            ],
            'metadata' => $metadata
        ];
        
        // Sauvegarder la tâche dans un fichier
        $taskFile = $this->tasksDir . '/' . $jobId . '.task';
        file_put_contents($taskFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Démarrer le traitement en arrière-plan (fork ou via une commande système)
        $this->executeTaskInBackground($jobId);
        
        return [
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Tâche de traitement YouTube créée avec succès'
        ];
    }
    
    /**
     * Exécute une tâche en arrière-plan
     * 
     * @param string $jobId ID de la tâche
     * @return bool Succès de l'opération
     */
    private function executeTaskInBackground($jobId)
    {
        // Chemin du script worker
        $workerScript = BASE_DIR . '/worker.php';
        
        // Créer le fichier worker si nécessaire
        if (!file_exists($workerScript)) {
            $this->createWorkerScript($workerScript);
        }
        
        // Commande d'exécution PHP en arrière-plan
        $phpPath = PHP_BINARY;
        $logFile = $this->logsDir . '/' . $jobId . '.log';
        
        // Systèmes Unix-like: utiliser nohup pour détacher le processus
        if (stripos(PHP_OS, 'WIN') === false) {
            $command = sprintf(
                'nohup %s %s process_task %s > %s 2>&1 & echo $!',
                escapeshellarg($phpPath),
                escapeshellarg($workerScript),
                escapeshellarg($jobId),
                escapeshellarg($logFile)
            );
            
            // Exécuter la commande et capturer le PID
            exec($command, $output);
            $pid = (int)$output[0];
            
            // Stocker le PID dans un fichier
            file_put_contents($this->tasksDir . '/' . $jobId . '.pid', $pid);
        } 
        // Systèmes Windows: utiliser start /B
        else {
            $command = sprintf(
                'start /B %s %s process_task %s > %s 2>&1',
                escapeshellarg($phpPath),
                escapeshellarg($workerScript),
                escapeshellarg($jobId),
                escapeshellarg($logFile)
            );
            
            pclose(popen($command, 'r'));
        }
        
        // Mettre à jour l'état de la tâche
        $this->processingService->updateJob($jobId, 10, 1, 'Traitement démarré en arrière-plan');
        
        return true;
    }
    
    /**
     * Crée le script worker pour le traitement en arrière-plan
     * 
     * @param string $workerScriptPath Chemin du script worker
     * @return bool Succès de l'opération
     */
    private function createWorkerScript($workerScriptPath)
    {
        $workerCode = <<<'EOT'
<?php
/**
 * Worker script pour le traitement asynchrone des tâches
 * 
 * Usage:
 * php worker.php process_task JOB_ID
 * php worker.php process_queue
 */

// Inclure les fichiers de configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/autoload.php';

// Analyser les arguments de la ligne de commande
$action = isset($argv[1]) ? $argv[1] : null;
$jobId = isset($argv[2]) ? $argv[2] : null;

// Initialiser les services nécessaires
$asyncService = new \Services\AsyncProcessingService();

// Exécuter l'action demandée
switch ($action) {
    case 'process_task':
        if (!$jobId) {
            echo "Erreur: ID de tâche non spécifié.\n";
            exit(1);
        }
        echo "Traitement de la tâche " . $jobId . "\n";
        $asyncService->processTask($jobId);
        break;
        
    case 'process_queue':
        echo "Traitement de la file d'attente\n";
        $asyncService->processQueue();
        break;
        
    default:
        echo "Usage:\n";
        echo "php worker.php process_task JOB_ID\n";
        echo "php worker.php process_queue\n";
        exit(1);
}

exit(0);
EOT;
        
        // Écrire le script
        file_put_contents($workerScriptPath, $workerCode);
        chmod($workerScriptPath, 0755);
        
        return true;
    }
    
    /**
     * Traite une tâche spécifique
     * 
     * @param string $jobId ID de la tâche
     * @return array Résultat du traitement
     */
    public function processTask($jobId)
    {
        // Vérifier si la tâche existe
        $taskFile = $this->tasksDir . '/' . $jobId . '.task';
        
        if (!file_exists($taskFile)) {
            return [
                'success' => false,
                'error' => 'Tâche non trouvée',
                'category' => 'not_found',
                'advice' => 'Vérifiez l\'ID de la tâche et réessayez.'
            ];
        }
        
        // Charger les données de la tâche
        $taskData = json_decode(file_get_contents($taskFile), true);
        
        if (!$taskData) {
            return [
                'success' => false,
                'error' => 'Données de tâche invalides',
                'category' => 'format',
                'advice' => 'Le fichier de tâche est corrompu ou mal formaté.'
            ];
        }
        
        // Mettre à jour l'état de la tâche
        $taskData['status'] = 'processing';
        $taskData['started_at'] = time();
        file_put_contents($taskFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Traiter la tâche selon son type
        $result = [
            'success' => false,
            'error' => 'Type de tâche non supporté',
            'category' => 'unsupported',
            'advice' => 'Ce type de tâche n\'est pas pris en charge par le système.'
        ];
        
        try {
            $this->processingService->updateJob($jobId, 20, 2, 'Prétraitement en cours');
            
            switch ($taskData['type']) {
                case 'file_processing':
                    $result = $this->processFileTask($taskData);
                    break;
                    
                case 'youtube_processing':
                    $result = $this->processYouTubeTask($taskData);
                    break;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, mettre à jour l'état de la tâche et du job
            $taskData['status'] = 'error';
            $taskData['error'] = $e->getMessage();
            $taskData['completed_at'] = time();
            file_put_contents($taskFile, json_encode($taskData, JSON_PRETTY_PRINT));
            
            $this->processingService->failJob($jobId, $e->getMessage(), 'exception', 'Une erreur inattendue est survenue. Veuillez réessayer.');
            
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'category' => 'exception',
                'advice' => 'Une erreur inattendue est survenue. Veuillez réessayer.'
            ];
        }
        
        // Mettre à jour l'état final de la tâche
        $taskData['status'] = $result['success'] ? 'completed' : 'error';
        $taskData['result'] = $result;
        $taskData['completed_at'] = time();
        file_put_contents($taskFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        return $result;
    }
    
    /**
     * Traite une tâche de traitement de fichier
     * 
     * @param array $taskData Données de la tâche
     * @return array Résultat du traitement
     */
    private function processFileTask($taskData)
    {
        $params = $taskData['parameters'];
        $jobId = $taskData['id'];
        
        // Initialiser les services
        $transcriptionService = new TranscriptionService();
        
        // Extraire les paramètres
        $filePath = $params['filePath'];
        $outputDir = $params['outputDir'];
        $language = $params['language'];
        $forceLanguage = $params['forceLanguage'];
        
        // Prétraiter le fichier audio
        $this->processingService->updateJob($jobId, 30, 2, 'Prétraitement audio');
        $preprocessResult = $transcriptionService->preprocessAudio($filePath, $outputDir);
        
        if (!$preprocessResult['success']) {
            $this->processingService->failJob($jobId, $preprocessResult['error'], $preprocessResult['category'] ?? 'unknown', $preprocessResult['advice'] ?? null);
            return $preprocessResult;
        }
        
        // Utiliser le fichier prétraité pour la transcription
        $preprocessedFilePath = $preprocessResult['output_file'];
        
        // Créer le répertoire de résultats si nécessaire
        if (!is_dir(RESULT_DIR)) {
            mkdir(RESULT_DIR, 0777, true);
        }
        
        // Générer un ID pour le résultat
        $resultId = FileUtils::generateUniqueId();
        $resultPath = RESULT_DIR . '/result_' . $resultId . '.json';
        
        // Transcrire le fichier audio
        $this->processingService->updateJob($jobId, 50, 3, 'Transcription en cours');
        $transcriptionResult = $transcriptionService->transcribeAudio(
            $preprocessedFilePath, 
            $resultPath, 
            $language, 
            $forceLanguage
        );
        
        if (!$transcriptionResult['success']) {
            $this->processingService->failJob($jobId, $transcriptionResult['error'], $transcriptionResult['category'] ?? 'unknown', $transcriptionResult['advice'] ?? null);
            return $transcriptionResult;
        }
        
        // Finalisation
        $this->processingService->updateJob($jobId, 90, 4, 'Finalisation');
        
        // Compléter le job
        $this->processingService->completeJob($jobId, $resultId);
        
        return [
            'success' => true,
            'result_id' => $resultId,
            'text' => $transcriptionResult['text'],
            'language' => $transcriptionResult['language']
        ];
    }
    
    /**
     * Traite une tâche de traitement YouTube
     * 
     * @param array $taskData Données de la tâche
     * @return array Résultat du traitement
     */
    private function processYouTubeTask($taskData)
    {
        $params = $taskData['parameters'];
        $jobId = $taskData['id'];
        
        // Initialiser les services
        $youtubeService = new YouTubeService();
        
        // Extraire les paramètres
        $youtubeUrl = $params['youtubeUrl'];
        $language = $params['language'];
        $forceLanguage = $params['forceLanguage'];
        
        // Mettre à jour l'état du job
        $this->processingService->updateJob($jobId, 20, 2, 'Téléchargement de la vidéo YouTube');
        
        // Télécharger et transcrire la vidéo
        $result = $youtubeService->downloadAndTranscribe($youtubeUrl, $language, $forceLanguage);
        
        if (!$result['success']) {
            $this->processingService->failJob($jobId, $result['error'], $result['category'] ?? 'unknown', $result['advice'] ?? null);
            return $result;
        }
        
        // Compléter le job
        $this->processingService->completeJob($jobId, $result['result_id']);
        
        return $result;
    }
    
    /**
     * Traite toutes les tâches en attente dans la file
     * 
     * @param int $maxTasks Nombre maximum de tâches à traiter
     * @return array Résultats du traitement
     */
    public function processQueue($maxTasks = 5)
    {
        // Récupérer les tâches en attente
        $taskFiles = glob($this->tasksDir . '/*.task');
        $results = [];
        $processedCount = 0;
        
        foreach ($taskFiles as $taskFile) {
            // Limiter le nombre de tâches traitées
            if ($processedCount >= $maxTasks) {
                break;
            }
            
            // Charger les données de la tâche
            $taskData = json_decode(file_get_contents($taskFile), true);
            
            // Ne traiter que les tâches en attente
            if ($taskData && $taskData['status'] === 'pending') {
                $jobId = $taskData['id'];
                
                // Traiter la tâche
                $result = $this->processTask($jobId);
                $results[$jobId] = $result;
                $processedCount++;
            }
        }
        
        return [
            'success' => true,
            'processed_count' => $processedCount,
            'results' => $results
        ];
    }
    
    /**
     * Récupère l'état d'une tâche
     * 
     * @param string $jobId ID de la tâche
     * @return array État de la tâche
     */
    public function getTaskStatus($jobId)
    {
        // Vérifier l'état de la tâche dans le fichier
        $taskFile = $this->tasksDir . '/' . $jobId . '.task';
        
        if (file_exists($taskFile)) {
            $taskData = json_decode(file_get_contents($taskFile), true);
            
            if ($taskData) {
                return [
                    'success' => true,
                    'task' => $taskData
                ];
            }
        }
        
        // Vérifier l'état du job
        $jobStatus = $this->processingService->getStatus($jobId);
        
        if ($jobStatus) {
            return [
                'success' => true,
                'job' => $jobStatus
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Tâche non trouvée',
            'category' => 'not_found',
            'advice' => 'Vérifiez l\'ID de la tâche et réessayez.'
        ];
    }
    
    /**
     * Annule une tâche en cours
     * 
     * @param string $jobId ID de la tâche
     * @return array Résultat de l'annulation
     */
    public function cancelTask($jobId)
    {
        // Vérifier si la tâche existe
        $taskFile = $this->tasksDir . '/' . $jobId . '.task';
        
        if (!file_exists($taskFile)) {
            return [
                'success' => false,
                'error' => 'Tâche non trouvée',
                'category' => 'not_found',
                'advice' => 'Vérifiez l\'ID de la tâche et réessayez.'
            ];
        }
        
        // Charger les données de la tâche
        $taskData = json_decode(file_get_contents($taskFile), true);
        
        if (!$taskData) {
            return [
                'success' => false,
                'error' => 'Données de tâche invalides',
                'category' => 'format',
                'advice' => 'Le fichier de tâche est corrompu ou mal formaté.'
            ];
        }
        
        // Vérifier si la tâche est déjà terminée
        if ($taskData['status'] === 'completed' || $taskData['status'] === 'error') {
            return [
                'success' => false,
                'error' => 'La tâche est déjà terminée',
                'category' => 'completed',
                'advice' => 'Vous ne pouvez pas annuler une tâche déjà terminée.'
            ];
        }
        
        // Tenter de terminer le processus si le PID est connu
        $pidFile = $this->tasksDir . '/' . $jobId . '.pid';
        
        if (file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);
            
            // Systèmes Unix-like: utiliser kill pour terminer le processus
            if (stripos(PHP_OS, 'WIN') === false && $pid > 0) {
                exec('kill ' . $pid . ' 2> /dev/null');
            }
            
            // Supprimer le fichier PID
            @unlink($pidFile);
        }
        
        // Mettre à jour l'état de la tâche
        $taskData['status'] = 'cancelled';
        $taskData['cancelled_at'] = time();
        file_put_contents($taskFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Mettre à jour l'état du job
        $this->processingService->failJob($jobId, 'Tâche annulée par l\'utilisateur', 'cancelled', 'Vous avez annulé cette tâche.');
        
        return [
            'success' => true,
            'message' => 'Tâche annulée avec succès'
        ];
    }
    
    /**
     * Nettoie les tâches anciennes et terminées
     * 
     * @param int $olderThan Âge en secondes à partir duquel les tâches sont considérées comme anciennes
     * @return array Résultat du nettoyage
     */
    public function cleanupTasks($olderThan = 86400)
    {
        $now = time();
        $taskFiles = glob($this->tasksDir . '/*.task');
        $logFiles = glob($this->logsDir . '/*.log');
        $pidFiles = glob($this->tasksDir . '/*.pid');
        
        $cleanedTasks = 0;
        $cleanedLogs = 0;
        $cleanedPids = 0;
        
        // Nettoyer les fichiers de tâches
        foreach ($taskFiles as $taskFile) {
            $taskData = json_decode(file_get_contents($taskFile), true);
            
            // Ne nettoyer que les tâches terminées
            if ($taskData && 
                (($taskData['status'] === 'completed' || $taskData['status'] === 'error' || $taskData['status'] === 'cancelled') && 
                ($now - ($taskData['completed_at'] ?? $taskData['cancelled_at'] ?? $taskData['created_at']) > $olderThan))) {
                @unlink($taskFile);
                $cleanedTasks++;
            }
        }
        
        // Nettoyer les fichiers de logs
        foreach ($logFiles as $logFile) {
            if ($now - filemtime($logFile) > $olderThan) {
                @unlink($logFile);
                $cleanedLogs++;
            }
        }
        
        // Nettoyer les fichiers PID
        foreach ($pidFiles as $pidFile) {
            $isActive = false;
            $pid = (int)file_get_contents($pidFile);
            
            // Vérifier si le processus est toujours actif
            if (stripos(PHP_OS, 'WIN') === false && $pid > 0) {
                exec('ps -p ' . $pid . ' -o pid=', $output);
                $isActive = !empty($output);
            }
            
            // Supprimer les fichiers PID des processus inactifs
            if (!$isActive || $now - filemtime($pidFile) > $olderThan) {
                @unlink($pidFile);
                $cleanedPids++;
            }
        }
        
        return [
            'success' => true,
            'cleaned_tasks' => $cleanedTasks,
            'cleaned_logs' => $cleanedLogs,
            'cleaned_pids' => $cleanedPids
        ];
    }
}