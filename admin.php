<?php

/**
 * Entry point for admin panel
 */

require_once __DIR__ . '/src/bootstrap.php';

use Controllers\UserController;
use Services\AuthService;

// Initialize authentication
AuthService::init();

// Require admin permission
AuthService::requirePermission('admin.access');

// Get controller and action from the request
$controller = $_GET['controller'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Route the request to the appropriate controller
switch ($controller) {
    case 'user':
        $userController = new UserController();
        
        switch ($action) {
            case 'index':
                echo $userController->index();
                break;
                
            case 'create':
                echo $userController->create();
                break;
                
            case 'store':
                $userController->store();
                break;
                
            case 'edit':
                if (!$id) {
                    header('Location: admin.php?controller=user');
                    exit;
                }
                echo $userController->edit($id);
                break;
                
            case 'update':
                if (!$id) {
                    header('Location: admin.php?controller=user');
                    exit;
                }
                $userController->update($id);
                break;
                
            case 'delete':
                if (!$id) {
                    header('Location: admin.php?controller=user');
                    exit;
                }
                $userController->delete($id);
                break;
                
            default:
                header('Location: admin.php?controller=user');
                exit;
        }
        break;
        
    case 'dashboard':
    default:
        // Redirect to user management for now (could be a dashboard in the future)
        header('Location: admin.php?controller=user');
        exit;
}