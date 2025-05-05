<?php

/**
 * Entry point for authentication
 */

require_once __DIR__ . '/src/bootstrap.php';

use Controllers\AuthController;

// Get the action from the request
$action = $_GET['action'] ?? 'login';

// Initialize controller
$controller = new AuthController();

// Route the request
switch ($action) {
    case 'login':
        echo $controller->showLoginForm();
        break;
        
    case 'do_login':
        $controller->login();
        break;
        
    case 'logout':
        $controller->logout();
        break;
        
    case 'password_reset':
        echo $controller->showPasswordResetForm();
        break;
        
    case 'request_reset':
        $controller->requestPasswordReset();
        break;
        
    default:
        // Redirect to login page for unknown actions
        header('Location: login.php');
        exit;
}