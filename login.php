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

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Bitte Benutzername und Passwort eingeben."
    ]);
    exit;
}

try {

    $db = getDatabase();

   $stmt = $db->prepare(
    "SELECT id, first_name, last_name, username, password, actor, role
     FROM users
     WHERE username = ?
     LIMIT 1"
);


    $stmt->execute([$username]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user["password"])) {

        echo json_encode([
            "success" => false,
            "message" => "Benutzername oder Passwort ist falsch."
        ]);

        exit;
    }

    $_SESSION["user_id"] = $user["id"];

    echo json_encode([
        "success" => true,
        "message" => "Login erfolgreich.",
        "user" => [
            "first" => $user["first_name"],
            "last" => $user["last_name"],
            "username" => $user["username"],
            "actor" => $user["actor"],
            "role" => $user["role"]

        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Login konnte nicht durchgeführt werden."
    ]);
}
