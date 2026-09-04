<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Ungültige Anfrage."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt."]);
    exit;
}

$type = trim($_POST['type'] ?? 'alert');
$severity = trim($_POST['severity'] ?? 'critical');
$message = trim($_POST['message'] ?? '');
$actor = trim($_POST['actor'] ?? '');

if ($message === '') {
    echo json_encode(["success" => false, "message" => "Nachricht darf nicht leer sein."]);
    exit;
}

try {
    $db = getDatabase();

    // Prüfen, ob Benutzer Rolle hat (trainer/board/admin)
    $check = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $check->execute([$_SESSION['user_id']]);
    $me = $check->fetch();

    if (!$me || !in_array($me['role'], ['trainer','board','admin'], true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Keine Berechtigung."]);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO dispatches (type, severity, message, actor, created_by, status) VALUES (?, ?, ?, ?, ?, 'queued')");
    $stmt->execute([$type, $severity, $message, $actor, $_SESSION['user_id']]);

    $dispatchId = $db->lastInsertId();

    // optional: push to configured webhooks (comma-separated)
    $webhooks = defined('DISPATCH_WEBHOOKS') ? DISPATCH_WEBHOOKS : '';
    if ($webhooks !== '') {
        $urls = array_filter(array_map('trim', explode(',', $webhooks)));
        foreach ($urls as $url) {
            // fire-and-forget POST (best effort)
            $payload = json_encode([
                'id' => $dispatchId,
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
                'actor' => $actor,
                'created_by' => $_SESSION['user_id'],
                'created_at' => date('c')
            ]);

            // Use curl, short timeout
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    echo json_encode(["success" => true, "message" => "Dispatch erstellt.", "id" => $dispatchId]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Fehler beim Erstellen des Dispatches."]);
}

