<?php
// Test script for email sending functionality

// Include the handler to use its functions
require_once 'handler.php';

// Test data
$testEmail = "test@example.com";
$testSchool = "Test Iskola";

echo "Testing email sending functionality...\n";
echo "=====================================\n\n";

// Test the sendConfirmationEmail function
echo "1. Testing sendConfirmationEmail function:\n";
echo "   Email: $testEmail\n";
echo "   School: $testSchool\n\n";

$result = sendConfirmationEmail($testEmail, '', $testSchool);

if ($result) {
    echo "✅ SUCCESS: Email sent successfully!\n";
    echo "   Check the inbox of $testEmail for the confirmation email.\n";
} else {
    echo "❌ FAILED: Email sending failed.\n";
    echo "   Check the PHP error log for details.\n";
}

echo "\n\n2. Testing Amazon SES configuration:\n";
echo "   SMTP Host: " . SMTP_HOST . "\n";
echo "   SMTP Port: " . SMTP_PORT . "\n";
echo "   Sender Email: " . SENDER_EMAIL . "\n";
echo "   Sender Name: " . SENDER_NAME . "\n";

echo "\n\n3. Testing PHPMailer inclusion:\n";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "   ✅ PHPMailer class loaded successfully.\n";
} else {
    echo "   ❌ PHPMailer class not found.\n";
}

echo "\n\nNote: This is a basic test. For a full test, you should:\n";
echo "1. Use a real email address that you can check\n";
echo "2. Verify that the email arrives in the inbox (not spam)\n";
echo "3. Check that the email content is correct\n";
echo "4. Test with the actual registration form\n";
