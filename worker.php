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

// Configuration de l'environnement
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // Pas de limite de temps d'exécution
ignore_user_abort(true); // Continuer même si l'utilisateur ferme la connexion

// Fonction de journalisation
function workerLog($message) {
    $date = date('Y-m-d H:i:s');
    echo "[$date] $message\n";
}

// Initialiser les services nécessaires
$asyncService = new \Services\AsyncProcessingService();

// Exécuter l'action demandée
switch ($action) {
    case 'process_task':
        if (!$jobId) {
            workerLog("Erreur: ID de tâche non spécifié.");
            exit(1);
        }
        
        workerLog("Démarrage du traitement de la tâche " . $jobId);
        
        try {
            $result = $asyncService->processTask($jobId);
            
            if ($result['success']) {
                workerLog("Tâche " . $jobId . " terminée avec succès.");
                workerLog("ID du résultat: " . ($result['result_id'] ?? 'N/A'));
            } else {
                workerLog("Erreur lors du traitement de la tâche " . $jobId . ": " . $result['error']);
                workerLog("Catégorie: " . ($result['category'] ?? 'unknown'));
                workerLog("Conseil: " . ($result['advice'] ?? 'Aucun conseil disponible.'));
            }
        } catch (\Exception $e) {
            workerLog("Exception lors du traitement de la tâche " . $jobId . ": " . $e->getMessage());
            workerLog("Trace: " . $e->getTraceAsString());
            exit(1);
        }
        break;
        
    case 'process_queue':
        workerLog("Démarrage du traitement de la file d'attente");
        
        try {
            $result = $asyncService->processQueue();
            workerLog("Traitement de la file d'attente terminé. " . $result['processed_count'] . " tâches traitées.");
        } catch (\Exception $e) {
            workerLog("Exception lors du traitement de la file d'attente: " . $e->getMessage());
            workerLog("Trace: " . $e->getTraceAsString());
            exit(1);
        }
        break;
        
    case 'cleanup':
        workerLog("Démarrage du nettoyage des tâches anciennes");
        
        try {
            $result = $asyncService->cleanupTasks();
            workerLog("Nettoyage terminé. " . $result['cleaned_tasks'] . " tâches, " . 
                      $result['cleaned_logs'] . " logs et " . $result['cleaned_pids'] . " PIDs nettoyés.");
        } catch (\Exception $e) {
            workerLog("Exception lors du nettoyage: " . $e->getMessage());
            workerLog("Trace: " . $e->getTraceAsString());
            exit(1);
        }
        break;
        
    default:
        workerLog("Usage:");
        workerLog("php worker.php process_task JOB_ID - Traite une tâche spécifique");
        workerLog("php worker.php process_queue - Traite toutes les tâches en attente");
        workerLog("php worker.php cleanup - Nettoie les tâches anciennes");
        exit(1);
}

workerLog("Processus de travail terminé.");
exit(0);