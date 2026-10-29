
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/db.php";

function respond($success, $message, $data = [], $status = 200)
{
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

// Require an authenticated administrator.
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    respond(false, "Please log in first.", [], 401);
}

if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    respond(false, "Only administrators can access dashboard data.", [], 403);
}

try {
    // Count registered student accounts.
    $studentCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM students")
        ->fetchColumn();

    // Count campus events.
    $eventCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM events")
        ->fetchColumn();

    // Count event registrations.
    $registrationCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM registrations")
        ->fetchColumn();

    // Get the latest registrations with student and event details.
    $sql = "
        SELECT
            r.registration_id,
            r.registered_at,
            s.name AS student_name,
            s.email AS student_email,
            e.title AS event_title
        FROM registrations r
        INNER JOIN students s
            ON s.student_id = r.student_id
        INNER JOIN events e
            ON e.event_id = r.event_id
        ORDER BY r.registered_at DESC, r.registration_id DESC
        LIMIT 5
    ";

    $latestRegistrations = $pdo
        ->query($sql)
        ->fetchAll(PDO::FETCH_ASSOC);

    respond(true, "Dashboard data retrieved successfully.", [
        "student_count" => $studentCount,
        "event_count" => $eventCount,
        "registration_count" => $registrationCount,
        "latest_registrations" => $latestRegistrations
    ]);

} catch (PDOException $e) {
    error_log("Admin dashboard query failed: " . $e->getMessage());

    respond(
        false,
        "Unable to load dashboard data. Please check the database configuration.",
        [],
        500
    );
}
