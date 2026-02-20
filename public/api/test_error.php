<?php
// Simple test to check PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing PHP error handling...\n";
echo "=============================\n\n";

// Test 1: Check if we can connect to database
try {
    $db = new PDO('sqlite:viadal_database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";

    // Check if students table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='students'");
    if ($result->fetch()) {
        echo "✅ Students table exists\n";
    } else {
        echo "❌ Students table does not exist\n";
        echo "Creating students table...\n";

        $createTable = "CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            s1_name TEXT,
            s1_email TEXT,
            s2_name TEXT,
            s2_email TEXT,
            s3_name TEXT,
            s3_email TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $db->exec($createTable);
        echo "✅ Students table created\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 2: Check PHPMailer inclusion
echo "\n\nTest 2: PHPMailer inclusion\n";
echo "===========================\n";

try {
    require_once 'libs/PHPMailer/Exception.php';
    require_once 'libs/PHPMailer/PHPMailer.php';
    require_once 'libs/PHPMailer/SMTP.php';

    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✅ PHPMailer classes loaded successfully\n";
    } else {
        echo "❌ PHPMailer classes not found\n";
    }
} catch (Exception $e) {
    echo "❌ PHPMailer error: " . $e->getMessage() . "\n";
}

// Test 3: Test the actual handler.php
echo "\n\nTest 3: Testing handler.php directly\n";
echo "====================================\n";

// Simulate a POST request to handler.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'register_student';

// Test data
$testData = [
    'school' => 'Test Iskola',
    'email' => 'test' . time() . '@example.com',
    'password' => 'testpassword123',
    's1_n' => 'Test Teacher 1',
    's1_e' => 'teacher1@example.com'
];

// Convert to JSON for php://input simulation
$jsonData = json_encode($testData);

echo "Test data:\n";
print_r($testData);
echo "\n";

// We can't directly test the handler because it expects php://input
// But we can check if it runs without syntax errors
echo "Checking handler.php syntax...\n";
$output = shell_exec('php -l handler.php 2>&1');
if (strpos($output, 'No syntax errors detected') !== false) {
    echo "✅ handler.php has no syntax errors\n";
} else {
    echo "❌ handler.php has syntax errors:\n";
    echo $output . "\n";
}

echo "\n\nTest complete!\n";
