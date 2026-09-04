<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Ungültige Anfrage."
    ]);
    exit;
}

$firstName = trim($_POST["firstName"] ?? "");
$lastName  = trim($_POST["lastName"] ?? "");
$username  = trim($_POST["username"] ?? "");
$password  = $_POST["password"] ?? "";
$actor     = trim($_POST["actor"] ?? "");

if (
    $firstName === "" ||
    $lastName === "" ||
    $username === "" ||
    $password === "" ||
    $actor === ""
) {
    echo json_encode([
        "success" => false,
        "message" => "Bitte alle Felder ausfüllen."
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "Das Passwort muss mindestens 6 Zeichen haben."
    ]);
    exit;
}

try {

    $db = getDatabase();

    // Prüfen, ob Benutzername bereits existiert
    $check = $db->prepare(
        "SELECT id FROM users WHERE username = ? LIMIT 1"
    );

    $check->execute([$username]);

    if ($check->fetch()) {
        echo json_encode([
            "success" => false,
            "message" => "Dieser Benutzername ist bereits vergeben."
        ]);
        exit;
    }

    // Passwort sicher verschlüsseln
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Benutzer anlegen
$stmt = $db->prepare(
    "INSERT INTO users
    (first_name, last_name, username, password, actor, role)
    VALUES (?, ?, ?, ?, ?, 'member')"
);

$stmt->execute([
    $firstName,
    $lastName,
    $username,
    $passwordHash,
    $actor
]);



    $userId = $db->lastInsertId();

    // Standardeinstellungen anlegen
    $settings = $db->prepare(
        "INSERT INTO settings
        (user_id, notify_news, notify_events)
        VALUES (?, TRUE, TRUE)"
    );

    $settings->execute([$userId]);

    // Benutzer direkt einloggen
    $_SESSION["user_id"] = $userId;

    echo json_encode([
        "success" => true,
        "message" => "Account erfolgreich erstellt.",
        "user" => [
            "first" => $firstName,
            "last" => $lastName,
            "username" => $username,
            "actor" => $actor,
            "role" => 'member'
        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Account konnte nicht erstellt werden."
    ]);
}
