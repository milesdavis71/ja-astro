<?php

declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/libs/PHPMailer/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/SMTP.php';
require_once __DIR__ . '/smtp_config_loader.php';

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_request_data(): array
{
    $rawBody = file_get_contents('php://input');
    if (is_string($rawBody) && trim($rawBody) !== '') {
        $decoded = json_decode($rawBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST;
}

function clean_text($value): string
{
    return trim((string)($value ?? ''));
}

function clean_email($value): string
{
    return strtolower(trim((string)($value ?? '')));
}

function get_smtp_config(): array
{
    return load_smtp_config(__DIR__ . '/smtp_config.php');
}

function create_database_connection(): PDO
{
    $databasePath = __DIR__ . '/viadal_database.sqlite';
    $db = new PDO('sqlite:' . $databasePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON');

    $db->exec(
        'CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            school TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            s1_name TEXT NOT NULL DEFAULT "",
            s1_email TEXT NOT NULL DEFAULT "",
            s2_name TEXT NOT NULL DEFAULT "",
            s2_email TEXT NOT NULL DEFAULT "",
            s3_name TEXT NOT NULL DEFAULT "",
            s3_email TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    return $db;
}

function validate_registration_payload(array $data): array
{
    $school = clean_text($data['school'] ?? '');
    $email = clean_email($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    $students = [
        ['name' => clean_text($data['s1_n'] ?? ''), 'email' => clean_email($data['s1_e'] ?? '')],
        ['name' => clean_text($data['s2_n'] ?? ''), 'email' => clean_email($data['s2_e'] ?? '')],
        ['name' => clean_text($data['s3_n'] ?? ''), 'email' => clean_email($data['s3_e'] ?? '')],
    ];

    if ($school === '' || $school === 'none') {
        json_response(['success' => false, 'message' => 'Érvényes iskola kiválasztása kötelező.'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Érvényes email cím megadása kötelező.'], 422);
    }

    if (strlen($password) < 8) {
        json_response(['success' => false, 'message' => 'A jelszónak legalább 8 karakter hosszúnak kell lennie.'], 422);
    }

    foreach ($students as $index => $student) {
        if ($student['name'] === '') {
            json_response(['success' => false, 'message' => ($index + 1) . '. diáktárs neve kötelező.'], 422);
        }

        if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => ($index + 1) . '. diáktárs email címe érvénytelen.'], 422);
        }
    }

    return [
        'school' => $school,
        'email' => $email,
        'password' => $password,
        's1_n' => $students[0]['name'],
        's1_e' => $students[0]['email'],
        's2_n' => $students[1]['name'],
        's2_e' => $students[1]['email'],
        's3_n' => $students[2]['name'],
        's3_e' => $students[2]['email'],
    ];
}

function validate_login_payload(array $data): array
{
    $email = clean_email($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        json_response(['success' => false, 'message' => 'Az email cím és a jelszó megadása kötelező.'], 422);
    }

    return ['email' => $email, 'password' => $password];
}

function build_student_response(array $student): array
{
    return [
        'id' => (int)$student['id'],
        'school' => $student['school'],
        'email' => $student['email'],
        's1_name' => $student['s1_name'],
        's1_email' => $student['s1_email'],
        's2_name' => $student['s2_name'],
        's2_email' => $student['s2_email'],
        's3_name' => $student['s3_name'],
        's3_email' => $student['s3_email'],
        'created_at' => $student['created_at'],
    ];
}

function send_confirmation_email(array $smtpConfig, string $toEmail, string $school): bool
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpConfig['host'];
        $mail->Port = $smtpConfig['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpConfig['username'];
        $mail->Password = $smtpConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtpConfig['sender_email'], $smtpConfig['sender_name']);
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Sikeres regisztráció - Junior Akadémia Viadala';

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="hu">
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; }
            .wrapper { max-width: 640px; margin: 0 auto; padding: 24px; }
            .header { background: #2563eb; color: #ffffff; padding: 20px 24px; border-radius: 12px 12px 0 0; }
            .content { background: #f8fafc; padding: 24px; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 12px 12px; }
          </style>
        </head>
        <body>
          <div class="wrapper">
            <div class="header">
              <h1>Junior Akadémia Viadala</h1>
            </div>
            <div class="content">
              <p>Köszönjük a regisztrációt.</p>
              <p><strong>Iskola:</strong> ' . htmlspecialchars($school, ENT_QUOTES, 'UTF-8') . '</p>
              <p><strong>Email cím:</strong> ' . htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8') . '</p>
              <p>A szerkesztéshez később ugyanezzel az email címmel és jelszóval tud bejelentkezni.</p>
            </div>
          </div>
        </body>
        </html>';

        $mail->AltBody =
            "Köszönjük a regisztrációt a Junior Akadémia Viadalára.\n\n" .
            "Iskola: {$school}\n" .
            "Email cím: {$toEmail}\n\n" .
            "A szerkesztéshez később ugyanezzel az email címmel és jelszóval tud bejelentkezni.";

        $mail->send();
        return true;
    } catch (Exception $exception) {
        error_log('Email sending failed: ' . $exception->getMessage());
        return false;
    }
}

function handle_login(PDO $db, array $data): void
{
    $payload = validate_login_payload($data);

    $statement = $db->prepare(
        'SELECT id, school, email, password, s1_name, s1_email, s2_name, s2_email, s3_name, s3_email, created_at
         FROM students
         WHERE email = :email
         LIMIT 1'
    );
    $statement->execute([':email' => $payload['email']]);
    $student = $statement->fetch();

    if (!$student || !password_verify($payload['password'], $student['password'])) {
        json_response(['success' => false, 'message' => 'Hibás email cím vagy jelszó.'], 401);
    }

    if (password_needs_rehash($student['password'], PASSWORD_DEFAULT)) {
        $update = $db->prepare('UPDATE students SET password = :password WHERE id = :id');
        $update->execute([
            ':password' => password_hash($payload['password'], PASSWORD_DEFAULT),
            ':id' => $student['id'],
        ]);
    }

    json_response(['success' => true, 'user' => build_student_response($student)]);
}

function handle_registration(PDO $db, array $data, array $smtpConfig): void
{
    $payload = validate_registration_payload($data);

    $existsStatement = $db->prepare('SELECT COUNT(*) FROM students WHERE email = :email');
    $existsStatement->execute([':email' => $payload['email']]);
    if ((int)$existsStatement->fetchColumn() > 0) {
        json_response(['success' => false, 'message' => 'Ez az email cím már regisztrálva van.'], 409);
    }

    $insert = $db->prepare(
        'INSERT INTO students (
            school, email, password, s1_name, s1_email, s2_name, s2_email, s3_name, s3_email
        ) VALUES (
            :school, :email, :password, :s1_name, :s1_email, :s2_name, :s2_email, :s3_name, :s3_email
        )'
    );

    $db->beginTransaction();
    try {
        $insert->execute([
            ':school' => $payload['school'],
            ':email' => $payload['email'],
            ':password' => password_hash($payload['password'], PASSWORD_DEFAULT),
            ':s1_name' => $payload['s1_n'],
            ':s1_email' => $payload['s1_e'],
            ':s2_name' => $payload['s2_n'],
            ':s2_email' => $payload['s2_e'],
            ':s3_name' => $payload['s3_n'],
            ':s3_email' => $payload['s3_e'],
        ]);
        $db->commit();
    } catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $throwable;
    }

    $emailSent = send_confirmation_email($smtpConfig, $payload['email'], $payload['school']);

    json_response([
        'success' => true,
        'message' => 'Sikeres regisztráció.',
        'email_sent' => $emailSent,
        'email_message' => $emailSent
            ? 'Visszaigazoló email elküldve.'
            : 'A regisztráció sikeres, de a visszaigazoló emailt nem sikerült elküldeni.',
    ]);
}

