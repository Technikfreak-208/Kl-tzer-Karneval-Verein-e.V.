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

    // Admin/board see all or filter? Trainers see only their actor; board/admin can pass ?actor=...
    $queryActor = $_GET['actor'] ?? $actor;

    $stmt = $db->prepare("SELECT id, first_name, last_name, username, actor, role FROM users WHERE actor = ? ORDER BY last_name, first_name");
    $stmt->execute([$queryActor]);

    $users = $stmt->fetchAll();

    echo json_encode(["success" => true, "users" => $users]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Fehler beim Laden der Mitglieder."]);
}
