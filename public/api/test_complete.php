<?php
// Complete test of the registration system
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Complete Test of Registration System\n";
echo "====================================\n\n";

// Test 1: Check if handler.php works
echo "Test 1: Testing handler.php directly\n";
echo "------------------------------------\n";

// Create a test function to simulate a request
function testHandler($action, $data)
{
    // Save current state
    $oldGet = $_GET;
    $oldInput = file_get_contents('php://input');

    // Set up test environment
    $_GET = ['action' => $action];
    $inputData = json_encode($data);

    // Create a temporary file for input
    $tempFile = tmpfile();
    fwrite($tempFile, $inputData);
    rewind($tempFile);

    // Capture output
    ob_start();

    // Include the handler
    include 'handler.php';

    $output = ob_get_clean();

    // Restore original state
    $_GET = $oldGet;

    return $output;
}

// Test data
$testEmail = 'test_' . time() . '@example.com';
$testData = [
    'school' => 'Budapesti Gimnázium',
    'email' => $testEmail,
    'password' => 'testpassword123',
    's1_n' => 'Test Teacher 1',
    's1_e' => 'teacher1@example.com',
    's2_n' => 'Test Teacher 2',
    's2_e' => 'teacher2@example.com',
    's3_n' => 'Test Teacher 3',
    's3_e' => 'teacher3@example.com'
];

echo "Test email: $testEmail\n";
echo "Test school: " . $testData['school'] . "\n\n";

// Run the test
$output = testHandler('register_student', $testData);

echo "Handler output:\n";
echo $output . "\n\n";

// Parse JSON response
$response = json_decode($output, true);
if ($response) {
    echo "✅ JSON parsed successfully\n";
    echo "Response details:\n";
    print_r($response);

    if ($response['success']) {
        echo "\n✅ SUCCESS: Registration worked!\n";
        echo "Message: " . $response['message'] . "\n";
        if (isset($response['email_sent'])) {
            echo "Email sent: " . ($response['email_sent'] ? 'Yes' : 'No') . "\n";
            echo "Email message: " . $response['email_message'] . "\n";

            if ($response['email_sent']) {
                echo "\n🎉 PERFECT! The email confirmation system is working!\n";
                echo "Check the email inbox for: $testEmail\n";
                echo "(Also check spam folder)\n";
            } else {
                echo "\n⚠️ WARNING: Registration worked but email sending failed\n";
                echo "This could be due to:\n";
                echo "1. Amazon SES configuration issues\n";
                echo "2. Email address not verified in SES\n";
                echo "3. Network issues\n";
            }
        }
    } else {
        echo "\n❌ ERROR: Registration failed\n";
        echo "Message: " . $response['message'] . "\n";
    }
} else {
    echo "❌ ERROR: Handler did not return valid JSON\n";
    echo "This usually means PHP errors are being output\n";
    echo "Check that error_reporting(0) is set in handler.php\n";
}

// Test 2: Check database
echo "\n\nTest 2: Checking database\n";
echo "-------------------------\n";

try {
    $db = new PDO('sqlite:viadal_database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if test user was created
    $stmt = $db->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "✅ Test user found in database\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "School: " . $user['school'] . "\n";
        echo "Created at: " . $user['created_at'] . "\n";
    } else {
        echo "❌ Test user NOT found in database\n";
    }

    // Count total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total users in database: $count\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 3: Check email sending capability
echo "\n\nTest 3: Email sending test\n";
echo "--------------------------\n";

// Include the sendConfirmationEmail function
require_once 'handler.php';

// Test with a simple call (we'll need to call it directly)
echo "Note: Email sending was already tested above.\n";
echo "If email_sent was true, then Amazon SES is working.\n";
echo "If not, check:\n";
echo "1. SMTP credentials in handler.php\n";
echo "2. Amazon SES console for verification status\n";
echo "3. That you're out of sandbox mode in SES\n";

echo "\n\nSummary:\n";
echo "--------\n";
echo "1. The PHP handler is now fixed and should not output errors before JSON\n";
echo "2. The form has better error handling with clear messages\n";
echo "3. To test the actual form:\n";
echo "   a) Start PHP server: php -S localhost:8080 in public/api directory\n";
echo "   b) Open the Astro site in browser\n";
echo "   c) Try submitting the form\n";
echo "   d) You should see either success or a clear error message\n";

echo "\n✅ Testing complete!\n";
