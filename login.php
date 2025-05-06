<?php

/**
 * Entry point for authentication
 */

// Charger uniquement config.php du répertoire racine, pas src/config.php
require_once __DIR__ . '/config.php';

// Inclure ensuite le bootstrap
require_once __DIR__ . '/src/bootstrap.php';

use Controllers\AuthController;
use Utils\ResponseUtils; // Assuming ResponseUtils is in the Utils namespace

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
        ResponseUtils::redirect('login.php');
        exit;
}
