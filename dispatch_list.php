<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt."]);
    exit;
}

try {
    $db = getDatabase();

    // Prüfen Rolle: trainer/board/admin können sehen; others no
    $check = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $check->execute([$_SESSION['user_id']]);
    $me = $check->fetch();

    if (!$me || !in_array($me['role'], ['trainer','board','admin'], true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Keine Berechtigung."]);
        exit;
    }

    // Optional filter by actor
    $actor = $_GET['actor'] ?? null;
    if ($actor) {
        $stmt = $db->prepare("SELECT id,type,severity,message,actor,created_by,created_at FROM dispatches WHERE actor = ? ORDER BY created_at DESC LIMIT 200");
        $stmt->execute([$actor]);
    } else {
        $stmt = $db->query("SELECT id,type,severity,message,actor,created_by,created_at FROM dispatches ORDER BY created_at DESC LIMIT 200");
    }

    $dispatches = $stmt->fetchAll();

    echo json_encode(["success" => true, "dispatches" => $dispatches]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Dispatches konnten nicht geladen werden."]);
}
