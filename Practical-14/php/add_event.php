
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/db_mysqli.php";
require_once __DIR__ . "/audit_logger.php";

function addEventResponse(int $status, array $data): void
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
    addEventResponse(403, [
        "success" => false,
        "message" => "Only administrators can add events."
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    addEventResponse(405, [
        "success" => false,
        "message" => "POST requests are required."
    ]);
}

// Read submitted fields.
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
    $title === "" ||
    $category === "" ||
    $eventDate === "" ||
    $venue === "" ||
    $organizer === ""
) {
    addEventResponse(400, [
        "success" => false,
        "message" => "Please fill in all required event fields."
    ]);
}

if (
    strlen($title) > 150 ||
    strlen($venue) > 100 ||
    strlen($organizer) > 100 ||
    !in_array($category, $allowedCategories, true) ||
    !in_array($status, ["open", "closed"], true)
) {
    addEventResponse(400, [
        "success" => false,
        "message" => "One or more event fields contain invalid values."
    ]);
}

// Validate date format and calendar date.
$dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $eventDate
) {
    addEventResponse(400, [
        "success" => false,
        "message" => "Please enter a valid event date."
    ]);
}

$posterName = null;
$uploadedPath = null;

// Validate and save an optional poster.
if (
    isset($_FILES["poster"]) &&
    $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    $file = $_FILES["poster"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        addEventResponse(400, [
            "success" => false,
            "message" => "The poster upload failed."
        ]);
    }

    $maxSize = 2 * 1024 * 1024;

    if ($file["size"] <= 0 || $file["size"] > $maxSize) {
        addEventResponse(400, [
            "success" => false,
            "message" => "Poster size must be between 1 byte and 2 MB."
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
        addEventResponse(400, [
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
        addEventResponse(500, [
            "success" => false,
            "message" => "Unable to prepare the poster upload directory."
        ]);
    }

    if (!is_writable($uploadDirectory)) {
        addEventResponse(500, [
            "success" => false,
            "message" => "The poster upload directory is not writable."
        ]);
    }

    $posterName = bin2hex(random_bytes(16)) .
        "." . $allowedMimeTypes[$mimeType];

    $uploadedPath =
        $uploadDirectory . DIRECTORY_SEPARATOR . $posterName;

    if (!move_uploaded_file($file["tmp_name"], $uploadedPath)) {
        addEventResponse(500, [
            "success" => false,
            "message" => "Unable to save the uploaded poster."
        ]);
    }
}

// Insert event using a prepared statement.
$sql = "
    INSERT INTO events (
        title, category, event_date, venue,
        organizer, description, poster, status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }

    addEventResponse(500, [
        "success" => false,
        "message" => "Unable to prepare the event insert query."
    ]);
}

$stmt->bind_param(
    "ssssssss",
    $title,
    $category,
    $eventDate,
    $venue,
    $organizer,
    $description,
    $posterName,
    $status
);

if (!$stmt->execute()) {
    $stmt->close();

    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }

    addEventResponse(500, [
        "success" => false,
        "message" => "Unable to save the event to the database."
    ]);
}

$eventId = (int) $stmt->insert_id;
$stmt->close();

// Record the successful action.
writeAuditLog(
    $conn,
    "EVENT_CREATED",
    "Created event ID {$eventId}: {$title}"
);

echo json_encode([
    "success" => true,
    "message" => "Event added successfully.",
    "event_id" => $eventId
]);

exit;
?>
