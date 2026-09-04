<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Ungültige Anfrage."]);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt."]);
    exit;
}

$userIdToSet = intval($_POST["user_id"] ?? 0);
$newRole = trim($_POST["role"] ?? "");

$validRoles = ['member', 'trainer', 'board', 'admin'];

if ($userIdToSet <= 0 || $newRole === "" || !in_array($newRole, $validRoles, true)) {
    echo json_encode(["success" => false, "message" => "Ungültige Daten."]);
    exit;
}

try {
    $db = getDatabase();

    // Prüfen ob aktueller Benutzer Admin ist
    $check = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $check->execute([$_SESSION["user_id"]]);
    $me = $check->fetch();

    if (!$me || $me["role"] !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Keine Berechtigung."]);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userIdToSet]);

    echo json_encode(["success" => true, "message" => "Rolle wurde aktualisiert."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Rolle konnte nicht gesetzt werden."]);
}
