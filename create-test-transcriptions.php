<?php

/**
 * Script pour créer des transcriptions de test
 */

echo "🎯 Création de transcriptions de test...\n\n";

// Connexion à la base de données
$dbPath = __DIR__ . '/database/transcription.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtenir l'ID utilisateur admin
$stmt = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$userId = $admin['id'] ?? 1;

try {
    // Supprimer les transcriptions existantes
    $pdo->exec("DELETE FROM transcriptions");
    
    // Transcriptions de test
    $transcriptions = [
        [
            'id' => 'trans_001_' . time(),
            'file_name' => 'interview_client.mp3',
            'text' => 'Bonjour, aujourd\'hui nous allons discuter de votre projet de transcription automatique. Pouvez-vous me parler de vos besoins spécifiques ? Nous aimerions comprendre comment vous envisagez d\'utiliser cette technologie dans votre workflow quotidien.',
            'language' => 'fr',
            'file_size' => 2457600, // 2.4MB
            'duration' => 180, // 3 minutes
            'is_processed' => 1,
            'user_id' => $userId
        ],
        [
            'id' => 'trans_002_' . time(),
            'file_name' => 'meeting_team.wav',
            'text' => 'Welcome everyone to today\'s team meeting. We have several important topics to discuss including the new project timeline, budget allocations, and the upcoming product launch. Let\'s start with the project status update from Sarah.',
            'language' => 'en',
            'file_size' => 5242880, // 5MB
            'duration' => 900, // 15 minutes
            'is_processed' => 1,
            'user_id' => $userId
        ],
        [
            'id' => 'trans_003_' . time(),
            'file_name' => 'formation_securite.mp4',
            'text' => 'Cette formation de sécurité informatique couvre les principales menaces cybernétiques et les bonnes pratiques à adopter. Nous aborderons les mots de passe sécurisés, la gestion des emails suspects, et les protocoles de sauvegarde.',
            'language' => 'fr',
            'file_size' => 15728640, // 15MB
            'duration' => 1200, // 20 minutes
            'is_processed' => 1,
            'user_id' => $userId
        ],
        [
            'id' => 'trans_004_' . time(),
            'file_name' => 'conference_ia.mp3',
            'text' => 'L\'intelligence artificielle révolutionne notre façon de travailler. Dans cette conférence, nous explorerons les applications pratiques de l\'IA, ses limites actuelles, et les perspectives d\'évolution pour les prochaines années.',
            'language' => 'fr',
            'file_size' => 8388608, // 8MB
            'duration' => 2400, // 40 minutes
            'is_processed' => 1,
            'user_id' => $userId
        ],
        [
            'id' => 'trans_005_' . time(),
            'file_name' => 'presentation_produit.wav',
            'text' => 'Nous sommes ravis de vous présenter notre nouveau produit qui va transformer votre expérience utilisateur. Cette solution innovante combine simplicité d\'utilisation et performances exceptionnelles.',
            'language' => 'fr',
            'file_size' => 3145728, // 3MB
            'duration' => 300, // 5 minutes
            'is_processed' => 1,
            'user_id' => $userId
        ],
        [
            'id' => 'trans_006_' . time(),
            'file_name' => 'tutorial_youtube.mp3',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_id' => 'dQw4w9WgXcQ',
            'text' => 'This is a comprehensive tutorial on how to use our transcription service. We will cover all the features from basic transcription to advanced analytics and export options.',
            'language' => 'en',
            'duration' => 600, // 10 minutes
            'is_processed' => 1,
            'user_id' => $userId
        ],
        [
            'id' => 'trans_007_' . time(),
            'file_name' => 'podcast_tech.mp3',
            'text' => 'Dans ce podcast, nous discutons des dernières tendances technologiques, de l\'impact de la blockchain, et des innovations en matière de développement logiciel.',
            'language' => 'fr',
            'file_size' => 12582912, // 12MB
            'duration' => 3600, // 1 heure
            'is_processed' => 0, // En cours de traitement
            'user_id' => $userId
        ]
    ];
    
    // Insérer les transcriptions
    $stmt = $pdo->prepare("
        INSERT INTO transcriptions (
            id, file_name, text, language, file_size, duration, 
            is_processed, user_id, youtube_url, youtube_id, created_at
        ) VALUES (
            :id, :file_name, :text, :language, :file_size, :duration,
            :is_processed, :user_id, :youtube_url, :youtube_id, 
            datetime('now', '-' || abs(random() % 30) || ' days')
        )
    ");
    
    foreach ($transcriptions as $transcription) {
        $stmt->execute([
            'id' => $transcription['id'],
            'file_name' => $transcription['file_name'],
            'text' => $transcription['text'],
            'language' => $transcription['language'],
            'file_size' => $transcription['file_size'] ?? null,
            'duration' => $transcription['duration'],
            'is_processed' => $transcription['is_processed'],
            'user_id' => $transcription['user_id'],
            'youtube_url' => $transcription['youtube_url'] ?? null,
            'youtube_id' => $transcription['youtube_id'] ?? null
        ]);
    }
    
    echo "✅ " . count($transcriptions) . " transcriptions de test créées !\n\n";
    
    // Afficher le résumé
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as processing,
            COUNT(DISTINCT language) as languages
        FROM transcriptions
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Statistiques :\n";
    echo "   📁 Total : {$stats['total']} transcriptions\n";
    echo "   ✅ Terminées : {$stats['completed']}\n";
    echo "   ⏳ En cours : {$stats['processing']}\n";
    echo "   🌍 Langues : {$stats['languages']}\n\n";
    
    // Afficher les transcriptions
    echo "📋 Transcriptions créées :\n";
    $stmt = $pdo->query("
        SELECT file_name, language, is_processed, 
               CASE WHEN youtube_url IS NOT NULL THEN 'YouTube' ELSE 'Fichier' END as type
        FROM transcriptions 
        ORDER BY created_at DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_processed'] ? '✅' : '⏳';
        echo "   {$status} {$row['file_name']} ({$row['language']}) - {$row['type']}\n";
    }
    
    echo "\n🎯 Vous pouvez maintenant tester la liste des transcriptions !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}