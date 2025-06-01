<?php
/**
 * Force le traitement d'une transcription existante
 */

require_once 'config.php';

$transcriptionId = 'trans_683c5251ae187'; // Votre transcription

echo "🎬 Forçage du traitement de la transcription: $transcriptionId\n\n";

// Connexion à la base de données
$dbPath = __DIR__ . '/database/transcription.db';
echo "📂 Chemin DB: $dbPath\n";
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupérer les infos de la transcription
$stmt = $pdo->prepare("SELECT * FROM transcriptions WHERE id = ?");
$stmt->execute([$transcriptionId]);
$transcription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transcription) {
    die("❌ Transcription non trouvée\n");
}

echo "📁 Fichier: " . $transcription['file_name'] . "\n";
echo "📍 Chemin: " . $transcription['file_path'] . "\n";
echo "🗣️ Langue: " . $transcription['language'] . "\n";
echo "📊 Status: " . ($transcription['is_processed'] ? 'Terminé' : 'En cours') . "\n\n";

// Vérifier si le fichier existe
if (!file_exists($transcription['file_path'])) {
    die("❌ Fichier audio non trouvé: " . $transcription['file_path'] . "\n");
}

echo "✅ Fichier audio trouvé\n";

// Lancer le traitement
$filePath = $transcription['file_path'];
$language = $transcription['language'];

echo "🚀 Lancement du traitement...\n";

// Utiliser le transcribe.php existant
$processingCommand = "php " . __DIR__ . "/transcribe.php '$filePath' '$language' '$transcriptionId'";

echo "Commande: $processingCommand\n\n";

// Exécuter en premier plan pour voir les résultats
passthru($processingCommand);

echo "\n✅ Traitement terminé!\n";
?>