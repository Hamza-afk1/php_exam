<?php
// Load configuration
require_once __DIR__ . '/config/config.php';

// Connect to MySQL without selecting a database
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "<h1>Database Setup</h1>";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $dbExists = $stmt->rowCount() > 0;
    
    if (!$dbExists) {
        echo "<p>Creating database '" . DB_NAME . "'...</p>";
        
        // Create the database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        echo "<p>Database created successfully!</p>";
        
        // Connect to the newly created database
        $pdo->exec("USE " . DB_NAME);
        
        // Import the schema
        echo "<p>Importing schema...</p>";
        
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        
        // Split the SQL file to execute each query separately
        $queries = preg_split('/;\s*$/m', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    echo "<p>Error importing schema: " . $e->getMessage() . "</p>";
                    echo "<p>Query: " . $query . "</p>";
                }
            }
        }
        
        echo "<p>Schema imported successfully!</p>";
        
        // Create admin user with password: admin123
        $username = 'admin';
        $password = 'admin123';
        $email = 'admin@example.com';
        $role = 'admin';
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if admin user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userExists = $stmt->rowCount() > 0;
        
        if (!$userExists) {
            // Insert admin user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            
            echo "<p>Admin user created with:</p>";
            echo "<ul>";
            echo "<li>Username: admin</li>";
            echo "<li>Password: admin123</li>";
            echo "</ul>";
        } else {
            echo "<p>Admin user already exists.</p>";
        }
    } else {
        echo "<p>Database '" . DB_NAME . "' already exists.</p>";
        
        // Connect to the existing database
        $pdo->exec("USE " . DB_NAME);
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $userExists = $stmt->rowCount() > 0;
        
        if (!$userExists) {
            // Create admin user with password: admin123
            $username = 'admin';
            $password = 'admin123';
            $email = 'admin@example.com';
            $role = 'admin';
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert admin user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            
            echo "<p>Admin user created with:</p>";
            echo "<ul>";
            echo "<li>Username: admin</li>";
            echo "<li>Password: admin123</li>";
            echo "</ul>";
        } else {
            echo "<p>Admin user already exists.</p>";
            
            // Update admin password for testing
            $password = 'admin123';
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->execute([$hashedPassword, 'admin']);
            
            echo "<p>Admin password updated to: admin123</p>";
        }
    }
    
    echo "<p>Setup completed successfully!</p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
