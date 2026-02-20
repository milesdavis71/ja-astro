<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$db = new PDO('sqlite:viadal_database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

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

        echo json_encode(["success" => true, "message" => "Sikeres regisztráció!"]);
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
