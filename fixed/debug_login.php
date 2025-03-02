<?php
// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/User.php';

// Set debug credentials
$username = 'admin';
$password = 'admin123';

// Initialize database connection directly
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "<h1>Login Debugging</h1>";
    
    // 1. Check if database exists
    echo "<h2>1. Database Connection</h2>";
    echo "<p style='color:green;'>✅ Successfully connected to database: " . DB_NAME . "</p>";
    
    // 2. Check if users table exists
    echo "<h2>2. Users Table Check</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✅ Users table exists</p>";
    } else {
        echo "<p style='color:red;'>❌ Users table does not exist!</p>";
        die("You need to run setup_database.php to create the tables");
    }
    
    // 3. Check if admin user exists
    echo "<h2>3. Admin User Check</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color:green;'>✅ Admin user found with username: " . htmlspecialchars($username) . "</p>";
        echo "<p>User ID: " . $user['id'] . "</p>";
        echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
        echo "<p>Role: " . htmlspecialchars($user['role']) . "</p>";
        
        // 4. Check password hash
        echo "<h2>4. Password Verification</h2>";
        echo "<p>Stored password hash: " . $user['password'] . "</p>";
        
        if (password_verify($password, $user['password'])) {
            echo "<p style='color:green;'>✅ Password verification successful!</p>";
        } else {
            echo "<p style='color:red;'>❌ Password verification failed!</p>";
            
            // Let's fix the password
            echo "<h3>Fixing password hash:</h3>";
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            echo "<p>New password hash: " . $hashedPassword . "</p>";
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            if ($stmt->rowCount() > 0) {
                echo "<p style='color:green;'>✅ Admin password updated successfully!</p>";
            } else {
                echo "<p style='color:red;'>❌ Failed to update password</p>";
            }
        }
    } else {
        echo "<p style='color:red;'>❌ Admin user not found!</p>";
        
        // Let's create the admin user
        echo "<h3>Creating admin user:</h3>";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$username, 'admin@example.com', $hashedPassword]);
        
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green;'>✅ Admin user created successfully!</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to create admin user</p>";
        }
    }
    
    // 5. Check User model
    echo "<h2>5. Testing User Model Authentication</h2>";
    $userModel = new User();
    $authUser = $userModel->authenticate($username, $password);
    
    if ($authUser) {
        echo "<p style='color:green;'>✅ User::authenticate() method works correctly!</p>";
    } else {
        echo "<p style='color:red;'>❌ User::authenticate() method failed!</p>";
    }
    
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 5px;'>";
    echo "<h3>Login Credentials (For Testing):</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "</div>";
    
    echo "<p><a href='login.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Try Login Again</a></p>";
    
} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p>The database doesn't exist. Please run the setup script:</p>";
        echo "<a href='setup_database.php' style='display:inline-block; padding:10px 20px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Run Database Setup</a>";
    }
}
?>
