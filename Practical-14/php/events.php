<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once "db_mysqli.php";

// Allow administrators only.
if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Only administrators can manage events."
    ]);

    exit;
}

// Search and filters.
$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$status = trim($_GET["status"] ?? "");

// Pagination.
$page = max(1, (int) ($_GET["page"] ?? 1));
$limit = (int) ($_GET["limit"] ?? 5);

if ($limit < 1 || $limit > 50) {
    $limit = 5;
}

$offset = ($page - 1) * $limit;

// Build parameterized WHERE conditions.
$where = [];
$params = [];
$types = "";

if ($search !== "") {
    $where[] = "(
        title LIKE ?
        OR venue LIKE ?
        OR organizer LIKE ?
        OR description LIKE ?
    )";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ssss";
}

if ($category !== "") {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($status !== "") {
    if (!in_array($status, ["open", "closed"], true)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid event status."
        ]);

        exit;
    }

    $where[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereClause = "";

if (!empty($where)) {
    $whereClause = "WHERE " . implode(" AND ", $where);
}

// Count matching events.
$countSql = "
    SELECT COUNT(*) AS total
    FROM events
    $whereClause
";

$countStmt = $conn->prepare($countSql);

if (!$countStmt) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare event count query."
    ]);

    exit;
}

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

if (!$countStmt->execute()) {
    $countStmt->close();
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to count events."
    ]);

    exit;
}

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$total = (int) ($countRow["total"] ?? 0);

$countStmt->close();

// Retrieve event records.
$sql = "
    SELECT
        event_id,
        title,
        category,
        event_date,
        venue,
        organizer,
        description,
        poster,
        status
    FROM events
    $whereClause
    ORDER BY event_id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare event query."
    ]);

    exit;
}

$allParams = $params;
$allParams[] = $limit;
$allParams[] = $offset;

$allTypes = $types . "ii";

$stmt->bind_param($allTypes, ...$allParams);

if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve events."
    ]);

    exit;
}

$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$stmt->close();

$totalPages = max(1, (int) ceil($total / $limit));

echo json_encode([
    "success" => true,
    "message" => "Events retrieved successfully.",
    "events" => $events,
    "total" => $total,
    "page" => $page,
    "limit" => $limit,
    "total_pages" => $totalPages
]);

exit;
?>