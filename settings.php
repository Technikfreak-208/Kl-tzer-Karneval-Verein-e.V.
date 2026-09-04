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
    // EINSTELLUNGEN SPEICHERN
    // ==========================================

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $news =
            ($_POST["news"] ?? "false") === "true"
                ? 1
                : 0;

        $events =
            ($_POST["events"] ?? "false") === "true"
                ? 1
                : 0;


        $stmt = $db->prepare(
            "INSERT INTO settings
                (user_id, notify_news, notify_events)
             VALUES
                (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                notify_news = VALUES(notify_news),
                notify_events = VALUES(notify_events)"
        );


        $stmt->execute([
            $userId,
            $news,
            $events
        ]);


        echo json_encode([
            "success" => true,
            "message" => "Einstellungen gespeichert."
        ]);

        exit;
    }


    // ==========================================
    // EINSTELLUNGEN LADEN
    // ==========================================

    $stmt = $db->prepare(
        "SELECT
            notify_news,
            notify_events
         FROM settings
         WHERE user_id = ?
         LIMIT 1"
    );


    $stmt->execute([
        $userId
    ]);


    $settings = $stmt->fetch();


    // Falls noch keine Einstellungen existieren
    if (!$settings) {

        $create = $db->prepare(
            "INSERT INTO settings
             (user_id, notify_news, notify_events)
             VALUES (?, TRUE, TRUE)"
        );

        $create->execute([
            $userId
        ]);


        $settings = [
            "notify_news" => 1,
            "notify_events" => 1
        ];
    }


    echo json_encode([
        "success" => true,
        "settings" => [
            "news" => (bool)$settings["notify_news"],
            "events" => (bool)$settings["notify_events"]
        ]
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Einstellungen konnten nicht geladen werden."
    ]);
}
