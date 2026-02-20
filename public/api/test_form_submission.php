<?php
// Test the actual form submission
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing form submission to handler.php\n";
echo "======================================\n\n";

// Create test data similar to what the form would send
$testData = [
    'school' => 'Budapesti Gimnázium',
    'email' => 'test' . time() . '@example.com',
    'password' => 'testpassword123',
    's1_n' => 'Test Teacher 1',
    's1_e' => 'teacher1@example.com',
    's2_n' => 'Test Teacher 2',
    's2_e' => 'teacher2@example.com',
    's3_n' => 'Test Teacher 3',
    's3_e' => 'teacher3@example.com'
];

echo "Test data:\n";
print_r($testData);
echo "\n";

// Simulate the fetch request
$url = 'http://localhost/api/handler.php?action=register_student';
$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($testData),
    ],
];

$context = stream_context_create($options);
echo "Sending request to: $url\n";
echo "Headers: Content-Type: application/json\n";
echo "Body: " . json_encode($testData) . "\n\n";

try {
    $result = file_get_contents($url, false, $context);
    echo "Response received:\n";
    echo $result . "\n";

    // Parse JSON response
    $response = json_decode($result, true);
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ Registration successful!\n";
            if (isset($response['email_sent'])) {
                echo "Email sent: " . ($response['email_sent'] ? 'Yes' : 'No') . "\n";
                if (isset($response['email_message'])) {
                    echo "Email message: " . $response['email_message'] . "\n";
                }
            }
        } else {
            echo "❌ Registration failed: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . $result . "\n";
    }
} catch (Exception $e) {
    echo "❌ Request failed: " . $e->getMessage() . "\n";

    // Check if we can access the handler directly
    echo "\nTrying to test handler.php directly...\n";

    // Set up the environment for handler.php
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['action'] = 'register_student';

    // Capture the output
    ob_start();
    try {
        // Include the handler
        require_once 'handler.php';
        $output = ob_get_clean();
        echo "Handler output:\n";
        echo $output . "\n";
    } catch (Exception $e2) {
        ob_end_clean();
        echo "Handler exception: " . $e2->getMessage() . "\n";
    }
}

echo "\n\nChecking common issues:\n";
echo "1. Make sure PHP is running on localhost\n";
echo "2. Check that the /api/handler.php file is accessible\n";
echo "3. Verify database permissions\n";
echo "4. Check PHP error log for details\n";
