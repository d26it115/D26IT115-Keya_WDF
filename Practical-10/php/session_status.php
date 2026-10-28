<?php

session_start();

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$timeout = 15 * 60; // 15 minutes

// --------------------------------------------------
// 1. Check existing session
// --------------------------------------------------

if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    // Check session timeout
    if (
        isset($_SESSION["last_activity"]) &&
        (time() - $_SESSION["last_activity"]) > $timeout
    ) {

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

        echo json_encode([
            "logged_in" => false,
            "message" => "Your session has expired. Please log in again."
        ]);

        exit;
    }

    // Update activity time
    $_SESSION["last_activity"] = time();

    echo json_encode([
        "logged_in" => true,
        "student_id" => $_SESSION["student_id"],
        "name" => $_SESSION["name"],
        "email" => $_SESSION["email"],
        "role" => $_SESSION["role"]
    ]);

    exit;
}


// --------------------------------------------------
// 2. Check Remember Me cookie
// --------------------------------------------------

if (isset($_COOKIE["studenthub_remember"])) {

    $cookieParts = explode(
        ":",
        $_COOKIE["studenthub_remember"],
        2
    );

    if (count($cookieParts) === 2) {

        $selector = $cookieParts[0];
        $token = $cookieParts[1];

        $tokenHash = hash("sha256", $token);

        require_once "db_mysqli.php";

        $sql = "
            SELECT
                student_id,
                name,
                email,
                role,
                remember_token_hash,
                remember_expires
            FROM students
            WHERE remember_selector = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $selector);

            $stmt->execute();

            $result = $stmt->get_result();

            $user = $result->fetch_assoc();

            $stmt->close();

            // Validate remember-me token
            if (
                $user &&
                !empty($user["remember_token_hash"]) &&
                !empty($user["remember_expires"]) &&
                strtotime($user["remember_expires"]) > time() &&
                hash_equals(
                    $user["remember_token_hash"],
                    $tokenHash
                )
            ) {

                // Create a new session
                session_regenerate_id(true);

                $_SESSION["logged_in"] = true;
                $_SESSION["student_id"] = $user["student_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["last_activity"] = time();

                echo json_encode([
                    "logged_in" => true,
                    "student_id" => $user["student_id"],
                    "name" => $user["name"],
                    "email" => $user["email"],
                    "role" => $user["role"]
                ]);

                exit;
            }
        }
    }
}


// --------------------------------------------------
// 3. User is not logged in
// --------------------------------------------------

echo json_encode([
    "logged_in" => false,
    "message" => "Please log in to access this page."
]);

exit;

?>