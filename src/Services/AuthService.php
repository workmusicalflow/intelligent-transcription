<?php

namespace Services;

use Database\DatabaseManager;
use Models\User;
use Utils\ResponseUtils;

/**
 * Authentication service for user authentication and session management
 */
class AuthService
{
    /**
     * Session key for authenticated user
     */
    const SESSION_USER_KEY = 'auth_user_id';
    
    /**
     * Session key for user CSRF token
     */
    const SESSION_CSRF_TOKEN = 'csrf_token';
    
    /**
     * Session expiry in seconds (default: 4 hours)
     */
    const SESSION_EXPIRY = 14400;
    
    /**
     * Cookie name for the session token
     */
    const COOKIE_NAME = 'auth_session';
    
    /**
     * @var User|null Currently authenticated user
     */
    private static $currentUser = null;
    
    /**
     * Initialize authentication system
     * 
     * @return void
     */
    public static function init()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookie parameters
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params(
                $cookieParams["lifetime"],
                $cookieParams["path"],
                $cookieParams["domain"],
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // secure
                true // httponly
            );
            
            session_start();
        }
        
        // Check if user is already authenticated in session
        if (isset($_SESSION[self::SESSION_USER_KEY])) {
            self::$currentUser = User::findById($_SESSION[self::SESSION_USER_KEY]);
        } else {
            // Check for persistent login cookie
            self::processRememberMeCookie();
        }
        
        // Ensure CSRF token exists
        if (!isset($_SESSION[self::SESSION_CSRF_TOKEN])) {
            $_SESSION[self::SESSION_CSRF_TOKEN] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Authenticate user with username/email and password
     * 
     * @param string $usernameOrEmail Username or email
     * @param string $password Password
     * @param bool $remember Remember login
     * @return array Authentication result
     */
    public static function authenticate($usernameOrEmail, $password, $remember = false)
    {
        try {
            // Debugging
            error_log("Authentication attempt: Username/Email: $usernameOrEmail");
            
            // Check if input is email or username
            $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
            
            // Find user by username or email
            $user = $isEmail
                ? User::findByEmail($usernameOrEmail)
                : User::findByUsername($usernameOrEmail);
            
            // Debugging - check if user was found
            if ($user) {
                error_log("User found with ID: " . $user->getId());
            } else {
                error_log("User not found for: $usernameOrEmail");
                return [
                    'success' => false,
                    'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'
                ];
            }
            
            // Check if user is active
            if (!$user->isActive()) {
                error_log("User is not active: " . $user->getId());
                return [
                    'success' => false,
                    'message' => 'Ce compte a été désactivé. Veuillez contacter l\'administrateur.'
                ];
            }
            
            // Get password hash from database
            $sql = "SELECT password_hash FROM users WHERE id = :id";
            $stmt = DatabaseManager::query($sql, [':id' => $user->getId()]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Debugging - log password hash details
            if ($userData) {
                error_log("Password hash found for user: " . $user->getId());
                $passwordVerified = User::verifyPassword($password, $userData['password_hash']);
                error_log("Password verification result: " . ($passwordVerified ? 'Success' : 'Failed'));
                
                if (!$passwordVerified) {
                    return [
                        'success' => false,
                        'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'
                    ];
                }
            } else {
                error_log("No password hash found for user: " . $user->getId());
                return [
                    'success' => false,
                    'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'
                ];
            }
            
            // Set as authenticated user
            error_log("Authentication successful for user ID: " . $user->getId());
            self::$currentUser = $user;
            $_SESSION[self::SESSION_USER_KEY] = $user->getId();
            
            // Set remember me cookie if requested
            if ($remember) {
                self::createRememberMeCookie($user->getId());
            }
            
            // Update last login time
            User::updateLastLogin($user->getId());
            
            // Create session record in database
            self::createSessionRecord($user->getId());
            
            return [
                'success' => true,
                'user' => $user->toArray()
            ];
        } catch (\Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'authentification.'
            ];
        }
    }
    
    /**
     * Log out the current user
     * 
     * @return bool True on success
     */
    public static function logout()
    {
        try {
            // Clear database session if we have a session ID
            if (isset($_COOKIE[self::COOKIE_NAME])) {
                $sessionId = $_COOKIE[self::COOKIE_NAME];
                
                // Invalidate session in database
                $sql = "UPDATE user_sessions SET is_active = 0 WHERE id = :id";
                DatabaseManager::query($sql, [':id' => $sessionId]);
                
                // Remove cookie
                setcookie(self::COOKIE_NAME, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
            }
            
            // Clear all session data
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
            
            // Reset current user
            self::$currentUser = null;
            
            return true;
        } catch (\Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current authenticated user
     * 
     * @return User|null Current user or null if not authenticated
     */
    public static function getCurrentUser()
    {
        return self::$currentUser;
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if user is authenticated
     */
    public static function isAuthenticated()
    {
        return self::$currentUser !== null;
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool True if current user is admin
     */
    public static function isAdmin()
    {
        return self::isAuthenticated() && self::$currentUser->isAdmin();
    }
    
    /**
     * Check if current user has a specific permission
     * 
     * @param string $permission Permission to check
     * @return bool True if user has the permission
     */
    public static function hasPermission($permission)
    {
        return self::isAuthenticated() && self::$currentUser->checkUserPermission($permission);
    }
    
    /**
     * Get CSRF token for the current session
     * 
     * @return string CSRF token
     */
    public static function getCsrfToken()
    {
        return $_SESSION[self::SESSION_CSRF_TOKEN] ?? '';
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if token is valid
     */
    public static function verifyCsrfToken($token)
    {
        return isset($_SESSION[self::SESSION_CSRF_TOKEN]) && hash_equals($_SESSION[self::SESSION_CSRF_TOKEN], $token);
    }
    
    /**
     * Require authentication, redirect to login if not authenticated
     * 
     * @param string $redirectUrl URL to redirect to after login
     * @return void
     */
    public static function requireAuth($redirectUrl = null)
    {
        if (!self::isAuthenticated()) {
            $url = '/login';
            
            if ($redirectUrl) {
                $url .= '?redirect=' . urlencode($redirectUrl);
            }
            
            ResponseUtils::redirect($url);
            exit;
        }
    }
    
    /**
     * Require admin permission, redirect to dashboard if not admin
     * 
     * @return void
     */
    public static function requireAdmin()
    {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            ResponseUtils::redirect('/dashboard');
            exit;
        }
    }
    
    /**
     * Require specific permission, redirect to dashboard if not allowed
     * 
     * @param string $permission Permission required
     * @return void
     */
    public static function requirePermission($permission)
    {
        self::requireAuth();
        
        if (!self::hasPermission($permission)) {
            ResponseUtils::redirect('/dashboard');
            exit;
        }
    }
    
    /**
     * Create a remember me cookie
     * 
     * @param int $userId User ID
     * @return bool True on success
     */
    private static function createRememberMeCookie($userId)
    {
        try {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = time() + (86400 * 30); // 30 days
            
            // Create remember token in database
            $sessionId = bin2hex(random_bytes(16));
            
            // Store token in database
            $sql = "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
                    VALUES (:id, :user_id, :ip_address, :user_agent, datetime(:expires, 'unixepoch'))";
            
            DatabaseManager::query($sql, [
                ':id' => $sessionId,
                ':user_id' => $userId,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ':expires' => $expires
            ]);
            
            // Set cookie
            setcookie(
                self::COOKIE_NAME,
                $sessionId,
                $expires,
                '/',
                '',
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                true
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating remember me cookie: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process remember me cookie for auto-login
     * 
     * @return bool True if auto-login successful
     */
    private static function processRememberMeCookie()
    {
        try {
            // Check if cookie exists
            if (!isset($_COOKIE[self::COOKIE_NAME])) {
                return false;
            }
            
            $sessionId = $_COOKIE[self::COOKIE_NAME];
            
            // Look up session
            $sql = "SELECT user_id, expires_at 
                    FROM user_sessions 
                    WHERE id = :id 
                    AND is_active = 1 
                    AND datetime('now') < expires_at";
                    
            $stmt = DatabaseManager::query($sql, [':id' => $sessionId]);
            $session = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$session) {
                // Invalid or expired session, clear cookie
                setcookie(self::COOKIE_NAME, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
                return false;
            }
            
            // Load user
            $user = User::findById($session['user_id']);
            
            if (!$user || !$user->isActive()) {
                return false;
            }
            
            // Auto-login
            self::$currentUser = $user;
            $_SESSION[self::SESSION_USER_KEY] = $user->getId();
            
            // Update session last activity
            $sql = "UPDATE user_sessions SET last_activity = CURRENT_TIMESTAMP WHERE id = :id";
            DatabaseManager::query($sql, [':id' => $sessionId]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error processing remember me cookie: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a session record in the database
     * 
     * @param int $userId User ID
     * @return bool True on success
     */
    private static function createSessionRecord($userId)
    {
        try {
            // Generate session ID if not already set by remember me
            if (!isset($_COOKIE[self::COOKIE_NAME])) {
                $sessionId = bin2hex(random_bytes(16));
                
                // Calculate expiry time
                $expires = time() + self::SESSION_EXPIRY;
                
                // Store session in database
                $sql = "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
                        VALUES (:id, :user_id, :ip_address, :user_agent, datetime(:expires, 'unixepoch'))";
                
                DatabaseManager::query($sql, [
                    ':id' => $sessionId,
                    ':user_id' => $userId,
                    ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    ':expires' => $expires
                ]);
                
                // Set session cookie
                setcookie(
                    self::COOKIE_NAME,
                    $sessionId,
                    $expires,
                    '/',
                    '',
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    true
                );
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating session record: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up expired sessions
     * 
     * @return int Number of deleted sessions
     */
    public static function cleanupExpiredSessions()
    {
        try {
            $sql = "DELETE FROM user_sessions WHERE datetime('now') > expires_at";
            $stmt = DatabaseManager::query($sql);
            
            return $stmt->rowCount();
        } catch (\Exception $e) {
            error_log("Error cleaning up expired sessions: " . $e->getMessage());
            return 0;
        }
    }
}