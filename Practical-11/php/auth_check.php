<?php

session_start();

require_once "db_mysqli.php";


/*
|--------------------------------------------------------------------------
| Session timeout
|--------------------------------------------------------------------------
|
| 15 minutes of inactivity
|
*/

$timeout = 15 * 60;


if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    if (
        isset($_SESSION["last_activity"]) &&
        (time() - $_SESSION["last_activity"]) > $timeout
    ) {

        /*
         * Session expired
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

        header(
            "Location: ../pages/login.html?error=" .
            urlencode("Your session has expired. Please log in again.")
        );

        exit;
    }


    /*
     * Update last activity
     */

    $_SESSION["last_activity"] = time();

    return;
}


/*
|--------------------------------------------------------------------------
| Remember-Me authentication
|--------------------------------------------------------------------------
*/

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

            $stmt->bind_param(
                "s",
                $selector
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $user = $result->fetch_assoc();

            $stmt->close();


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

                /*
                 * Valid remember-me token
                 */

                session_regenerate_id(true);

                $_SESSION["logged_in"] = true;

                $_SESSION["student_id"] =
                    $user["student_id"];

                $_SESSION["name"] =
                    $user["name"];

                $_SESSION["email"] =
                    $user["email"];

                $_SESSION["role"] =
                    $user["role"];

                $_SESSION["last_activity"] =
                    time();

                return;
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| User is not authenticated
|--------------------------------------------------------------------------
*/

header(
    "Location: ../pages/login.html?error=" .
    urlencode("Please log in to access this page.")
);

exit;

?>