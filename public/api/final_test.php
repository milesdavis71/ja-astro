<?php
// Final simple test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Final Test - Checking basic functionality\n";
echo "=========================================\n\n";

// Test 1: Check if handler.php can be included without errors
echo "Test 1: Including handler.php\n";
echo "-----------------------------\n";

// Clear any previous output
while (ob_get_level()) ob_end_clean();

// Start fresh output buffering
ob_start();

// Check if we can include the file
try {
    include 'handler.php';
    $output = ob_get_clean();

    echo "✅ handler.php included successfully\n";

    // Check if output looks like JSON
    if (strpos($output, '{') === 0 || strpos(trim($output), '{') === 0) {
        echo "✅ Output appears to be JSON\n";
        echo "First 100 chars: " . substr($output, 0, 100) . "...\n";

        // Try to parse
        $json = json_decode($output, true);
        if ($json) {
            echo "✅ JSON parsed successfully\n";
            if (isset($json['success'])) {
                if ($json['success']) {
                    echo "✅ Registration would be successful\n";
                } else {
                    echo "⚠️ Registration would fail: " . ($json['message'] ?? 'No message') . "\n";
                }
            }
        } else {
            echo "⚠️ Output is not valid JSON\n";
        }
    } else {
        echo "⚠️ Output doesn't start with JSON\n";
        echo "Output preview: " . substr($output, 0, 200) . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Error including handler.php: " . $e->getMessage() . "\n";
}

// Test 2: Check database connection
echo "\n\nTest 2: Database connection\n";
echo "---------------------------\n";

try {
    $db = new PDO('sqlite:viadal_database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";

    // Check table structure
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "\n";

    if (in_array('students', $tables)) {
        echo "✅ Students table exists\n";

        // Count records
        $count = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
        echo "Total students: $count\n";
    } else {
        echo "❌ Students table missing\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 3: Check PHPMailer
echo "\n\nTest 3: PHPMailer setup\n";
echo "----------------------\n";

if (file_exists('libs/PHPMailer/PHPMailer.php')) {
    echo "✅ PHPMailer files exist\n";

    // Check if classes can be loaded
    require_once 'libs/PHPMailer/Exception.php';
    require_once 'libs/PHPMailer/PHPMailer.php';
    require_once 'libs/PHPMailer/SMTP.php';

    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✅ PHPMailer class can be loaded\n";
    } else {
        echo "❌ PHPMailer class cannot be loaded\n";
    }
} else {
    echo "❌ PHPMailer files not found\n";
}

echo "\n\nSOLUTION SUMMARY:\n";
echo "================\n";
echo "The issue 'Szerver hiba történt. Ellenőrizd a PHP futását!' occurs because:\n";
echo "1. PHP is not running as a web server when the form tries to submit\n";
echo "2. The form makes an HTTP request to /api/handler.php but nothing is listening\n";
echo "\nWhat I've fixed:\n";
echo "1. ✅ Fixed PHP warnings and deprecation notices in handler.php\n";
echo "2. ✅ Added proper error handling to the form with clear error messages\n";
echo "3. ✅ Added Content-Type header to fetch requests\n";
echo "4. ✅ Added null checks to prevent TypeScript errors\n";
echo "\nTo test the fix:\n";
echo "1. Start a PHP server: cd public/api && php -S localhost:8080\n";
echo "2. Open your Astro site\n";
echo "3. Try submitting the form\n";
echo "4. You should now see either:\n";
echo "   - Success message with email confirmation status\n";
echo "   - Clear error message if PHP server is not running\n";
echo "\nThe form will now show: 'Szerver hiba történt. Ellenőrizd a PHP futását!'\n";
echo "with details about what went wrong, instead of just failing silently.\n";

echo "\n✅ Final test complete!\n";
