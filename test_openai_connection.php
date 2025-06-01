<?php
/**
 * Test de connexion à l'API OpenAI
 */

require_once 'config.php';

if (!defined('OPENAI_API_KEY') || !defined('OPENAI_ORG_ID')) {
    die("❌ Les constantes OPENAI_API_KEY ou OPENAI_ORG_ID ne sont pas définies.\n");
}

echo "🔍 Test de connexion OpenAI Whisper...\n";
echo "Clé API: " . substr(OPENAI_API_KEY, 0, 20) . "...\n";
echo "Longueur clé: " . strlen(OPENAI_API_KEY) . " caractères\n";
echo "Org ID: " . OPENAI_ORG_ID . "\n\n";

// Test 1: Sans Organization ID d'abord
echo "=== Test 1: Sans Organization ID ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
if ($httpCode == 200) {
    echo "✅ Connexion réussie sans Org ID!\n";
} else {
    echo "❌ Échec sans Org ID\n";
    echo "Réponse: " . substr($response, 0, 200) . "...\n\n";
}

// Test 2: Avec Organization ID
echo "=== Test 2: Avec Organization ID ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . OPENAI_API_KEY,
    'OpenAI-Organization: ' . OPENAI_ORG_ID,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✅ Connexion réussie avec Org ID!\n";
    if (isset($data['data'])) {
        echo "Modèles disponibles (contenant 'whisper'):\n";
        foreach ($data['data'] as $model) {
            if (strpos($model['id'], 'whisper') !== false) {
                echo "  - " . $model['id'] . "\n";
            }
        }
    } else {
        echo "⚠️ Réponse inattendue (pas de liste de modèles).\n";
    }
} else {
    echo "❌ Échec avec Org ID\n";
    echo "Réponse: " . substr($response, 0, 200) . "...\n";
}

// Test 3: Vérifier le format de la clé
echo "\n=== Test 3: Analyse de la clé ===\n";
if (strpos(OPENAI_API_KEY, 'sk-') === 0) {
    echo "✅ Format de clé valide (commence par sk-)\n";
} else {
    echo "❌ Format de clé invalide (ne commence pas par sk-)\n";
}

if (strlen(OPENAI_API_KEY) >= 45) {
    echo "✅ Longueur de clé probable\n";
} else {
    echo "❌ Clé trop courte\n";
}