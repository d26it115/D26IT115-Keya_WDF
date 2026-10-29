
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/db_mysqli.php";
require_once __DIR__ . "/audit_logger.php";

function deleteEventResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Admin-only access.
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    deleteEventResponse(403, [
        "success" => false,
        "message" => "Only administrators can delete events."
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    deleteEventResponse(405, [
        "success" => false,
        "message" => "POST requests are required."
    ]);
}

// Validate event ID.
$eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    deleteEventResponse(400, [
        "success" => false,
        "message" => "A valid event ID is required."
    ]);
}

// Find the event and its poster before deleting it.
$findStmt = $conn->prepare(
    "SELECT title, poster FROM events WHERE event_id = ? LIMIT 1"
);

if (!$findStmt) {
    deleteEventResponse(500, [
        "success" => false,
        "message" => "Unable to prepare event lookup."
    ]);
}

$findStmt->bind_param("i", $eventId);
$findStmt->execute();

$event = $findStmt->get_result()->fetch_assoc();

$findStmt->close();

if (!$event) {
    deleteEventResponse(404, [
        "success" => false,
        "message" => "Event not found."
    ]);
}

$eventTitle = $event["title"];
$poster = $event["poster"];

// Delete the event from the database.
$deleteStmt = $conn->prepare(
    "DELETE FROM events WHERE event_id = ?"
);

if (!$deleteStmt) {
    deleteEventResponse(500, [
        "success" => false,
        "message" => "Unable to prepare event deletion."
    ]);
}

$deleteStmt->bind_param("i", $eventId);

if (!$deleteStmt->execute()) {
    $deleteStmt->close();

    deleteEventResponse(500, [
        "success" => false,
        "message" => "Unable to delete the event."
    ]);
}

$deleted = $deleteStmt->affected_rows;
$deleteStmt->close();

if ($deleted !== 1) {
    deleteEventResponse(404, [
        "success" => false,
        "message" => "The event could not be found for deletion."
    ]);
}

// Record the successful deletion.
writeAuditLog(
    $conn,
    "EVENT_DELETED",
    "Deleted event ID {$eventId}: {$eventTitle}"
);

// Remove the poster file after the database deletion succeeds.
if (
    !empty($poster) &&
    basename($poster) === $poster
) {
    $posterPath =
        dirname(__DIR__) . DIRECTORY_SEPARATOR .
        "images" . DIRECTORY_SEPARATOR .
        "event_posters" . DIRECTORY_SEPARATOR .
        $poster;

    if (is_file($posterPath) && !unlink($posterPath)) {
        error_log(
            "Unable to remove event poster for event ID " . $eventId
        );
    }
}

echo json_encode([
    "success" => true,
    "message" => "Event deleted successfully.",
    "event_id" => $eventId
]);

exit;
?>
