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
        "message" => "Bitte zuerst einloggen."
    ]);

    exit;
}


$userId = $_SESSION["user_id"];


try {

    $db = getDatabase();


    // ==========================================
    // NEUEN BEITRAG ERSTELLEN
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $text = trim(
            $_POST["text"] ?? ""
        );


        if ($text === "") {

            echo json_encode([
                "success" => false,
                "message" => "Bitte einen Beitrag schreiben."
            ]);

            exit;
        }


        if (mb_strlen($text) > 5000) {

            echo json_encode([
                "success" => false,
                "message" => "Der Beitrag ist zu lang."
            ]);

            exit;
        }


        $stmt = $db->prepare(
            "INSERT INTO posts
             (user_id, text)
             VALUES (?, ?)"
        );


        $stmt->execute([
            $userId,
            $text
        ]);


        echo json_encode([
            "success" => true,
            "message" => "Beitrag veröffentlicht."
        ]);

        exit;
    }


    // ==========================================
    // BEITRÄGE LADEN
    // ==========================================

    $stmt = $db->query(
        "SELECT
            posts.id,
            posts.text,
            posts.created_at,
            users.first_name,
            users.last_name,
            users.actor
         FROM posts
         INNER JOIN users
            ON posts.user_id = users.id
         ORDER BY posts.created_at DESC"
    );


    $posts = $stmt->fetchAll();


    echo json_encode([
        "success" => true,
        "posts" => $posts
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Pinnwand konnte nicht geladen werden."
    ]);
}
