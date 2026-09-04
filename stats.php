<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

try {
    $db = getDatabase();

    // counts
    $stmt = $db->query("SELECT COUNT(*) AS c FROM users");
    $row = $stmt->fetch();
    $users = intval($row['c'] ?? 0);

    $stmt = $db->query("SELECT COUNT(*) AS c FROM posts");
    $row = $stmt->fetch();
    $posts = intval($row['c'] ?? 0);

    $stmt = $db->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
    $row = $stmt->fetch();
    $admins = intval($row['c'] ?? 0);

    echo json_encode(["success" => true, "users" => $users, "posts" => $posts, "admins" => $admins]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Statistiken konnten nicht geladen werden."]);
}
