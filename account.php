<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";


// ==========================================
// Prüfen, ob eingeloggt
// ==========================================

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Nicht eingeloggt."
    ]);

    exit;
}


$userId = $_SESSION["user_id"];


try {

    $db = getDatabase();


    // ==========================================
    // ACCOUNT ÄNDERN
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $firstName = trim(
            $_POST["firstName"] ?? ""
        );

        $lastName = trim(
            $_POST["lastName"] ?? ""
        );

        $actor = trim(
            $_POST["actor"] ?? ""
        );


        if (
            $firstName === "" ||
            $lastName === "" ||
            $actor === ""
        ) {

            echo json_encode([
                "success" => false,
                "message" => "Bitte alle Felder ausfüllen."
            ]);

            exit;
        }


        $stmt = $db->prepare(
            "UPDATE users
             SET first_name = ?,
                 last_name = ?,
                 actor = ?
             WHERE id = ?"
        );


        $stmt->execute([
            $firstName,
            $lastName,
            $actor,
            $userId
        ]);


        echo json_encode([
            "success" => true,
            "message" => "Account wurde gespeichert."
        ]);

        exit;
    }


    // ==========================================
    // ACCOUNT LADEN
    // ==========================================

    $stmt = $db->prepare(
        "SELECT
            id,
            first_name,
            last_name,
            username,
            actor,
            role
         FROM users
         WHERE id = ?
         LIMIT 1"
    );


    $stmt->execute([
        $userId
    ]);


    $user = $stmt->fetch();


    if (!$user) {

        echo json_encode([
            "success" => false,
            "message" => "Benutzer wurde nicht gefunden."
        ]);

        exit;
    }


    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user["id"],
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
        "message" => "Account konnte nicht geladen werden."
    ]);
}
