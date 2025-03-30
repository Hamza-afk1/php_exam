<?php
require_once 'Model.php';

class User extends Model {
    protected $table = 'users';
    
    public function create(array $data) {
        try {
            error_log("User::create - Starting to create user: " . $data['username']);
            
            $query = "INSERT INTO users (username, email, password, role) 
                      VALUES (:username, :email, :password, :role)";
                      
            $stmt = $this->db->prepare($query);
            
            // Hash the password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            error_log("User::create - Password hashed for user: " . $data['username']);
            
            $params = [
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $hashedPassword,
                ':role' => $data['role']
            ];
            
            if ($this->db->execute($stmt, $params)) {
                $userId = $this->db->lastInsertId();
                error_log("User::create - User created successfully with ID: " . $userId);
                return $userId;
            }
            
            error_log("User::create - Failed to create user");
            return false;
        } catch (Exception $e) {
            error_log("User::create - Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update(array $data, $id) {
        try {
            error_log("User::update - Starting to update user ID: " . $id);
            
            $query = "UPDATE users SET 
                      username = :username, 
                      email = :email";
            
            // Only update password if it's provided
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }
            
            $query .= ", role = :role 
                      WHERE id = :id";
                      
            $stmt = $this->db->prepare($query);
            
            $params = [
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':role' => $data['role'],
                ':id' => $id
            ];
            
            // Add password to params if it exists
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $params[':password'] = $hashedPassword;
                error_log("User::update - Password updated and hashed for user ID: " . $id);
            }
            
            $result = $this->db->execute($stmt, $params);
            if ($result) {
                error_log("User::update - User updated successfully");
            } else {
                error_log("User::update - Failed to update user");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("User::update - Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function findByUsername($username) {
        try {
            $query = "SELECT * FROM users WHERE username = :username";
            $stmt = $this->db->prepare($query);
            $params = [':username' => $username];
            
            error_log("Finding user by username: " . $username);
            
            if (!$this->db->execute($stmt, $params)) {
                error_log("Failed to execute findByUsername query");
                return false;
            }
            
            $user = $this->db->single($stmt);
            error_log("Found user: " . ($user ? "Yes" : "No"));
            if ($user) {
                error_log("User role: " . $user['role']);
            }
            
            return $user;
        } catch (Exception $e) {
            error_log("Error in findByUsername: " . $e->getMessage());
            return false;
        }
    }
    
    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        return $this->db->single($stmt, [':email' => $email]);
    }
    
    public function authenticate($username, $password) {
        try {
            error_log("User::authenticate - Starting authentication for username: " . $username);
            
            $user = $this->findByUsername($username);
            
            if (!$user) {
                error_log("User::authenticate - User not found: " . $username);
                return false;
            }
            
            error_log("User::authenticate - Found user. Role: " . $user['role']);
            error_log("User::authenticate - Verifying password for: " . $username);
            
            // Password verification debugging
            $passwordLength = strlen($password);
            $hashLength = strlen($user['password']);
            error_log("User::authenticate - Password length: " . $passwordLength . ", Hash length: " . $hashLength);
            error_log("User::authenticate - Password first 3 chars: " . substr($password, 0, 3));
            error_log("User::authenticate - Hash algorithm: " . (strpos($user['password'], '$2y$') === 0 ? 'bcrypt' : 'unknown'));
            
            // Debug the hash info
            $hashInfo = password_get_info($user['password']);
            error_log("User::authenticate - Hash info: " . print_r($hashInfo, true));
            
            // Try password verification
            $verifyResult = password_verify($password, $user['password']);
            error_log("User::authenticate - Password verification result: " . ($verifyResult ? "true" : "false"));
            
            if ($verifyResult) {
                error_log("User::authenticate - Password verification successful for user: " . $username);
                return $user;
            } else {
                error_log("User::authenticate - Password verification FAILED for user: " . $username);
                return false;
            }
        } catch (Exception $e) {
            error_log("User::authenticate - Error: " . $e->getMessage());
            error_log("User::authenticate - Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function getUsersByRole($role) {
        $query = "SELECT * FROM users WHERE role = :role";
        $stmt = $this->db->prepare($query);
        return $this->db->resultSet($stmt, [':role' => $role]);
    }
}
?>
