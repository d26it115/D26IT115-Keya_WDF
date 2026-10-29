
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/db_mysqli.php";
require_once __DIR__ . "/audit_logger.php";

function editEventResponse(int $status, array $data): void
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
    editEventResponse(403, [
        "success" => false,
        "message" => "Only administrators can edit events."
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    editEventResponse(405, [
        "success" => false,
        "message" => "POST requests are required."
    ]);
}

$eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
$title = trim($_POST["title"] ?? "");
$category = trim($_POST["category"] ?? "");
$eventDate = trim($_POST["event_date"] ?? "");
$venue = trim($_POST["venue"] ?? "");
$organizer = trim($_POST["organizer"] ?? "");
$description = trim($_POST["description"] ?? "");
$status = trim($_POST["status"] ?? "open");

$allowedCategories = [
    "Hackathon", "Competition", "Workshop", "Seminar",
    "Cultural", "Sports", "Other"
];

if (
    !$eventId || $eventId < 1 ||
    $title === "" ||
    $category === "" ||
    $eventDate === "" ||
    $venue === "" ||
    $organizer === ""
) {
    editEventResponse(400, [
        "success" => false,
        "message" => "Please complete all required event fields."
    ]);
}

if (
    strlen($title) > 150 ||
    strlen($venue) > 100 ||
    strlen($organizer) > 100 ||
    !in_array($category, $allowedCategories, true) ||
    !in_array($status, ["open", "closed"], true)
) {
    editEventResponse(400, [
        "success" => false,
        "message" => "One or more event fields are invalid."
    ]);
}

$dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $eventDate
) {
    editEventResponse(400, [
        "success" => false,
        "message" => "Please enter a valid event date."
    ]);
}

// Find the current event.
$findStmt = $conn->prepare(
    "SELECT title, poster FROM events WHERE event_id = ? LIMIT 1"
);

if (!$findStmt) {
    editEventResponse(500, [
        "success" => false,
        "message" => "Unable to prepare event lookup."
    ]);
}

$findStmt->bind_param("i", $eventId);
$findStmt->execute();
$existingEvent = $findStmt->get_result()->fetch_assoc();
$findStmt->close();

if (!$existingEvent) {
    editEventResponse(404, [
        "success" => false,
        "message" => "Event not found."
    ]);
}

$oldTitle = $existingEvent["title"];
$oldPoster = $existingEvent["poster"];
$newPoster = $oldPoster;
$newPosterPath = null;

// Validate an optional replacement poster.
if (
    isset($_FILES["poster"]) &&
    $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    $file = $_FILES["poster"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        editEventResponse(400, [
            "success" => false,
            "message" => "The poster upload failed."
        ]);
    }

    if ($file["size"] <= 0 || $file["size"] > 2 * 1024 * 1024) {
        editEventResponse(400, [
            "success" => false,
            "message" => "The poster must be 2 MB or smaller."
        ]);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file["tmp_name"]);

    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (
        !isset($allowedMimeTypes[$mimeType]) ||
        @getimagesize($file["tmp_name"]) === false
    ) {
        editEventResponse(400, [
            "success" => false,
            "message" => "Only valid JPG, PNG, and WebP posters are allowed."
        ]);
    }

    $uploadDirectory =
        dirname(__DIR__) . DIRECTORY_SEPARATOR .
        "images" . DIRECTORY_SEPARATOR .
        "event_posters";

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true)
    ) {
        editEventResponse(500, [
            "success" => false,
            "message" => "Unable to prepare the poster directory."
        ]);
    }

    if (!is_writable($uploadDirectory)) {
        editEventResponse(500, [
            "success" => false,
            "message" => "The poster directory is not writable."
        ]);
    }

    $newPoster = bin2hex(random_bytes(16)) .
        "." . $allowedMimeTypes[$mimeType];

    $newPosterPath =
        $uploadDirectory . DIRECTORY_SEPARATOR . $newPoster;

    if (!move_uploaded_file($file["tmp_name"], $newPosterPath)) {
        editEventResponse(500, [
            "success" => false,
            "message" => "Unable to save the new poster."
        ]);
    }
}

// Update the event.
$sql = "
    UPDATE events
    SET
        title = ?,
        category = ?,
        event_date = ?,
        venue = ?,
        organizer = ?,
        description = ?,
        poster = ?,
        status = ?
    WHERE event_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    if ($newPosterPath && is_file($newPosterPath)) {
        unlink($newPosterPath);
    }

    editEventResponse(500, [
        "success" => false,
        "message" => "Unable to prepare the event update."
    ]);
}

$stmt->bind_param(
    "ssssssssi",
    $title,
    $category,
    $eventDate,
    $venue,
    $organizer,
    $description,
    $newPoster,
    $status,
    $eventId
);

if (!$stmt->execute()) {
    $stmt->close();

    if ($newPosterPath && is_file($newPosterPath)) {
        unlink($newPosterPath);
    }

    editEventResponse(500, [
        "success" => false,
        "message" => "Unable to update the event."
    ]);
}

$stmt->close();

// Remove the old poster only after the database update succeeds.
if (
    $newPosterPath !== null &&
    !empty($oldPoster) &&
    basename($oldPoster) === $oldPoster
) {
    $oldPosterPath =
        dirname(__DIR__) . DIRECTORY_SEPARATOR .
        "images" . DIRECTORY_SEPARATOR .
        "event_posters" . DIRECTORY_SEPARATOR .
        $oldPoster;

    if (is_file($oldPosterPath) && !unlink($oldPosterPath)) {
        error_log("Unable to remove old poster for event ID " . $eventId);
    }
}

// Record the successful update.
writeAuditLog(
    $conn,
    "EVENT_UPDATED",
    "Updated event ID {$eventId}: {$oldTitle} -> {$title}"
);

echo json_encode([
    "success" => true,
    "message" => "Event updated successfully.",
    "event_id" => $eventId
]);

exit;
?>
