<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Nicht eingeloggt."
    ]);

    exit;
}

try {

    $db = getDatabase();

    // Prüfen ob aktueller Benutzer Admin ist
    $check = $db->prepare(
        "SELECT role FROM users WHERE id = ? LIMIT 1"
    );

    $check->execute([$_SESSION["user_id"]]);

    $me = $check->fetch();

    if (!$me || ($me["role"] !== 'admin' && $me["role"] !== 'board')) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Keine Berechtigung."
        ]);
        exit;
    }

    $stmt = $db->prepare(
        "SELECT id, first_name, last_name, username, actor, role
         FROM users
         ORDER BY last_name, first_name"
    );

    $stmt->execute();

    $users = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "users" => $users
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Benutzerliste konnte nicht geladen werden."
    ]);
}
