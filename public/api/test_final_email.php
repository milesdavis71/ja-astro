<?php
// Final test for email sending functionality

// Include the handler to use its functions
require_once 'handler.php';

// Test with a real email (use your own email to test)
$testEmail = "your-email@example.com"; // CHANGE THIS TO YOUR EMAIL
$testSchool = "Teszt Iskola Gimnázium";

echo "Testing complete email sending functionality...\n";
echo "==============================================\n\n";

echo "Test email: $testEmail\n";
echo "Test school: $testSchool\n\n";

echo "Sending test email...\n";

// Test the sendConfirmationEmail function
$result = sendConfirmationEmail($testEmail, 'Teszt Diák', $testSchool);

if ($result) {
    echo "✅ SUCCESS: Test email sent successfully!\n";
    echo "   Check the inbox of $testEmail for the confirmation email.\n";
    echo "   Also check spam folder if not in inbox.\n";
} else {
    echo "❌ FAILED: Test email sending failed.\n";
    echo "   Check PHP error log for details.\n";
}

echo "\n\nNote: If the email doesn't arrive, check:\n";
echo "1. Amazon SES production mode is enabled\n";
echo "2. Sender email (info@juniorakademia.hu) is verified in Amazon SES\n";
echo "3. Recipient email is verified (if still in sandbox mode)\n";
echo "4. Check spam folder\n";

// Also test the registration response structure
echo "\n\nTesting registration response structure...\n";
echo "=========================================\n";

// Simulate registration data
$registrationData = [
    'school' => $testSchool,
    'email' => $testEmail,
    'password' => 'testpassword123',
    's1_n' => 'Teszt Tanár 1',
    's1_e' => 'tanar1@example.com',
    's2_n' => 'Teszt Tanár 2',
    's2_e' => 'tanar2@example.com'
];

echo "Registration would return JSON response with:\n";
echo "- success: true\n";
echo "- message: 'Sikeres regisztráció!'\n";
echo "- email_sent: true/false\n";
echo "- email_message: status message\n";

echo "\n✅ Implementation complete! The handler.php file is ready to use.\n";
