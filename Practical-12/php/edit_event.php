<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once "db_mysqli.php";


// Admin-only access.
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Only administrators can edit events."
    ]);

    exit;
}


// POST requests only.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "POST requests are required."
    ]);

    exit;
}


// Read fields.
$eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
$title = trim($_POST["title"] ?? "");
$category = trim($_POST["category"] ?? "");
$eventDate = trim($_POST["event_date"] ?? "");
$venue = trim($_POST["venue"] ?? "");
$organizer = trim($_POST["organizer"] ?? "");
$description = trim($_POST["description"] ?? "");
$status = trim($_POST["status"] ?? "open");

$allowedCategories = [
    "Hackathon",
    "Competition",
    "Workshop",
    "Seminar",
    "Cultural",
    "Sports",
    "Other"
];


// Validate required fields.
if (
    !$eventId ||
    $title === "" ||
    $category === "" ||
    $eventDate === "" ||
    $venue === "" ||
    $organizer === ""
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please complete all required event fields."
    ]);

    exit;
}

if (
    strlen($title) > 150 ||
    strlen($venue) > 100 ||
    strlen($organizer) > 100 ||
    !in_array($category, $allowedCategories, true) ||
    !in_array($status, ["open", "closed"], true)
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "One or more event fields are invalid."
    ]);

    exit;
}


// Validate date.
$dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $eventDate
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid event date."
    ]);

    exit;
}


// Find existing event and poster.
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

$existingEvent = $findStmt->get_result()->fetch_assoc();

$findStmt->close();

if (!$existingEvent) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Event not found."
    ]);

    exit;
}

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
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "The poster upload failed."
        ]);

        exit;
    }

    if ($file["size"] <= 0 || $file["size"] > 2 * 1024 * 1024) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "The poster must be 2 MB or smaller."
        ]);

        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file["tmp_name"]);

    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Only JPG, PNG, and WebP posters are allowed."
        ]);

        exit;
    }

    if (@getimagesize($file["tmp_name"]) === false) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "The uploaded file is not a valid image."
        ]);

        exit;
    }

    $uploadDirectory =
        dirname(__DIR__) . DIRECTORY_SEPARATOR .
        "images" . DIRECTORY_SEPARATOR .
        "event_posters";

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true)
    ) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare the poster directory."
        ]);

        exit;
    }

    if (!is_writable($uploadDirectory)) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "The poster directory is not writable."
        ]);

        exit;
    }

    $newPoster =
        bin2hex(random_bytes(16)) .
        "." .
        $allowedMimeTypes[$mimeType];

    $newPosterPath =
        $uploadDirectory . DIRECTORY_SEPARATOR . $newPoster;

    if (!move_uploaded_file($file["tmp_name"], $newPosterPath)) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to save the new poster."
        ]);

        exit;
    }
}


// Update event using a prepared statement.
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

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare the event update."
    ]);

    exit;
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

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to update the event."
    ]);

    exit;
}

$stmt->close();


// Delete the old poster only after the database update succeeds.
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

    if (is_file($oldPosterPath)) {
        unlink($oldPosterPath);
    }
}


echo json_encode([
    "success" => true,
    "message" => "Event updated successfully.",
    "event_id" => $eventId
]);

exit;

?>
