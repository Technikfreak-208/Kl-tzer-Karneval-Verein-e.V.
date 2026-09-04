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

        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $startAt = trim($_POST['start_at'] ?? '');

            if ($title === '' || $startAt === '') {
                echo json_encode(["success" => false, "message" => "Titel und Startzeit erforderlich."]);
                exit;
            }

            // validators for datetime could be added
            $stmt = $db->prepare("INSERT INTO events (actor, title, description, start_at, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$actor, $title, $description, $startAt, $_SESSION['user_id']]);

            echo json_encode(["success" => true, "message" => "Termin erstellt."]);
            exit;
        }

        if ($action === 'delete') {
            $eventId = intval($_POST['event_id'] ?? 0);
            if ($eventId <= 0) {
                echo json_encode(["success" => false, "message" => "Ungültige Event-ID."]);
                exit;
            }

            // trainers can only delete events for their actor
            if ($me['role'] === 'trainer') {
                $checkEv = $db->prepare("SELECT actor FROM events WHERE id = ? LIMIT 1");
                $checkEv->execute([$eventId]);
                $ev = $checkEv->fetch();
                if (!$ev || $ev['actor'] !== $actor) {
                    http_response_code(403);
                    echo json_encode(["success" => false, "message" => "Keine Berechtigung für diesen Termin."]);
                    exit;
                }
            }

            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$eventId]);

            echo json_encode(["success" => true, "message" => "Termin gelöscht."]);
            exit;
        }

        echo json_encode(["success" => false, "message" => "Unbekannte Aktion."]);
        exit;
    }

    // GET -> events for the actor (trainers: their actor; board/admin can pass actor param)
    $queryActor = $_GET['actor'] ?? $actor;

    $stmt = $db->prepare("SELECT id, actor, title, description, start_at, created_by, created_at FROM events WHERE actor = ? ORDER BY start_at ASC");
    $stmt->execute([$queryActor]);
    $events = $stmt->fetchAll();

    echo json_encode(["success" => true, "events" => $events]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Fehler beim Verarbeiten der Anfrage."]);
}
