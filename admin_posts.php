<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt."]);
    exit;
}

try {
    $db = getDatabase();

    // Prüfen ob aktueller Benutzer Admin ist
    $check = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $check->execute([$_SESSION["user_id"]]);
    $me = $check->fetch();

    if (!$me || ($me["role"] !== 'admin' && $me["role"] !== 'board')) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Keine Berechtigung."]);
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $postId = intval($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                echo json_encode(["success" => false, "message" => "Ungültige Post-ID."]);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);

            echo json_encode(["success" => true, "message" => "Beitrag gelöscht."]);
            exit;
        }

        echo json_encode(["success" => false, "message" => "Unbekannte Aktion."]);
        exit;
    }

    // GET -> Liste aller Beiträge
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

    echo json_encode(["success" => true, "posts" => $posts]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Fehler beim Verarbeiten der Anfrage."]);
}
