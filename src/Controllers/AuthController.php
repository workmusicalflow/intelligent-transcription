<?php

namespace Controllers;

use Services\AuthService;
use Models\User;
use Template\TwigManager;
use Utils\ResponseUtils;
use Utils\ValidationUtils;
use Database\DatabaseManager;

/**
 * Controller for authentication actions (login, logout, register, etc.)
 */
class AuthController
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
     * Display login form
     * 
     * @return string Rendered template
     */
    public function showLoginForm()
    {
        // Redirect to dashboard if already authenticated
        if (AuthService::isAuthenticated()) {
            ResponseUtils::redirect('/dashboard');
            exit;
        }
        
        // Get redirect URL
        $redirectUrl = isset($_GET['redirect']) ? $_GET['redirect'] : '/dashboard';
        
        // Log debug information
        error_log("Showing login form with GET params: " . json_encode($_GET));
        
        // Render login template
        return $this->twig->render('auth/login.twig', [
            'csrf_token' => AuthService::getCsrfToken(),
            'redirect_url' => $redirectUrl,
            'error' => isset($_GET['error']) ? $_GET['error'] : null,
            'success' => isset($_GET['success']) ? $_GET['success'] : null
        ]);
    }
    
    /**
     * Process login form
     * 
     * @return void
     */
    public function login()
    {
        // Redirect to dashboard if already authenticated
        if (AuthService::isAuthenticated()) {
            ResponseUtils::redirect('/dashboard');
            exit;
        }
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !AuthService::verifyCsrfToken($_POST['csrf_token'])) {
            ResponseUtils::redirectWithError('login', 'Invalid CSRF token. Please try again.');
            exit;
        }
        
        // Validate form data
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
        $redirectUrl = $_POST['redirect_url'] ?? '/dashboard';
        
        // Validate username and password
        if (empty($username) || empty($password)) {
            ResponseUtils::redirectWithError('login', 'Veuillez remplir tous les champs.');
            exit;
        }
        
        // Try to authenticate user
        $result = AuthService::authenticate($username, $password, $remember);
        
        if (!$result['success']) {
            ResponseUtils::redirectWithError('login', $result['message']);
            exit;
        }
        
        // Redirect to dashboard or requested page
        ResponseUtils::redirect($redirectUrl);
    }
    
    /**
     * Logout user
     * 
     * @return void
     */
    public function logout()
    {
        // Logout user
        AuthService::logout();
        
        // Redirect to login page
        ResponseUtils::redirectWithSuccess('login', 'Vous avez été déconnecté avec succès.');
    }
    
    /**
     * Show profile page
     * 
     * @return string Rendered template
     */
    public function showProfile()
    {
        // Require authentication
        AuthService::requireAuth();
        
        // Get current user
        $user = AuthService::getCurrentUser();
        
        // Render profile template
        return $this->twig->render('auth/profile.twig', [
            'user' => $user->toArray(),
            'csrf_token' => AuthService::getCsrfToken(),
            'error' => isset($_GET['error']) ? $_GET['error'] : null,
            'success' => isset($_GET['success']) ? $_GET['success'] : null
        ]);
    }
    
    /**
     * Update user profile
     * 
     * @return void
     */
    public function updateProfile()
    {
        // Require authentication
        AuthService::requireAuth();
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !AuthService::verifyCsrfToken($_POST['csrf_token'])) {
            ResponseUtils::redirectWithError('profile', 'Invalid CSRF token. Please try again.');
            exit;
        }
        
        // Get current user
        $user = AuthService::getCurrentUser();
        
        // Validate form data
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Update user data
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
        
        // Check if email is being changed
        if ($email !== $user->getEmail()) {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ResponseUtils::redirectWithError('profile', 'L\'adresse email n\'est pas valide.');
                exit;
            }
            
            // Check if email is already in use
            $existingUser = User::findByEmail($email);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                ResponseUtils::redirectWithError('profile', 'Cette adresse email est déjà utilisée.');
                exit;
            }
            
            // Add email to update data
            $userData['email'] = $email;
        }
        
        // Check if password is being changed
        if (!empty($newPassword)) {
            // Get current password hash
            $sql = "SELECT password_hash FROM users WHERE id = :id";
            $stmt = DatabaseManager::query($sql, [':id' => $user->getId()]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Verify current password
            if (!User::verifyPassword($currentPassword, $result['password_hash'])) {
                ResponseUtils::redirectWithError('profile', 'Le mot de passe actuel est incorrect.');
                exit;
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                ResponseUtils::redirectWithError('profile', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
                exit;
            }
            
            // Verify password confirmation
            if ($newPassword !== $confirmPassword) {
                ResponseUtils::redirectWithError('profile', 'Les nouveaux mots de passe ne correspondent pas.');
                exit;
            }
            
            // Add password to update data
            $userData['password'] = $newPassword;
        }
        
        // Update user
        $success = User::update($user->getId(), $userData);
        
        if (!$success) {
            ResponseUtils::redirectWithError('profile', 'Une erreur est survenue lors de la mise à jour du profil.');
            exit;
        }
        
        // Redirect to profile page
        ResponseUtils::redirectWithSuccess('profile', 'Votre profil a été mis à jour avec succès.');
    }
    
    /**
     * Show password reset request form
     * 
     * @return string Rendered template
     */
    public function showPasswordResetForm()
    {
        // Redirect to dashboard if already authenticated
        if (AuthService::isAuthenticated()) {
            ResponseUtils::redirect('/dashboard');
            exit;
        }
        
        // Render reset form template
        return $this->twig->render('auth/password_reset.twig', [
            'csrf_token' => AuthService::getCsrfToken(),
            'error' => isset($_GET['error']) ? $_GET['error'] : null,
            'success' => isset($_GET['success']) ? $_GET['success'] : null
        ]);
    }
    
    /**
     * Process password reset request
     * 
     * @return void
     */
    public function requestPasswordReset()
    {
        // Redirect to dashboard if already authenticated
        if (AuthService::isAuthenticated()) {
            ResponseUtils::redirect('/dashboard');
            exit;
        }
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !AuthService::verifyCsrfToken($_POST['csrf_token'])) {
            ResponseUtils::redirectWithError('password-reset', 'Invalid CSRF token. Please try again.');
            exit;
        }
        
        // Validate email
        $email = $_POST['email'] ?? '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseUtils::redirectWithError('password-reset', 'Veuillez fournir une adresse email valide.');
            exit;
        }
        
        // Find user by email
        $user = User::findByEmail($email);
        
        // Always show success message even if email doesn't exist (security)
        if (!$user) {
            ResponseUtils::redirectWithSuccess('password-reset', 'Si cette adresse email existe dans notre système, vous recevrez un email avec les instructions pour réinitialiser votre mot de passe.');
            exit;
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600; // 1 hour
        
        try {
            // Delete any existing tokens for this user
            $sql = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
            DatabaseManager::query($sql, [':user_id' => $user->getId()]);
            
            // Create new token
            $sql = "INSERT INTO password_reset_tokens (id, user_id, token, expires_at) 
                    VALUES (:id, :user_id, :token, datetime(:expires, 'unixepoch'))";
            
            DatabaseManager::query($sql, [
                ':id' => bin2hex(random_bytes(16)),
                ':user_id' => $user->getId(),
                ':token' => $token,
                ':expires' => $expires
            ]);
            
            // Build reset URL
            $resetUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/password-reset/confirm?token=' . $token;
            
            // In a real application, send email here
            // For this example, just log the URL
            error_log("Password reset URL: {$resetUrl}");
            
            // Redirect with success message
            ResponseUtils::redirectWithSuccess('password-reset', 'Un email a été envoyé avec les instructions pour réinitialiser votre mot de passe.');
        } catch (\Exception $e) {
            error_log("Error creating password reset token: " . $e->getMessage());
            ResponseUtils::redirectWithError('password-reset', 'Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }
}