<?php
/**
 * Worker pour traiter automatiquement les transcriptions en attente
 * Lance ce script pour traiter en continu les transcriptions
 * Usage: php transcription_worker.php
 */

require_once __DIR__ . '/config.php';

// Configuration
$checkInterval = 10; // Vérifier toutes les 10 secondes
$maxConcurrent = 2; // Maximum 2 transcriptions simultanées

function logWorker($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] WORKER: $message\n";
    error_log("[$timestamp] TRANSCRIPTION-WORKER: $message\n", 3, __DIR__ . '/logs/worker.log');
}

function getRunningTranscriptions() {
    // Compter les processus de transcription en cours
    $command = "pgrep -f 'process_transcription_auto.php' | wc -l";
    $count = (int)shell_exec($command);
    return $count;
}

function getPendingTranscriptions($pdo) {
    $stmt = $pdo->prepare("SELECT id, file_name, created_at FROM transcriptions WHERE is_processed = 0 ORDER BY created_at ASC LIMIT 10");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

logWorker("🚀 Démarrage du worker de transcription");
logWorker("Intervalle de vérification: {$checkInterval}s");
logWorker("Maximum simultané: $maxConcurrent");

// Connexion à la base de données
$dbPath = __DIR__ . '/database/transcription.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Boucle principale
while (true) {
    try {
        // Vérifier combien de transcriptions sont en cours
        $running = getRunningTranscriptions();
        $available = max(0, $maxConcurrent - $running);
        
        if ($available > 0) {
            // Récupérer les transcriptions en attente
            $pending = getPendingTranscriptions($pdo);
            
            if (!empty($pending)) {
                logWorker("📋 " . count($pending) . " transcription(s) en attente, $running en cours, $available slot(s) disponible(s)");
                
                $processed = 0;
                foreach ($pending as $transcription) {
                    if ($processed >= $available) {
                        break;
                    }
                    
                    $id = $transcription['id'];
                    $fileName = $transcription['file_name'];
                    
                    logWorker("🎯 Lancement: $fileName ($id)");
                    
                    // Lancer le processus en arrière-plan
                    $command = "php " . __DIR__ . "/process_transcription_auto.php '$id' > /dev/null 2>&1 &";
                    exec($command);
                    
                    $processed++;
                    usleep(500000); // Attendre 0.5s entre les lancements
                }
                
                if ($processed > 0) {
                    logWorker("✅ $processed transcription(s) lancée(s)");
                }
            }
        } else if ($running > 0) {
            logWorker("⏳ $running transcription(s) en cours, attente...");
        }
        
        // Attendre avant la prochaine vérification
        sleep($checkInterval);
        
    } catch (Exception $e) {
        logWorker("❌ Erreur: " . $e->getMessage());
        sleep($checkInterval);
    }
}
?>