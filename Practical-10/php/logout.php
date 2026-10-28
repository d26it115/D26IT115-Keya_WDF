<?php

session_start();

require_once "db_mysqli.php";


/*
|--------------------------------------------------------------------------
| Remove Remember-Me token from database
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["student_id"])) {

    $studentId = $_SESSION["student_id"];

    $sql = "
        UPDATE students
        SET
            remember_selector = NULL,
            remember_token_hash = NULL,
            remember_expires = NULL
        WHERE student_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param("i", $studentId);

        $stmt->execute();

        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| Remove Remember-Me cookie
|--------------------------------------------------------------------------
*/

setcookie(
    "studenthub_remember",
    "",
    [
        "expires" => time() - 42000,
        "path" => "/sh/",
        "secure" => false,
        "httponly" => true,
        "samesite" => "Lax"
    ]
);


/*
|--------------------------------------------------------------------------
| Destroy session
|--------------------------------------------------------------------------
*/

$_SESSION = [];


if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


session_destroy();


/*
|--------------------------------------------------------------------------
| Redirect to login
|--------------------------------------------------------------------------
*/

header("Location: ../pages/login.html");

exit;

?>