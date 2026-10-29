<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once "db_mysqli.php";


if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Only administrators can add events."
    ]);

    exit;
}



if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "POST requests are required."
    ]);

    exit;
}



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

if (
    $title === "" ||
    $category === "" ||
    $eventDate === "" ||
    $venue === "" ||
    $organizer === ""
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required event fields."
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
        "message" => "One or more event fields contain invalid values."
    ]);

    exit;
}


// Validate date format and calendar date.
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



$posterName = null;
$uploadedPath = null;

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

    // Maximum size: 2 MB.
    $maxSize = 2 * 1024 * 1024;

    if ($file["size"] <= 0 || $file["size"] > $maxSize) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Poster size must be between 1 byte and 2 MB."
        ]);

        exit;
    }

    // Detect the actual image MIME type from its contents.
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

    // Confirm that the uploaded file is a readable image.
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
            "message" => "Unable to prepare the poster upload directory."
        ]);

        exit;
    }

    if (!is_writable($uploadDirectory)) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "The poster upload directory is not writable."
        ]);

        exit;
    }

    // Generate a safe, unpredictable filename.
    $posterName =
        bin2hex(random_bytes(16)) .
        "." .
        $allowedMimeTypes[$mimeType];

    $uploadedPath =
        $uploadDirectory . DIRECTORY_SEPARATOR . $posterName;

    if (!move_uploaded_file($file["tmp_name"], $uploadedPath)) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to save the uploaded poster."
        ]);

        exit;
    }
}


// --------------------------------------------------
// Insert event using a prepared statement
// --------------------------------------------------

$sql = "
    INSERT INTO events (
        title,
        category,
        event_date,
        venue,
        organizer,
        description,
        poster,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    if ($uploadedPath && is_file($uploadedPath)) {
        unlink($uploadedPath);
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare the event insert query."
    ]);

    exit;
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

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to save the event to the database."
    ]);

    exit;
}

$eventId = $stmt->insert_id;

$stmt->close();



echo json_encode([
    "success" => true,
    "message" => "Event added successfully.",
    "event_id" => $eventId
]);

exit;

?>