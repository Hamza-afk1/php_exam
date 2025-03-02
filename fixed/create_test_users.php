<?php
// Include database connection
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';

// Create user model
$userModel = new User();

// Check if users already exist to avoid duplicates
$existingFormateur = $userModel->findByUsername('formateur');
$existingStag = $userModel->findByUsername('stagiaire');

// Only create formateur if doesn't exist
if (!$existingFormateur) {
    // Create a formateur user
    $formateurData = [
        'username' => 'formateur',
        'password' => 'formateur123', // Will be hashed in the create method
        'email' => 'formateur@example.com',
        'role' => 'formateur',
        'full_name' => 'Test Formateur'
    ];
    $result = $userModel->create($formateurData);
    if ($result) {
        echo "Formateur user created successfully!<br>";
    } else {
        echo "Error creating formateur user.<br>";
    }
} else {
    echo "Formateur user already exists.<br>";
}

// Only create stagiaire if doesn't exist
if (!$existingStag) {
    // Create a stagiaire user
    $stagiaireData = [
        'username' => 'stagiaire',
        'password' => 'stagiaire123', // Will be hashed in the create method
        'email' => 'stagiaire@example.com',
        'role' => 'stagiaire',
        'full_name' => 'Test Stagiaire'
    ];
    $result = $userModel->create($stagiaireData);
    if ($result) {
        echo "Stagiaire user created successfully!<br>";
    } else {
        echo "Error creating stagiaire user.<br>";
    }
} else {
    echo "Stagiaire user already exists.<br>";
}

echo "<hr>";
echo "You can now login with:<br>";
echo "Formateur: username 'formateur', password 'formateur123'<br>";
echo "Stagiaire: username 'stagiaire', password 'stagiaire123'<br>";
echo "<hr>";
echo "<a href='login.php'>Go to Login Page</a>";
?>
