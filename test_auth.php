<?php

/**
 * Script pour tester l'authentification manuellement
 */

require_once __DIR__ . '/src/bootstrap.php';

use Models\User;
use Services\AuthService;
use Database\DatabaseManager;

// Initialiser l'authentification
AuthService::init();

// Paramètres d'authentification
$username = 'admin';
$password = 'admin123';

echo "Test d'authentification pour l'utilisateur: $username\n";

// Récupérer l'utilisateur
$user = User::findByUsername($username);
if (!$user) {
    echo "Erreur: Utilisateur non trouvé.\n";
    exit(1);
}

echo "Utilisateur trouvé avec ID: " . $user->getId() . "\n";
echo "Email: " . $user->getEmail() . "\n";
echo "Statut admin: " . ($user->isAdmin() ? "Oui" : "Non") . "\n";
echo "Statut actif: " . ($user->isActive() ? "Oui" : "Non") . "\n";

// Récupérer le hash du mot de passe
$sql = "SELECT password_hash FROM users WHERE id = :id";
$stmt = DatabaseManager::query($sql, [':id' => $user->getId()]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData) {
    echo "Hash du mot de passe: " . $userData['password_hash'] . "\n";
    
    // Vérifier le mot de passe
    $isValid = User::verifyPassword($password, $userData['password_hash']);
    echo "Vérification du mot de passe: " . ($isValid ? "Succès" : "Échec") . "\n";
    
    if (!$isValid) {
        // Créer un nouveau hash pour test
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        echo "Nouveau hash généré: $newHash\n";
        
        // Vérifier avec le nouveau hash
        $isValidNew = password_verify($password, $newHash);
        echo "Vérification avec le nouveau hash: " . ($isValidNew ? "Succès" : "Échec") . "\n";
    }
} else {
    echo "Aucun hash de mot de passe trouvé pour cet utilisateur.\n";
}

// Tester l'authentification complète
echo "\nTest d'authentification complète...\n";
$result = AuthService::authenticate($username, $password, false);

echo "Résultat: " . ($result['success'] ? "Succès" : "Échec") . "\n";
if (!$result['success']) {
    echo "Message d'erreur: " . $result['message'] . "\n";
} else {
    echo "Authentification réussie!\n";
    
    // Vérifier si l'utilisateur est bien défini dans la session
    if (isset($_SESSION[AuthService::SESSION_USER_KEY])) {
        echo "ID utilisateur en session: " . $_SESSION[AuthService::SESSION_USER_KEY] . "\n";
    } else {
        echo "Erreur: ID utilisateur non défini dans la session.\n";
    }
}

echo "\nScript terminé.\n";