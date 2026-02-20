<?php

// Turn off error reporting for production to avoid output before JSON
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any accidental output
ob_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// PHPMailer betöltése
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'libs/PHPMailer/Exception.php';
require_once 'libs/PHPMailer/PHPMailer.php';
require_once 'libs/PHPMailer/SMTP.php';

try {
    $db = new PDO('sqlite:viadal_database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Adatbázis kapcsolati hiba: " . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// Amazon SES konfiguráció
define('SMTP_HOST', 'email-smtp.eu-north-1.amazonaws.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'AKIAQGVCWMZBAAR7HKQO');
define('SMTP_PASSWORD', 'BFdJYJZQR2RTT1feOJNKz9srtIuVRcLOYZgn5x/lzAS6');
define('SENDER_EMAIL', 'info@juniorakademia.hu');
define('SENDER_NAME', 'Junior Akadémia');

/**
 * Email küldése a regisztrált diáknak
 */
function sendConfirmationEmail($toEmail, $school, $studentName = '')
{
    try {
        $mail = new PHPMailer(true);

        // SMTP beállítások
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // Küldő beállítások
        $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
        $mail->addAddress($toEmail);

        // Email tartalom
        $mail->isHTML(true);
        $mail->Subject = 'Sikeres regisztráció - Junior Akadémia Viadal';

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background-color: #f9f9f9; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Junior Akadémia Viadal</h1>
                </div>
                <div class='content'>
                    <h2>Kedves " . htmlspecialchars($studentName ?: 'Résztvevő') . "!</h2>
                    <p>Köszönjük, hogy regisztráltál a Junior Akadémia Viadalra!</p>
                    <p><strong>Regisztrációs adataid:</strong></p>
                    <ul>
                        <li><strong>Iskola:</strong> " . htmlspecialchars($school) . "</li>
                        <li><strong>Email cím:</strong> " . htmlspecialchars($toEmail) . "</li>
                    </ul>
                    <p>A viadal részleteiről és a további információkról később értesíteni fogunk.</p>
                    <p>Ha bármilyen kérdésed van, keress minket a " . SENDER_EMAIL . " email címen.</p>
                    <p>Üdvözlettel,<br>A Junior Akadémia csapata</p>
                </div>
                <div class='footer'>
                    <p>Ez egy automatikus üzenet, kérjük ne válaszolj rá.</p>
                    <p>© " . date('Y') . " Junior Akadémia. Minden jog fenntartva.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $htmlBody;

        // Alternatív szöveges tartalom
        $textBody = "Kedves " . ($studentName ?: 'Résztvevő') . "!\n\n" .
            "Köszönjük, hogy regisztráltál a Junior Akadémia Viadalra!\n\n" .
            "Regisztrációs adataid:\n" .
            "- Iskola: " . $school . "\n" .
            "- Email cím: " . $toEmail . "\n\n" .
            "A viadal részleteiről és a további információkról később értesíteni fogunk.\n\n" .
            "Ha bármilyen kérdésed van, keress minket a " . SENDER_EMAIL . " email címen.\n\n" .
            "Üdvözlettel,\n" .
            "A Junior Akadémia csapata\n\n" .
            "© " . date('Y') . " Junior Akadémia. Minden jog fenntartva.";

        $mail->AltBody = $textBody;

        // Email küldése
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Naplózzuk a hibát, de ne akadályozzuk meg a regisztrációt
        error_log("Email küldési hiba: " . $mail->ErrorInfo);
        return false;
    }
}

try {
    // DIÁK BEJELENTKEZÉS ÉS ADATOK LEKÉRÉSE
    if ($action === 'login_student') {
        $stmt = $db->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data['password'], $user['password'])) {
            unset($user['password']); // Biztonság: jelszót nem küldünk vissza
            echo json_encode(["success" => true, "user" => $user]);
        } else {
            echo json_encode(["success" => false, "message" => "Hibás email cím vagy jelszó!"]);
        }
    }

    // DIÁK REGISZTRÁCIÓ
    elseif ($action === 'register_student') {
        // Ellenőrizzük, hogy az email már létezik-e
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
        $stmt->execute([$data['email']]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            echo json_encode(["success" => false, "message" => "Ez az email cím már regisztrálva van!"]);
            return;
        }

        // Jelszó hash-elése
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Új diák beszúrása
        $stmt = $db->prepare("INSERT INTO students (school, email, password, s1_name, s1_email, s2_name, s2_email, s3_name, s3_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['school'],
            $data['email'],
            $hashedPassword,
            $data['s1_n'] ?? '',
            $data['s1_e'] ?? '',
            $data['s2_n'] ?? '',
            $data['s2_e'] ?? '',
            $data['s3_n'] ?? '',
            $data['s3_e'] ?? ''
        ]);

        // Sikeres regisztráció után email küldése
        $emailSent = sendConfirmationEmail($data['email'], $data['school'], '');

        $response = [
            "success" => true,
            "message" => "Sikeres regisztráció!"
        ];

        if (!$emailSent) {
            $response["email_sent"] = false;
            $response["email_message"] = "A regisztráció sikeres, de a visszaigazoló emailt nem sikerült elküldeni.";
        } else {
            $response["email_sent"] = true;
            $response["email_message"] = "Visszaigazoló email elküldve.";
        }

        echo json_encode($response);
    }

    // DIÁK ADATOK FRISSÍTÉSE
    elseif ($action === 'update_student') {
        // Újra ellenőrizzük a jelszót a módosítás előtt a biztonság kedvéért
        $stmt = $db->prepare("SELECT password FROM students WHERE email = ?");
        $stmt->execute([$data['email']]);
        $passHash = $stmt->fetchColumn();

        if ($passHash && password_verify($data['password'], $passHash)) {
            $stmt = $db->prepare("UPDATE students SET s1_name=?, s1_email=?, s2_name=?, s2_email=?, s3_name=?, s3_email=? WHERE email=?");
            $stmt->execute([
                $data['s1_n'],
                $data['s1_e'],
                $data['s2_n'],
                $data['s2_e'],
                $data['s3_n'],
                $data['s3_e'],
                $data['email']
            ]);
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Biztonsági hiba: Érvénytelen munkamenet."]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
