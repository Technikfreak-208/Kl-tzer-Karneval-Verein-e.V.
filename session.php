<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => true,
        "loggedIn" => false
    ]);

    exit;
}

try {

    $db = getDatabase();

    $stmt = $db->prepare(
        "SELECT id, first_name, last_name, username, actor, role
         FROM users
         WHERE id = ?
         LIMIT 1"
    );


    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $user = $stmt->fetch();

    if (!$user) {

        session_unset();
        session_destroy();

        echo json_encode([
            "success" => true,
            "loggedIn" => false
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "loggedIn" => true,
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
        "message" => "Sitzung konnte nicht geprüft werden."
    ]);
}
