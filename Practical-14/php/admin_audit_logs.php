
<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/db_mysqli.php";

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Allow administrators only.
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    respond(401, [
        "success" => false,
        "message" => "Please log in first."
    ]);
}

if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    respond(403, [
        "success" => false,
        "message" => "Only administrators can view audit logs."
    ]);
}

// Retrieve the latest 100 audit records.
$sql = "
    SELECT
        log_id,
        user_id,
        user_name,
        user_role,
        action,
        details,
        ip_address,
        created_at
    FROM audit_logs
    ORDER BY created_at DESC, log_id DESC
    LIMIT 100
";

$result = $conn->query($sql);

if (!$result) {
    error_log("Audit log retrieval failed: " . $conn->error);

    respond(500, [
        "success" => false,
        "message" => "Unable to retrieve audit logs."
    ]);
}

$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$result->free();

echo json_encode([
    "success" => true,
    "message" => "Audit logs retrieved successfully.",
    "total" => count($logs),
    "logs" => $logs
]);

exit;
?>
