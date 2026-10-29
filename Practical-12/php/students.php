<?php

session_start();

require_once "db_mysqli.php";

/*
|--------------------------------------------------------------------------
| Only logged-in admins can access student management
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "Please log in to access student records."
    ]);

    exit;
}


if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => false,
        "message" => "You do not have permission to access student records."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Response type
|--------------------------------------------------------------------------
*/

header("Content-Type: application/json");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/*
|--------------------------------------------------------------------------
| Get search and filter values
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");

$course = trim($_GET["course"] ?? "");

$year = trim($_GET["year"] ?? "");

$gender = trim($_GET["gender"] ?? "");


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$page = isset($_GET["page"])
    ? (int) $_GET["page"]
    : 1;

$limit = isset($_GET["limit"])
    ? (int) $_GET["limit"]
    : 5;


/*
| Prevent invalid pagination values
*/

if ($page < 1) {
    $page = 1;
}

if ($limit < 1 || $limit > 50) {
    $limit = 5;
}

$offset = ($page - 1) * $limit;


/*
|--------------------------------------------------------------------------
| Build WHERE conditions
|--------------------------------------------------------------------------
*/

$where = [];

$parameters = [];

$types = "";


/*
|--------------------------------------------------------------------------
| Search by name, email, or course
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $where[] = "
        (
            name LIKE ?
            OR email LIKE ?
            OR course LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;

    $types .= "sss";
}


/*
|--------------------------------------------------------------------------
| Filter by course
|--------------------------------------------------------------------------
*/

if ($course !== "") {

    $where[] = "course = ?";

    $parameters[] = $course;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| Filter by year
|--------------------------------------------------------------------------
*/

if ($year !== "") {

    $where[] = "year = ?";

    $parameters[] = $year;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| Filter by gender
|--------------------------------------------------------------------------
*/

if ($gender !== "") {

    $where[] = "gender = ?";

    $parameters[] = $gender;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| Create WHERE clause
|--------------------------------------------------------------------------
*/

$whereClause = "";

if (count($where) > 0) {

    $whereClause =
        "WHERE " .
        implode(" AND ", $where);

}


/*
|--------------------------------------------------------------------------
| Count total matching students
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS total
    FROM students
    $whereClause
";


$countStmt = $conn->prepare($countSql);


if (!$countStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare student count query."
    ]);

    exit;
}


/*
| Bind search/filter parameters
*/

if (count($parameters) > 0) {

    $countStmt->bind_param(
        $types,
        ...$parameters
    );

}


$countStmt->execute();


$countResult =
    $countStmt->get_result();


$countRow =
    $countResult->fetch_assoc();


$total =
    (int) ($countRow["total"] ?? 0);


$countStmt->close();


/*
|--------------------------------------------------------------------------
| Get student records
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        student_id,
        name,
        email,
        mobile,
        course,
        year,
        gender
    FROM students
    $whereClause
    ORDER BY student_id DESC
    LIMIT ? OFFSET ?
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare student query."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Bind search/filter + pagination parameters
|--------------------------------------------------------------------------
*/

if (count($parameters) > 0) {

    /*
    | Existing parameters are strings.
    | LIMIT and OFFSET are integers.
    */

    $allTypes =
        $types . "ii";

    $allParameters =
        array_merge(
            $parameters,
            [$limit, $offset]
        );


    $stmt->bind_param(
        $allTypes,
        ...$allParameters
    );

} else {

    $stmt->bind_param(
        "ii",
        $limit,
        $offset
    );

}


/*
|--------------------------------------------------------------------------
| Execute query
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {

    $stmt->close();

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve student records."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch results
|--------------------------------------------------------------------------
*/

$result =
    $stmt->get_result();


$students = [];


while (
    $row =
    $result->fetch_assoc()
) {

    $students[] = [
        "student_id" => $row["student_id"],
        "name" => $row["name"],
        "email" => $row["email"],
        "mobile" => $row["mobile"],
        "course" => $row["course"],
        "year" => $row["year"],
        "gender" => $row["gender"]
    ];

}


$stmt->close();


/*
|--------------------------------------------------------------------------
| Calculate total pages
|--------------------------------------------------------------------------
*/

$totalPages =
    $total > 0
        ? (int) ceil($total / $limit)
        : 1;


/*
|--------------------------------------------------------------------------
| Send JSON response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => "Student records retrieved successfully.",
    "students" => $students,
    "total" => $total,
    "page" => $page,
    "limit" => $limit,
    "total_pages" => $totalPages
]);

exit;

?>