<?php

/**
 * Script pour créer un utilisateur administrateur
 */

require_once __DIR__ . '/src/bootstrap.php';

use Models\User;
use Database\DatabaseManager;

// Afficher les utilisateurs existants
echo "Utilisateurs existants:\n";
$users = User::getAll(100, 0);

if (empty($users)) {
    echo "Aucun utilisateur trouvé.\n";
} else {
    echo "ID\tNom d'utilisateur\tEmail\tAdmin\tActif\n";
    foreach ($users as $user) {
        echo $user->getId() . "\t" . 
             $user->getUsername() . "\t" . 
             $user->getEmail() . "\t" . 
             ($user->isAdmin() ? "Oui" : "Non") . "\t" . 
             ($user->isActive() ? "Oui" : "Non") . "\n";
    }
}

// Vérifier la structure de la table users
echo "\nStructure de la table users:\n";
$stmt = DatabaseManager::query("PRAGMA table_info(users);");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $column) {
    echo $column['name'] . " (" . $column['type'] . ")\n";
}

// Vérifier la présence de l'administrateur
$admin = User::findByUsername('admin');

if (!$admin) {
    echo "\nCréation de l'utilisateur admin...\n";
    // Créer un nouvel utilisateur admin
    $admin = User::create([
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'first_name' => 'Admin',
        'last_name' => 'User',
        'is_admin' => 1,
        'is_active' => 1
    ]);
    
    if ($admin) {
        echo "Utilisateur admin créé avec succès!\n";
        echo "Nom d'utilisateur: admin\n";
        echo "Mot de passe: admin123\n";
    } else {
        echo "Erreur lors de la création de l'utilisateur admin.\n";
    }
} else {
    echo "\nL'utilisateur admin existe déjà (ID: " . $admin->getId() . ").\n";
    
    // Récupérer le hash du mot de passe
    $sql = "SELECT password_hash FROM users WHERE id = :id";
    $stmt = DatabaseManager::query($sql, [':id' => $admin->getId()]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        echo "Hash du mot de passe actuel: " . $userData['password_hash'] . "\n";
        
        // Mettre à jour le mot de passe
        echo "Mise à jour du mot de passe...\n";
        $newPassword = 'admin123';
        
        $result = User::update($admin->getId(), [
            'password' => $newPassword
        ]);
        
        if ($result) {
            echo "Mot de passe mis à jour avec succès!\n";
            echo "Nouveau mot de passe: $newPassword\n";
            
            // Vérifier le nouveau hash
            $stmt = DatabaseManager::query($sql, [':id' => $admin->getId()]);
            $updatedData = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Nouveau hash du mot de passe: " . $updatedData['password_hash'] . "\n";
        } else {
            echo "Erreur lors de la mise à jour du mot de passe.\n";
        }
    } else {
        echo "Aucun hash de mot de passe trouvé pour cet utilisateur.\n";
    }
}

// Nettoyer les sessions existantes
echo "\nNettoyage des sessions existantes...\n";
try {
    DatabaseManager::query("DELETE FROM user_sessions WHERE user_id = :user_id", [':user_id' => $admin->getId()]);
    echo "Sessions nettoyées avec succès.\n";
} catch (Exception $e) {
    echo "Erreur lors du nettoyage des sessions: " . $e->getMessage() . "\n";
}

echo "\nScript terminé.\n";