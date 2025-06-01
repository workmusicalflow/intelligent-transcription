<?php
/**
 * Test direct de Whisper avec votre fichier
 */

require_once 'config.php';

$filePath = '/Users/ns2poportable/Desktop/inteligent-transcription/uploads/trans_683c5251ae187_WhatsApp_Video_2025-03-24_at_22_10_08.mp4';
$transcriptionId = 'trans_683c5251ae187';

echo "🎤 Test direct Whisper avec votre fichier...\n";
echo "📁 Fichier: " . basename($filePath) . "\n";
echo "📊 Taille: " . round(filesize($filePath) / 1024 / 1024, 2) . " MB\n\n";

// Vérifier si le fichier est trop gros pour Whisper (25MB max)
$fileSize = filesize($filePath);
if ($fileSize > 25 * 1024 * 1024) {
    echo "⚠️ Fichier trop gros pour Whisper (" . round($fileSize / 1024 / 1024, 2) . " MB > 25 MB)\n";
    echo "Le preprocessing avec FFmpeg est requis.\n";
    exit;
}

echo "✅ Taille acceptable pour Whisper\n\n";

// Appel direct à l'API Whisper
echo "🚀 Envoi à Whisper...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY,
]);

$postFields = [
    'model' => 'whisper-1',
    'file' => new CURLFile($filePath, 'video/mp4', basename($filePath)),
    'response_format' => 'verbose_json',
    'language' => 'fr' // Supposons français
];

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Erreur cURL: " . ($error ?: 'Aucune') . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    echo "✅ Transcription réussie!\n";
    echo "🗣️ Langue détectée: " . ($data['language'] ?? 'N/A') . "\n";
    echo "⏱️ Durée: " . ($data['duration'] ?? 'N/A') . "s\n";
    echo "📝 Texte (" . strlen($data['text']) . " caractères):\n";
    echo "---\n";
    echo substr($data['text'], 0, 500) . (strlen($data['text']) > 500 ? "..." : "") . "\n";
    echo "---\n\n";
    
    // Sauvegarder dans la base de données
    echo "💾 Sauvegarde dans la base...\n";
    
    $dbPath = __DIR__ . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $updateQuery = "UPDATE transcriptions SET 
        text = :text, 
        is_processed = 1, 
        duration = :duration,
        detected_language = :detected_language,
        whisper_data = :whisper_data
        WHERE id = :id";
    
    $stmt = $pdo->prepare($updateQuery);
    $result = $stmt->execute([
        'text' => $data['text'],
        'duration' => $data['duration'] ?? null,
        'detected_language' => $data['language'] ?? null,
        'whisper_data' => json_encode($data),
        'id' => $transcriptionId
    ]);
    
    if ($result) {
        echo "✅ Transcription sauvegardée!\n";
        echo "🔗 Actualisez votre page: http://localhost:5173/transcriptions/$transcriptionId\n";
    } else {
        echo "❌ Erreur lors de la sauvegarde\n";
    }
    
} else {
    echo "❌ Erreur Whisper:\n";
    echo $response . "\n";
}
?>