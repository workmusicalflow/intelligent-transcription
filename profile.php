<?php

/**
 * Entry point for user profile
 */

require_once __DIR__ . '/src/bootstrap.php';

use Controllers\AuthController;

// Get the action from the request
$action = $_GET['action'] ?? 'show';

// Initialize controller
$controller = new AuthController();

// Route the request
switch ($action) {
    case 'show':
        echo $controller->showProfile();
        break;
        
    case 'update':
        $controller->updateProfile();
        break;
        
    case 'change_password':
        // In the AuthController the updateProfile method already handles password changes
        $controller->updateProfile();
        break;
        
    default:
        // Redirect to profile page for unknown actions
        header('Location: profile.php');
        exit;
}