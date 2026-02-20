<?php
// Simple test without complex stream wrapper overrides
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Simple test of handler.php\n";
echo "==========================\n\n";

// Temporarily disable output buffering and headers
ob_start();

// Include the handler but capture output
include 'handler.php';

$output = ob_get_clean();

echo "Raw output from handler.php:\n";
echo "----------------------------\n";
echo $output . "\n\n";

// Try to parse as JSON
$response = json_decode($output, true);
if ($response) {
    echo "Parsed JSON response:\n";
    echo "---------------------\n";
    print_r($response);

    if (isset($response['success']) && $response['success']) {
        echo "\n✅ SUCCESS: Handler is working correctly!\n";
        if (isset($response['email_sent'])) {
            echo "   Email sent: " . ($response['email_sent'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "\n❌ ERROR: Handler returned success=false\n";
        echo "   Message: " . ($response['message'] ?? 'No message') . "\n";
    }
} else {
    echo "❌ ERROR: Handler did not return valid JSON\n";
    echo "This could be due to:\n";
    echo "1. PHP errors/warnings being output before JSON\n";
    echo "2. Missing Content-Type header\n";
    echo "3. Other output before the JSON response\n";

    // Check for common errors
    if (strpos($output, 'Warning:') !== false || strpos($output, 'Error:') !== false) {
        echo "\nPHP errors detected in output. Try running with error_reporting(0) in handler.php\n";
    }
}

echo "\n\nTo fix the 'Szerver hiba történt' error:\n";
echo "1. Make sure PHP is running as a web server\n";
echo "2. Or modify the form to handle connection errors gracefully\n";
echo "3. Check that the /api/handler.php URL is accessible from the browser\n";
