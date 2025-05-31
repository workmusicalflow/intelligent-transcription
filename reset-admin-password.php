<?php

/**
 * Script simple pour réinitialiser le mot de passe admin
 */

echo "🔐 Réinitialisation du mot de passe admin...\n\n";

// Connexion à la base de données
$dbPath = __DIR__ . '/database/transcription.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Nouveau mot de passe admin
$newPassword = 'admin123';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    // Mettre à jour le mot de passe admin
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE username = 'admin' OR email = 'admin@example.com'
    ");
    $stmt->execute([$hashedPassword]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Mot de passe admin mis à jour avec succès!\n\n";
    } else {
        echo "⚠️  Aucun utilisateur admin trouvé, création...\n";
        
        // Créer l'utilisateur admin
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active)
            VALUES ('admin', 'admin@example.com', ?, 'Admin', 'System', 1, 1)
        ");
        $stmt->execute([$hashedPassword]);
        echo "✅ Utilisateur admin créé!\n\n";
    }
    
    // Afficher les informations de connexion
    echo "🎯 Identifiants de connexion:\n";
    echo "   👤 Nom d'utilisateur: admin\n";
    echo "   📧 Email: admin@example.com\n";
    echo "   🔑 Mot de passe: admin123\n\n";
    
    // Vérifier tous les utilisateurs
    echo "📋 Utilisateurs disponibles:\n";
    $stmt = $pdo->query("SELECT username, email, is_admin, is_active FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $status = $user['is_active'] ? '🟢' : '🔴';
        $role = $user['is_admin'] ? 'ADMIN' : 'USER';
        echo "   {$status} {$user['username']} ({$user['email']}) - {$role}\n";
    }
    
    echo "\n✅ Vous pouvez maintenant vous connecter!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}