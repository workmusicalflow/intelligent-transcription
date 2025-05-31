<?php

/**
 * Migration pour ajouter le support des segments Whisper
 */

echo "🔄 Migration - Support des segments Whisper...\n\n";

// Connexion à la base de données
$dbPath = __DIR__ . '/database/transcription.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    echo "📋 Vérification de la structure actuelle...\n";
    
    // Vérifier si la colonne whisper_data existe déjà
    $checkColumn = $pdo->query("PRAGMA table_info(transcriptions)");
    $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);
    
    $hasWhisperData = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'whisper_data') {
            $hasWhisperData = true;
            break;
        }
    }
    
    if (!$hasWhisperData) {
        echo "➕ Ajout de la colonne whisper_data...\n";
        $pdo->exec("ALTER TABLE transcriptions ADD COLUMN whisper_data TEXT");
        echo "✅ Colonne whisper_data ajoutée\n";
    } else {
        echo "ℹ️  Colonne whisper_data déjà présente\n";
    }
    
    // Ajouter d'autres colonnes utiles pour Whisper
    $additionalColumns = [
        'confidence_score' => 'REAL',
        'detected_language' => 'TEXT',
        'processing_model' => 'TEXT',
        'whisper_version' => 'TEXT'
    ];
    
    foreach ($additionalColumns as $columnName => $columnType) {
        $hasColumn = false;
        foreach ($columns as $column) {
            if ($column['name'] === $columnName) {
                $hasColumn = true;
                break;
            }
        }
        
        if (!$hasColumn) {
            echo "➕ Ajout de la colonne $columnName...\n";
            $pdo->exec("ALTER TABLE transcriptions ADD COLUMN $columnName $columnType");
            echo "✅ Colonne $columnName ajoutée\n";
        }
    }
    
    echo "\n📊 Mise à jour des transcriptions existantes...\n";
    
    // Mettre à jour les transcriptions existantes avec des métadonnées par défaut
    $stmt = $pdo->prepare("
        UPDATE transcriptions 
        SET 
            processing_model = 'whisper-1',
            detected_language = language,
            confidence_score = 0.85
        WHERE processing_model IS NULL
    ");
    $affected = $stmt->execute();
    
    $rowCount = $stmt->rowCount();
    echo "✅ $rowCount transcriptions mises à jour avec des métadonnées par défaut\n";
    
    echo "\n🗂️  Création d'une table segments optionnelle...\n";
    
    // Créer une table pour les segments (optionnel pour de meilleures performances)
    $createSegmentsTable = "
        CREATE TABLE IF NOT EXISTS transcription_segments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transcription_id TEXT NOT NULL,
            segment_index INTEGER NOT NULL,
            start_time REAL NOT NULL,
            end_time REAL NOT NULL,
            text TEXT NOT NULL,
            confidence REAL,
            avg_logprob REAL,
            compression_ratio REAL,
            no_speech_prob REAL,
            temperature REAL,
            word_count INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE CASCADE
        )
    ";
    
    $pdo->exec($createSegmentsTable);
    echo "✅ Table transcription_segments créée\n";
    
    // Créer des index pour de meilleures performances
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_segments_transcription ON transcription_segments(transcription_id)",
        "CREATE INDEX IF NOT EXISTS idx_segments_time ON transcription_segments(start_time, end_time)",
        "CREATE INDEX IF NOT EXISTS idx_transcriptions_model ON transcriptions(processing_model)"
    ];
    
    foreach ($indexes as $indexSql) {
        $pdo->exec($indexSql);
    }
    echo "✅ Index créés pour optimiser les performances\n";
    
    echo "\n📝 Exemple de structure pour futures transcriptions Whisper...\n";
    
    // Montrer la structure de données attendue pour Whisper
    $exampleWhisperData = [
        'segments' => [
            [
                'text' => 'Bonjour, comment allez-vous ?',
                'start' => 0.0,
                'end' => 2.5,
                'tokens' => [1234, 5678, 9012],
                'temperature' => 0.0,
                'avg_logprob' => -0.15,
                'compression_ratio' => 1.2,
                'no_speech_prob' => 0.01
            ]
        ],
        'words' => [
            ['word' => 'Bonjour', 'start' => 0.0, 'end' => 0.8],
            ['word' => 'comment', 'start' => 1.0, 'end' => 1.5],
            ['word' => 'allez-vous', 'start' => 1.6, 'end' => 2.3]
        ]
    ];
    
    echo json_encode($exampleWhisperData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    echo "\n🎯 Statistiques finales...\n";
    
    // Afficher les statistiques
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_transcriptions,
            SUM(CASE WHEN whisper_data IS NOT NULL THEN 1 ELSE 0 END) as with_segments,
            SUM(CASE WHEN processing_model = 'whisper-1' THEN 1 ELSE 0 END) as whisper_processed,
            COUNT(DISTINCT detected_language) as languages_detected
        FROM transcriptions
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Statistiques de la base de données :\n";
    echo "   📁 Total transcriptions : {$stats['total_transcriptions']}\n";
    echo "   🎯 Avec segments Whisper : {$stats['with_segments']}\n";
    echo "   🤖 Traitées par Whisper : {$stats['whisper_processed']}\n";
    echo "   🌍 Langues détectées : {$stats['languages_detected']}\n";
    
    // Vérifier la table segments
    $segmentStats = $pdo->query("SELECT COUNT(*) as total_segments FROM transcription_segments")->fetch(PDO::FETCH_ASSOC);
    echo "   📝 Segments stockés : {$segmentStats['total_segments']}\n";
    
    echo "\n✅ Migration terminée avec succès !\n";
    echo "\n💡 Prochaines étapes :\n";
    echo "   1. Les nouvelles transcriptions Whisper utiliseront automatiquement les segments\n";
    echo "   2. L'API /transcriptions/detail retourne désormais les vrais segments s'ils existent\n";
    echo "   3. Le frontend peut maintenant gérer la synchronisation temporelle précise\n";
    echo "   4. Export SRT disponible avec timestamps réels\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}