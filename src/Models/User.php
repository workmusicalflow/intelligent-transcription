<?php

namespace Models;

use Database\DatabaseManager;

/**
 * User model for authentication and user management
 */
class User
{
    /**
     * @var int User ID
     */
    private $id;
    
    /**
     * @var string Username
     */
    private $username;
    
    /**
     * @var string Email address
     */
    private $email;
    
    /**
     * @var string First name
     */
    private $firstName;
    
    /**
     * @var string Last name
     */
    private $lastName;
    
    /**
     * @var bool Admin status
     */
    private $isAdmin;
    
    /**
     * @var bool Active status
     */
    private $isActive;
    
    /**
     * @var string Created at timestamp
     */
    private $createdAt;
    
    /**
     * @var string Updated at timestamp
     */
    private $updatedAt;
    
    /**
     * @var string Last login timestamp
     */
    private $lastLogin;
    
    /**
     * @var array User permissions
     */
    private $permissions = [];
    
    /**
     * Find a user by ID
     * 
     * @param int $id User ID
     * @return User|null User object or null if not found
     */
    public static function findById($id)
    {
        try {
            $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
            $stmt = DatabaseManager::query($sql, [':id' => $id]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$userData) {
                return null;
            }
            
            return self::createFromArray($userData);
        } catch (\Exception $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a user by username
     * 
     * @param string $username Username
     * @return User|null User object or null if not found
     */
    public static function findByUsername($username)
    {
        try {
            $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = DatabaseManager::query($sql, [':username' => $username]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$userData) {
                return null;
            }
            
            return self::createFromArray($userData);
        } catch (\Exception $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email Email address
     * @return User|null User object or null if not found
     */
    public static function findByEmail($email)
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = DatabaseManager::query($sql, [':email' => strtolower($email)]);
            $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$userData) {
                return null;
            }
            
            return self::createFromArray($userData);
        } catch (\Exception $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all users
     * 
     * @param int $limit Limit number of results
     * @param int $offset Offset for pagination
     * @return array Array of User objects
     */
    public static function getAll($limit = 10, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM users ORDER BY id ASC LIMIT :limit OFFSET :offset";
            $stmt = DatabaseManager::query($sql, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $users = [];
            while ($userData = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $users[] = self::createFromArray($userData);
            }
            
            return $users;
        } catch (\Exception $e) {
            error_log("Error getting all users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count total users
     * 
     * @return int Total number of users
     */
    public static function countAll()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM users";
            $stmt = DatabaseManager::query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return (int) $result['total'];
        } catch (\Exception $e) {
            error_log("Error counting users: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data
     * @return User|false New user object or false on failure
     */
    public static function create($userData)
    {
        try {
            // Validate required fields
            if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
                return false;
            }
            
            // Check if username or email already exists
            if (self::findByUsername($userData['username']) || self::findByEmail($userData['email'])) {
                return false;
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
            
            // Prepare SQL
            $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, is_active) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name, :is_admin, :is_active)";
            
            // Execute query
            DatabaseManager::query($sql, [
                ':username' => $userData['username'],
                ':email' => strtolower($userData['email']),
                ':password_hash' => $passwordHash,
                ':first_name' => $userData['first_name'] ?? null,
                ':last_name' => $userData['last_name'] ?? null,
                ':is_admin' => isset($userData['is_admin']) ? (int)$userData['is_admin'] : 0,
                ':is_active' => isset($userData['is_active']) ? (int)$userData['is_active'] : 1
            ]);
            
            // Get new user ID
            $userId = DatabaseManager::lastInsertId();
            
            // Add default permissions
            if (isset($userData['is_admin']) && $userData['is_admin']) {
                self::addPermission($userId, 'admin.access');
                self::addPermission($userId, 'users.manage');
            }
            
            self::addPermission($userId, 'transcriptions.own');
            
            // Return new user
            return self::findById($userId);
        } catch (\Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a user
     * 
     * @param int $userId User ID
     * @param array $userData User data to update
     * @return bool True on success, false on failure
     */
    public static function update($userId, $userData)
    {
        try {
            // Start with empty SQL parts
            $sqlParts = [];
            $params = [':id' => $userId];
            
            // Build SQL parts based on provided data
            if (isset($userData['email'])) {
                $sqlParts[] = "email = :email";
                $params[':email'] = strtolower($userData['email']);
            }
            
            if (isset($userData['first_name'])) {
                $sqlParts[] = "first_name = :first_name";
                $params[':first_name'] = $userData['first_name'];
            }
            
            if (isset($userData['last_name'])) {
                $sqlParts[] = "last_name = :last_name";
                $params[':last_name'] = $userData['last_name'];
            }
            
            if (isset($userData['is_admin'])) {
                $sqlParts[] = "is_admin = :is_admin";
                $params[':is_admin'] = (int)$userData['is_admin'];
            }
            
            if (isset($userData['is_active'])) {
                $sqlParts[] = "is_active = :is_active";
                $params[':is_active'] = (int)$userData['is_active'];
            }
            
            // Update password if provided
            if (!empty($userData['password'])) {
                $sqlParts[] = "password_hash = :password_hash";
                $params[':password_hash'] = password_hash($userData['password'], PASSWORD_BCRYPT);
            }
            
            // If no updates, return early
            if (empty($sqlParts)) {
                return true;
            }
            
            // Build and execute SQL
            $sql = "UPDATE users SET " . implode(", ", $sqlParts) . " WHERE id = :id";
            $stmt = DatabaseManager::query($sql, $params);
            
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a user
     * 
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public static function delete($userId)
    {
        try {
            // Start transaction
            DatabaseManager::beginTransaction();
            
            // Delete user sessions
            $sql = "DELETE FROM user_sessions WHERE user_id = :user_id";
            DatabaseManager::query($sql, [':user_id' => $userId]);
            
            // Delete user permissions
            $sql = "DELETE FROM user_permissions WHERE user_id = :user_id";
            DatabaseManager::query($sql, [':user_id' => $userId]);
            
            // Delete user password reset tokens
            $sql = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
            DatabaseManager::query($sql, [':user_id' => $userId]);
            
            // Delete user
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = DatabaseManager::query($sql, [':id' => $userId]);
            
            // Commit transaction
            DatabaseManager::commit();
            
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            // Rollback transaction on error
            DatabaseManager::rollback();
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add a permission to a user
     * 
     * @param int $userId User ID
     * @param string $permission Permission name
     * @return bool True on success, false on failure
     */
    public static function addPermission($userId, $permission)
    {
        try {
            $sql = "INSERT OR IGNORE INTO user_permissions (user_id, permission) VALUES (:user_id, :permission)";
            DatabaseManager::query($sql, [
                ':user_id' => $userId,
                ':permission' => $permission
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error adding permission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a permission from a user
     * 
     * @param int $userId User ID
     * @param string $permission Permission name
     * @return bool True on success, false on failure
     */
    public static function removePermission($userId, $permission)
    {
        try {
            $sql = "DELETE FROM user_permissions WHERE user_id = :user_id AND permission = :permission";
            DatabaseManager::query($sql, [
                ':user_id' => $userId,
                ':permission' => $permission
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error removing permission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a user has a specific permission
     * 
     * @param int $userId User ID
     * @param string $permission Permission name
     * @return bool True if user has permission, false otherwise
     */
    public static function hasPermission($userId, $permission)
    {
        try {
            // Admin users have all permissions
            $user = self::findById($userId);
            if ($user && $user->isAdmin()) {
                return true;
            }
            
            $sql = "SELECT COUNT(*) as count FROM user_permissions 
                    WHERE user_id = :user_id AND permission = :permission";
            $stmt = DatabaseManager::query($sql, [
                ':user_id' => $userId,
                ':permission' => $permission
            ]);
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (\Exception $e) {
            error_log("Error checking permission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all permissions for a user
     * 
     * @param int $userId User ID
     * @return array Array of permissions
     */
    public static function getPermissions($userId)
    {
        try {
            $sql = "SELECT permission FROM user_permissions WHERE user_id = :user_id";
            $stmt = DatabaseManager::query($sql, [':user_id' => $userId]);
            
            $permissions = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $permissions[] = $row['permission'];
            }
            
            // Add all permissions for admin users
            $user = self::findById($userId);
            if ($user && $user->isAdmin()) {
                $permissions = array_merge($permissions, [
                    'admin.access',
                    'users.manage',
                    'transcriptions.all'
                ]);
            }
            
            return array_unique($permissions);
        } catch (\Exception $e) {
            error_log("Error getting permissions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update last login timestamp
     * 
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public static function updateLastLogin($userId)
    {
        try {
            $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
            DatabaseManager::query($sql, [':id' => $userId]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify a password
     * 
     * @param string $password Plain password
     * @param string $hash Password hash
     * @return bool True if password is correct
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Create a User object from database row
     * 
     * @param array $userData User data from database
     * @return User User object
     */
    private static function createFromArray($userData)
    {
        $user = new self();
        $user->id = (int) $userData['id'];
        $user->username = $userData['username'];
        $user->email = $userData['email'];
        $user->firstName = $userData['first_name'];
        $user->lastName = $userData['last_name'];
        $user->isAdmin = (bool) $userData['is_admin'];
        $user->isActive = (bool) $userData['is_active'];
        $user->createdAt = $userData['created_at'];
        $user->updatedAt = $userData['updated_at'];
        $user->lastLogin = $userData['last_login'];
        
        // Load permissions
        $user->permissions = self::getPermissions($user->id);
        
        return $user;
    }
    
    /**
     * Get user ID
     * 
     * @return int User ID
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get username
     * 
     * @return string Username
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Get email
     * 
     * @return string Email
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * Get first name
     * 
     * @return string First name
     */
    public function getFirstName()
    {
        return $this->firstName;
    }
    
    /**
     * Get last name
     * 
     * @return string Last name
     */
    public function getLastName()
    {
        return $this->lastName;
    }
    
    /**
     * Get full name
     * 
     * @return string Full name
     */
    public function getFullName()
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool True if user is admin
     */
    public function isAdmin()
    {
        return $this->isAdmin;
    }
    
    /**
     * Check if user is active
     * 
     * @return bool True if user is active
     */
    public function isActive()
    {
        return $this->isActive;
    }
    
    /**
     * Get created at timestamp
     * 
     * @return string Created at timestamp
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Get updated at timestamp
     * 
     * @return string Updated at timestamp
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
    /**
     * Get last login timestamp
     * 
     * @return string Last login timestamp
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }
    
    /**
     * Get user permissions
     * 
     * @return array User permissions
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
    
    /**
     * Check if user has a specific permission
     * 
     * @param string $permission Permission name
     * @return bool True if user has permission
     */
    public function hasPermission($permission)
    {
        return $this->isAdmin || in_array($permission, $this->permissions);
    }
    
    /**
     * Convert user to array
     * 
     * @param bool $includePrivate Include private fields
     * @return array User data
     */
    public function toArray($includePrivate = false)
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'is_admin' => $this->isAdmin,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'permissions' => $this->permissions,
        ];
        
        if ($includePrivate) {
            $data['updated_at'] = $this->updatedAt;
            $data['last_login'] = $this->lastLogin;
        }
        
        return $data;
    }
}