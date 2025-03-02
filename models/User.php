<?php
require_once 'Model.php';

class User extends Model {
    protected $table = 'users';
    
    public function create(array $data) {
        $query = "INSERT INTO users (username, email, password, role) 
                  VALUES (:username, :email, :password, :role)";
                  
        $stmt = $this->db->prepare($query);
        
        $params = [
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role' => $data['role']
        ];
        
        if ($this->db->execute($stmt, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update(array $data, $id) {
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
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->db->execute($stmt, $params);
    }
    
    public function findByUsername($username) {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $params = [':username' => $username];
        $this->db->execute($stmt, $params);
        return $this->db->single($stmt);
    }
    
    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $params = [':email' => $email];
        $this->db->execute($stmt, $params);
        return $this->db->single($stmt);
    }
    
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            return $user;
        } else {
            return false;
        }
    }
    
    public function getUsersByRole($role) {
        $query = "SELECT * FROM users WHERE role = :role";
        $stmt = $this->db->prepare($query);
        $params = [':role' => $role];
        $this->db->execute($stmt, $params);
        return $this->db->resultSet($stmt);
    }
}
?>
