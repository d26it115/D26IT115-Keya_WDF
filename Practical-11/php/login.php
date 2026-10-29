<?php

session_start();

require_once "db_mysqli.php";


/* Only allow POST requests */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/login.html");
    exit;
}


/* Get form values */

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$remember = isset($_POST["remember"]);


/* Validate */

if ($email === "" || $password === "") {

    header(
        "Location: ../pages/login.html?error=" .
        urlencode("Email and password are required.")
    );

    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    header(
        "Location: ../pages/login.html?error=" .
        urlencode("Please enter a valid email address.")
    );

    exit;
}


/* Find user */

$sql = "
    SELECT
        student_id,
        name,
        email,
        password,
        role
    FROM students
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    header(
        "Location: ../pages/login.html?error=" .
        urlencode("Unable to process login.")
    );

    exit;
}


$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


/* Verify password */

if (!$user || !password_verify($password, $user["password"])) {

    header(
        "Location: ../pages/login.html?error=" .
        urlencode("Invalid email or password.")
    );

    exit;
}


/* Regenerate session ID */

session_regenerate_id(true);


/* Store session information */

$_SESSION["logged_in"] = true;
$_SESSION["student_id"] = $user["student_id"];
$_SESSION["name"] = $user["name"];
$_SESSION["email"] = $user["email"];
$_SESSION["role"] = $user["role"];
$_SESSION["last_activity"] = time();


/*
|--------------------------------------------------------------------------
| Remember Me
|--------------------------------------------------------------------------
*/

if ($remember) {

    $selector = bin2hex(random_bytes(16));

    $token = bin2hex(random_bytes(32));

    $tokenHash = hash("sha256", $token);

    $expires = date(
        "Y-m-d H:i:s",
        time() + (30 * 24 * 60 * 60)
    );


    /* Save token in database */

    $rememberSql = "
        UPDATE students
        SET
            remember_selector = ?,
            remember_token_hash = ?,
            remember_expires = ?
        WHERE student_id = ?
    ";

    $rememberStmt = $conn->prepare($rememberSql);

    if ($rememberStmt) {

        $rememberStmt->bind_param(
            "sssi",
            $selector,
            $tokenHash,
            $expires,
            $user["student_id"]
        );

        $rememberStmt->execute();

        $rememberStmt->close();
    }


    /* Save token in secure cookie */

    setcookie(
        "studenthub_remember",
        $selector . ":" . $token,
        [
            "expires" => time() + (30 * 24 * 60 * 60),
            "path" => "/sh/",
            "secure" => false,
            "httponly" => true,
            "samesite" => "Lax"
        ]
    );
}


/*
|--------------------------------------------------------------------------
| Role-based redirection
|--------------------------------------------------------------------------
*/

if ($user["role"] === "admin") {

    header("Location: ../pages/admin_dashboard.html");
    exit;
}


/* Normal student */

header("Location: ../pages/dashboard.html");
exit;

?>