<?php
/**
 * Script de cron pour le traitement périodique des tâches et le nettoyage
 * 
 * À configurer pour s'exécuter périodiquement via cron ou tâche planifiée.
 * 
 * Exemple de configuration cron (toutes les 5 minutes):
 * */5 * * * * php /chemin/vers/cron.php >> /chemin/vers/logs/cron.log 2>&1
 */

// Inclure les fichiers de configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/autoload.php';

// Configuration de l'environnement
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // Pas de limite de temps d'exécution

// Fonction de journalisation
function cronLog($message) {
    $date = date('Y-m-d H:i:s');
    echo "[$date] $message\n";
}

// Créer le répertoire des logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Démarrer le traitement
cronLog("Démarrage du script cron...");

// Initialiser le service asynchrone
$asyncService = new \Services\AsyncProcessingService();

// 1. Traiter la file d'attente
cronLog("Traitement de la file d'attente...");
try {
    $queueResult = $asyncService->processQueue(10); // Traiter jusqu'à 10 tâches à la fois
    cronLog("Traitement de la file d'attente terminé. " . $queueResult['processed_count'] . " tâches traitées.");
} catch (\Exception $e) {
    cronLog("Erreur lors du traitement de la file d'attente: " . $e->getMessage());
}

// 2. Nettoyer les tâches anciennes
cronLog("Nettoyage des tâches anciennes...");
try {
    $cleanupResult = $asyncService->cleanupTasks(86400); // Nettoyer les tâches de plus de 24 heures
    cronLog("Nettoyage terminé. " . $cleanupResult['cleaned_tasks'] . " tâches, " . 
            $cleanupResult['cleaned_logs'] . " logs et " . $cleanupResult['cleaned_pids'] . " PIDs nettoyés.");
} catch (\Exception $e) {
    cronLog("Erreur lors du nettoyage des tâches: " . $e->getMessage());
}

// 3. Nettoyer les anciens fichiers temporaires
cronLog("Nettoyage des fichiers temporaires...");
try {
    // Nettoyer les fichiers dans temp_audio
    $tempAudioDir = __DIR__ . '/temp_audio';
    if (is_dir($tempAudioDir)) {
        $tempFiles = glob($tempAudioDir . '/*');
        $now = time();
        $cleanedCount = 0;
        
        foreach ($tempFiles as $file) {
            // Nettoyer les fichiers de plus de 48 heures
            if ($now - filemtime($file) > 48 * 3600) {
                @unlink($file);
                $cleanedCount++;
            }
        }
        
        cronLog("Nettoyage des fichiers temporaires terminé. $cleanedCount fichiers supprimés.");
    }
} catch (\Exception $e) {
    cronLog("Erreur lors du nettoyage des fichiers temporaires: " . $e->getMessage());
}

// 4. Nettoyer le répertoire de logs
cronLog("Nettoyage des fichiers de logs...");
try {
    $processingService = new \Services\ProcessingService();
    $processingService->cleanupOldJobs();
    
    // Nettoyer également les logs Python
    $pyErrorUtils = new \Utils\PythonErrorUtils();
    $pyErrorUtils::cleanupOldLogs();
    
    cronLog("Nettoyage des fichiers de logs terminé.");
} catch (\Exception $e) {
    cronLog("Erreur lors du nettoyage des fichiers de logs: " . $e->getMessage());
}

cronLog("Script cron terminé.");
exit(0);