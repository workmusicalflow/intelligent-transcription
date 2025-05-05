<?php

namespace Controllers;

use Services\AuthService;
use Models\User;
use Template\TwigManager;
use Utils\ResponseUtils;
use Database\DatabaseManager;

/**
 * Controller for user management
 */
class UserController
{
    /**
     * @var TwigManager Twig template manager
     */
    private $twig;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->twig = TwigManager::getInstance();
        
        // Initialize authentication
        AuthService::init();
    }
    
    /**
     * Show user list page (admin only)
     * 
     * @return string Rendered template
     */
    public function index()
    {
        // Require admin permission
        AuthService::requirePermission('users.manage');
        
        // Pagination parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Get users
        $users = User::getAll($limit, $offset);
        $totalUsers = User::countAll();
        $totalPages = ceil($totalUsers / $limit);
        
        // Render user list template
        return $this->twig->render('admin/users/index.twig', [
            'users' => array_map(function($user) { return $user->toArray(); }, $users),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalUsers
            ],
            'csrf_token' => AuthService::getCsrfToken(),
            'error' => isset($_GET['error']) ? $_GET['error'] : null,
            'success' => isset($_GET['success']) ? $_GET['success'] : null
        ]);
    }
    
    /**
     * Show user create form (admin only)
     * 
     * @return string Rendered template
     */
    public function create()
    {
        // Require admin permission
        AuthService::requirePermission('users.manage');
        
        // Render user create form
        return $this->twig->render('admin/users/create.twig', [
            'csrf_token' => AuthService::getCsrfToken()
        ]);
    }
    
    /**
     * Process user creation (admin only)
     * 
     * @return void
     */
    public function store()
    {
        // Require admin permission
        AuthService::requirePermission('users.manage');
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !AuthService::verifyCsrfToken($_POST['csrf_token'])) {
            ResponseUtils::redirectWithError('admin/users', 'Invalid CSRF token. Please try again.');
            exit;
        }
        
        // Validate form data
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';
        
        // Validate required fields
        if (empty($username) || empty($email) || empty($password)) {
            ResponseUtils::redirectWithError('admin/users/create', 'Veuillez remplir tous les champs obligatoires.');
            exit;
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseUtils::redirectWithError('admin/users/create', 'L\'adresse email n\'est pas valide.');
            exit;
        }
        
        // Validate password length
        if (strlen($password) < 8) {
            ResponseUtils::redirectWithError('admin/users/create', 'Le mot de passe doit contenir au moins 8 caractères.');
            exit;
        }
        
        // Create user
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_admin' => $isAdmin
        ];
        
        $user = User::create($userData);
        
        if (!$user) {
            ResponseUtils::redirectWithError('admin/users/create', 'Une erreur est survenue lors de la création de l\'utilisateur. Vérifiez que le nom d\'utilisateur et l\'email ne sont pas déjà utilisés.');
            exit;
        }
        
        // Redirect to user list
        ResponseUtils::redirectWithSuccess('admin/users', 'L\'utilisateur a été créé avec succès.');
    }
    
    /**
     * Show user edit form (admin only)
     * 
     * @param int $id User ID
     * @return string Rendered template
     */
    public function edit($id)
    {
        // Require admin permission
        AuthService::requirePermission('users.manage');
        
        // Get user
        $user = User::findById($id);
        
        // Check if user exists
        if (!$user) {
            ResponseUtils::redirectWithError('admin/users', 'L\'utilisateur demandé n\'existe pas.');
            exit;
        }
        
        // Get user permissions
        $permissions = $user->getPermissions();
        
        // Render user edit form
        return $this->twig->render('admin/users/edit.twig', [
            'user' => $user->toArray(true),
            'permissions' => $permissions,
            'csrf_token' => AuthService::getCsrfToken(),
            'error' => isset($_GET['error']) ? $_GET['error'] : null,
            'success' => isset($_GET['success']) ? $_GET['success'] : null
        ]);
    }
    
    /**
     * Process user update (admin only)
     * 
     * @param int $id User ID
     * @return void
     */
    public function update($id)
    {
        // Require admin permission
        AuthService::requirePermission('users.manage');
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !AuthService::verifyCsrfToken($_POST['csrf_token'])) {
            ResponseUtils::redirectWithError("admin/users/edit/{$id}", 'Invalid CSRF token. Please try again.');
            exit;
        }
        
        // Get user
        $user = User::findById($id);
        
        // Check if user exists
        if (!$user) {
            ResponseUtils::redirectWithError('admin/users', 'L\'utilisateur demandé n\'existe pas.');
            exit;
        }
        
        // Get current user (to prevent self-demotion)
        $currentUser = AuthService::getCurrentUser();
        
        // Prevent admin from removing their own admin status
        if ($user->getId() === $currentUser->getId() && $user->isAdmin() && !isset($_POST['is_admin'])) {
            ResponseUtils::redirectWithError("admin/users/edit/{$id}", 'Vous ne pouvez pas supprimer votre propre statut d\'administrateur.');
            exit;
        }
        
        // Validate form data
        $email = $_POST['email'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        $password = $_POST['password'] ?? '';
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseUtils::redirectWithError("admin/users/edit/{$id}", 'L\'adresse email n\'est pas valide.');
            exit;
        }
        
        // Check if email is already in use by another user
        if ($email !== $user->getEmail()) {
            $existingUser = User::findByEmail($email);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                ResponseUtils::redirectWithError("admin/users/edit/{$id}", 'Cette adresse email est déjà utilisée.');
                exit;
            }
        }
        
        // Validate password if set
        if (!empty($password) && strlen($password) < 8) {
            ResponseUtils::redirectWithError("admin/users/edit/{$id}", 'Le mot de passe doit contenir au moins 8 caractères.');
            exit;
        }
        
        // Update user data
        $userData = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_admin' => $isAdmin,
            'is_active' => $isActive
        ];
        
        // Add password to update data if provided
        if (!empty($password)) {
            $userData['password'] = $password;
        }
        
        // Update user
        $success = User::update($user->getId(), $userData);
        
        if (!$success) {
            ResponseUtils::redirectWithError("admin/users/edit/{$id}", 'Une erreur est survenue lors de la mise à jour de l\'utilisateur.');
            exit;
        }
        
        // Update permissions
        // Clear existing permissions (except admin.access which is handled by is_admin)
        $sql = "DELETE FROM user_permissions WHERE user_id = :user_id AND permission != 'admin.access'";
        DatabaseManager::query($sql, [':user_id' => $user->getId()]);
        
        // Add selected permissions
        $availablePermissions = [
            'transcriptions.own',
            'transcriptions.all',
            'users.view'
        ];
        
        foreach ($availablePermissions as $permission) {
            if (isset($_POST['permissions']) && in_array($permission, $_POST['permissions'])) {
                User::addPermission($user->getId(), $permission);
            }
        }
        
        // Handle admin.access permission separately based on is_admin
        if ($isAdmin) {
            User::addPermission($user->getId(), 'admin.access');
            User::addPermission($user->getId(), 'users.manage');
        } else {
            // Remove admin permissions
            User::removePermission($user->getId(), 'admin.access');
            User::removePermission($user->getId(), 'users.manage');
        }
        
        // Redirect back to edit page
        ResponseUtils::redirectWithSuccess("admin/users/edit/{$id}", 'L\'utilisateur a été mis à jour avec succès.');
    }
    
    /**
     * Delete user (admin only)
     * 
     * @param int $id User ID
     * @return void
     */
    public function delete($id)
    {
        // Require admin permission
        AuthService::requirePermission('users.manage');
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !AuthService::verifyCsrfToken($_POST['csrf_token'])) {
            ResponseUtils::redirectWithError('admin/users', 'Invalid CSRF token. Please try again.');
            exit;
        }
        
        // Get user
        $user = User::findById($id);
        
        // Check if user exists
        if (!$user) {
            ResponseUtils::redirectWithError('admin/users', 'L\'utilisateur demandé n\'existe pas.');
            exit;
        }
        
        // Get current user
        $currentUser = AuthService::getCurrentUser();
        
        // Prevent admin from deleting themselves
        if ($user->getId() === $currentUser->getId()) {
            ResponseUtils::redirectWithError('admin/users', 'Vous ne pouvez pas supprimer votre propre compte.');
            exit;
        }
        
        // Delete user
        $success = User::delete($user->getId());
        
        if (!$success) {
            ResponseUtils::redirectWithError('admin/users', 'Une erreur est survenue lors de la suppression de l\'utilisateur.');
            exit;
        }
        
        // Redirect to user list
        ResponseUtils::redirectWithSuccess('admin/users', 'L\'utilisateur a été supprimé avec succès.');
    }
}