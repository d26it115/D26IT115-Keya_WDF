<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once "db_mysqli.php";


// --------------------------------------------------
// Admin-only access
// --------------------------------------------------

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Only administrators can delete events."
    ]);

    exit;
}


// --------------------------------------------------
// POST requests only
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "POST requests are required."
    ]);

    exit;
}


// --------------------------------------------------
// Validate event ID
// --------------------------------------------------

$eventId = filter_input(
    INPUT_POST,
    "event_id",
    FILTER_VALIDATE_INT
);

if (!$eventId || $eventId < 1) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid event ID is required."
    ]);

    exit;
}


// --------------------------------------------------
// Find the event and its poster
// --------------------------------------------------

$findStmt = $conn->prepare(
    "SELECT poster FROM events WHERE event_id = ? LIMIT 1"
);

if (!$findStmt) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare event lookup."
    ]);

    exit;
}

$findStmt->bind_param("i", $eventId);
$findStmt->execute();

$event = $findStmt->get_result()->fetch_assoc();

$findStmt->close();

if (!$event) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Event not found."
    ]);

    exit;
}


// --------------------------------------------------
// Delete event from the database
// --------------------------------------------------

$deleteStmt = $conn->prepare(
    "DELETE FROM events WHERE event_id = ?"
);

if (!$deleteStmt) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare event deletion."
    ]);

    exit;
}

$deleteStmt->bind_param("i", $eventId);

if (!$deleteStmt->execute()) {
    $deleteStmt->close();

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to delete the event."
    ]);

    exit;
}

$deleted = $deleteStmt->affected_rows;

$deleteStmt->close();

if ($deleted !== 1) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "The event could not be found for deletion."
    ]);

    exit;
}


// --------------------------------------------------
// Remove the poster file after successful deletion
// --------------------------------------------------

$poster = $event["poster"];

if (
    !empty($poster) &&
    basename($poster) === $poster
) {
    $posterPath =
        dirname(__DIR__) . DIRECTORY_SEPARATOR .
        "images" . DIRECTORY_SEPARATOR .
        "event_posters" . DIRECTORY_SEPARATOR .
        $poster;

    if (is_file($posterPath)) {
        if (!unlink($posterPath)) {
            error_log(
                "Unable to remove event poster for event ID " .
                $eventId
            );
        }
    }
}


// --------------------------------------------------
// Success response
// --------------------------------------------------

echo json_encode([
    "success" => true,
    "message" => "Event deleted successfully.",
    "event_id" => $eventId
]);

exit;

?>
