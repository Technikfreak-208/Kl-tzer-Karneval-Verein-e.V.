<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Ungültige Anfrage."]);
    exit;
}

$first = trim($_POST['firstName'] ?? '');
$last = trim($_POST['lastName'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$actor = trim($_POST['actor'] ?? '');

if ($first === '' || $last === '' || $username === '' || $password === '') {
    echo json_encode(["success" => false, "message" => "Bitte alle Felder ausfüllen."]);
    exit;
}

try {
    $db = getDatabase();

    // Erlaube Erstellung, wenn aktuell kein Admin existiert OR aufrufender Benutzer ist Admin
    $stmt = $db->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
    $row = $stmt->fetch();
    $adminCount = intval($row['c'] ?? 0);

    $allowed = false;

    if ($adminCount === 0) {
        $allowed = true; // allow initial admin creation
    } else {
        // require caller to be logged in and admin
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Keine Berechtigung."]);
            exit;
        }

        $check = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $check->execute([$_SESSION['user_id']]);
        $me = $check->fetch();
        if ($me && $me['role'] === 'admin') {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Keine Berechtigung zur Erstellung eines Admins."]);
        exit;
    }

    // Prüfen, ob Username bereits existiert
    $check = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $check->execute([$username]);
    if ($check->fetch()) {
        echo json_encode(["success" => false, "message" => "Benutzername bereits vergeben."]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        "INSERT INTO users (first_name, last_name, username, password, actor, role) VALUES (?, ?, ?, ?, ?, 'admin')"
    );

    $stmt->execute([$first, $last, $username, $passwordHash, $actor]);

    echo json_encode(["success" => true, "message" => "Admin-Benutzer erstellt."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Fehler beim Erstellen des Admins."]);
}
