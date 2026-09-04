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
    $check = $db->prepare("SELECT role, actor FROM users WHERE id = ? LIMIT 1");
    $check->execute([$_SESSION["user_id"]]);
    $me = $check->fetch();

    if (!$me || !in_array($me['role'], ['trainer','board','admin'], true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Keine Berechtigung."]);
        exit;
    }

    $actor = $me['actor'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $postId = intval($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                echo json_encode(["success" => false, "message" => "Ungültige Post-ID."]);
                exit;
            }

            // Delete only if post belongs to same actor (trainer can only moderate their group's posts), or board/admin can delete any
            if ($me['role'] === 'trainer') {
                $checkPost = $db->prepare("SELECT users.actor FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ? LIMIT 1");
                $checkPost->execute([$postId]);
                $row = $checkPost->fetch();
                if (!$row || $row['actor'] !== $actor) {
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "Keine Berechtigung für diesen Beitrag."]);
                    exit;
                }
            }

            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);

            echo json_encode(["success" => true, "message" => "Beitrag gelöscht."]);
            exit;
        }

        echo json_encode(["success" => false, "message" => "Unbekannte Aktion."]);
        exit;
    }

    // GET -> list posts for actor (trainers see only their actor; board/admin can pass actor)
    $queryActor = $_GET['actor'] ?? $actor;

    $stmt = $db->prepare(
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
         WHERE users.actor = ?
         ORDER BY posts.created_at DESC"
    );

    $stmt->execute([$queryActor]);
    $posts = $stmt->fetchAll();

    echo json_encode(["success" => true, "posts" => $posts]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Fehler beim Verarbeiten der Anfrage."]);
}
