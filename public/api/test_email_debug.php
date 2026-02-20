<?php
// Test script for email sending with debug output

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'libs/PHPMailer/Exception.php';
require_once 'libs/PHPMailer/PHPMailer.php';
require_once 'libs/PHPMailer/SMTP.php';

// Amazon SES configuration
define('SMTP_HOST', 'email-smtp.eu-north-1.amazonaws.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'AKIAQGVCWMZBAAR7HKQO');
define('SMTP_PASSWORD', 'BFdJYJZQR2RTT1feOJNKz9srtIuVRcLOYZgn5x/lzAS6');
define('SENDER_EMAIL', 'info@juniorakademia.hu');
define('SENDER_NAME', 'Junior Akadémia');

echo "Testing Amazon SES SMTP connection...\n";
echo "=====================================\n\n";

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = 3; // 3 = client + server messages
    $mail->Debugoutput = function ($str, $level) {
        echo "[$level] $str\n";
    };

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    echo "Connecting to SMTP server: " . SMTP_HOST . ":" . SMTP_PORT . "\n";
    echo "Username: " . SMTP_USERNAME . "\n";
    echo "Using TLS encryption\n\n";

    // Test connection without sending email
    if (!$mail->smtpConnect()) {
        echo "❌ SMTP connection failed!\n";
    } else {
        echo "✅ SMTP connection successful!\n";
        $mail->smtpClose();
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "PHPMailer Error: " . $mail->ErrorInfo . "\n";
}

echo "\n\nCommon issues to check:\n";
echo "1. Verify SMTP credentials in AWS Console\n";
echo "2. Check if sender email is verified in Amazon SES\n";
echo "3. Ensure Amazon SES is in production mode (not sandbox)\n";
echo "4. Check AWS region matches SMTP server region\n";
echo "5. Verify IAM user has SES sending permissions\n";