function handle_update(PDO $db, array $data): void
{
    $payload = validate_registration_payload($data);

    $statement = $db->prepare('SELECT id, password FROM students WHERE email = :email LIMIT 1');
    $statement->execute([':email' => $payload['email']]);
    $student = $statement->fetch();

    if (!$student || !password_verify($payload['password'], $student['password'])) {
        json_response(['success' => false, 'message' => 'Hibás email cím vagy jelszó.'], 401);
    }

    $update = $db->prepare(
        'UPDATE students
         SET school = :school,
             s1_name = :s1_name,
             s1_email = :s1_email,
             s2_name = :s2_name,
             s2_email = :s2_email,
             s3_name = :s3_name,
             s3_email = :s3_email
         WHERE id = :id'
    );

    $update->execute([
        ':school' => $payload['school'],
        ':s1_name' => $payload['s1_n'],
        ':s1_email' => $payload['s1_e'],
        ':s2_name' => $payload['s2_n'],
        ':s2_email' => $payload['s2_e'],
        ':s3_name' => $payload['s3_n'],
        ':s3_email' => $payload['s3_e'],
        ':id' => $student['id'],
    ]);

    json_response(['success' => true, 'message' => 'Az adatok sikeresen frissítve lettek.']);
}

try {
    $db = create_database_connection();
    $smtpConfig = get_smtp_config();
    $action = clean_text($_GET['action'] ?? '');
    $data = get_request_data();

    if ($action === '') {
        json_response(['success' => false, 'message' => 'Hiányzó művelet.'], 400);
    }

    if ($action === 'login_student') {
        handle_login($db, $data);
    }

    if ($action === 'register_student') {
        handle_registration($db, $data, $smtpConfig);
    }

    if ($action === 'update_student') {
        handle_update($db, $data);
    }

    json_response(['success' => false, 'message' => 'Ismeretlen művelet.'], 404);
} catch (Throwable $throwable) {
    error_log('Handler error: ' . $throwable->getMessage());
    json_response(['success' => false, 'message' => 'Szerveroldali hiba történt.'], 500);
}
