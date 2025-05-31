<?php

/**
 * Script pour créer des utilisateurs de test
 */

require_once __DIR__ . '/src/bootstrap.php';

// Connexion à la base de données
$dbPath = __DIR__ . '/database/transcription.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🔐 Création/Mise à jour des utilisateurs de test...\n\n";

// Fonction pour hasher les mots de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Vérifier si la table users existe et a la bonne structure
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        role VARCHAR(20) DEFAULT 'user',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Utilisateurs de test à créer
$testUsers = [
    [
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'first_name' => 'Admin',
        'last_name' => 'System',
        'role' => 'admin'
    ],
    [
        'username' => 'test',
        'email' => 'test@example.com',
        'password' => 'test123',
        'first_name' => 'Test',
        'last_name' => 'User',
        'role' => 'user'
    ],
    [
        'username' => 'demo',
        'email' => 'demo@example.com',
        'password' => 'demo123',
        'first_name' => 'Demo',
        'last_name' => 'Account',
        'role' => 'user'
    ]
];

foreach ($testUsers as $user) {
    try {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$user['username'], $user['email']]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Mettre à jour le mot de passe
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([hashPassword($user['password']), $existingUser['id']]);
            echo "✅ Utilisateur '{$user['username']}' mis à jour\n";
        } else {
            // Créer un nouvel utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $user['username'],
                $user['email'],
                hashPassword($user['password']),
                $user['first_name'],
                $user['last_name'],
                $user['role']
            ]);
            echo "✅ Utilisateur '{$user['username']}' créé\n";
        }
        
        echo "   📧 Email: {$user['email']}\n";
        echo "   🔑 Mot de passe: {$user['password']}\n";
        echo "   👤 Rôle: {$user['role']}\n\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur pour '{$user['username']}': " . $e->getMessage() . "\n\n";
    }
}

// Afficher tous les utilisateurs
echo "📋 Liste des utilisateurs disponibles:\n";
$stmt = $pdo->query("SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $status = $user['is_active'] ? '🟢' : '🔴';
    echo "   {$status} {$user['username']} ({$user['email']}) - {$user['role']}\n";
}

echo "\n🎯 Vous pouvez maintenant vous connecter avec:\n";
echo "   • admin / admin123 (administrateur)\n";
echo "   • test / test123 (utilisateur)\n";
echo "   • demo / demo123 (utilisateur)\n";
echo "\n✅ Configuration terminée!\n";