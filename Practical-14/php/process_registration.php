<?php

/*
============================================================
StudentHub - Practical 9
Secure User Registration
MySQLi + Server-Side Validation
Duplicate Email Check + Password Hashing
============================================================
*/


/* =========================================================
   1. ONLY ALLOW POST REQUEST
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../pages/register.html");
    exit;

}


/* =========================================================
   2. CONNECT TO MYSQL DATABASE
   ========================================================= */

require_once "db_mysqli.php";


/* =========================================================
   3. GET FORM DATA
   ========================================================= */

$name = trim($_POST["name"] ?? "");

$email = trim($_POST["email"] ?? "");

$mobile = trim($_POST["mobile"] ?? "");

$password = $_POST["password"] ?? "";

$confirmPassword = $_POST["confirmPassword"] ?? "";

$course = trim($_POST["course"] ?? "");

$year = trim($_POST["year"] ?? "");

$gender = trim($_POST["gender"] ?? "");

$terms = trim($_POST["terms"] ?? "");


/* =========================================================
   4. SERVER-SIDE VALIDATION
   ========================================================= */

$errors = [];


/* ---------- NAME ---------- */

if ($name === "") {

    $errors[] = "Name is required.";

} elseif (!preg_match("/^[A-Za-z ]{2,50}$/", $name)) {

    $errors[] =
        "Name must contain only letters and spaces (2-50 characters).";

}


/* ---------- EMAIL ---------- */

if ($email === "") {

    $errors[] = "Email is required.";

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errors[] = "Please provide a valid email address.";

}


/* ---------- MOBILE ---------- */

if ($mobile === "") {

    $errors[] = "Mobile number is required.";

} elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {

    $errors[] =
        "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";

}


/* ---------- PASSWORD ---------- */

$passwordRegex =
    "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}$/";

if ($password === "") {

    $errors[] = "Password is required.";

} elseif (!preg_match($passwordRegex, $password)) {

    $errors[] =
        "Password must be at least 8 characters and include uppercase, lowercase, numbers, and symbols.";

}


/* ---------- CONFIRM PASSWORD ---------- */

if ($confirmPassword === "") {

    $errors[] = "Please confirm your password.";

} elseif ($password !== $confirmPassword) {

    $errors[] = "Password and confirm password do not match.";

}


/* ---------- COURSE ---------- */

$allowedCourses = [
    "BCA",
    "BBA",
    "B.Sc",
    "B.Com",
    "B.E.",
    "B.Tech",
    "MCA"
];

if (
    $course === "" ||
    !in_array($course, $allowedCourses, true)
) {

    $errors[] = "Please select a valid course.";

}


/* ---------- YEAR ---------- */

$allowedYears = [
    "First Year",
    "Second Year",
    "Third Year",
    "Fourth Year"
];

if (
    $year === "" ||
    !in_array($year, $allowedYears, true)
) {

    $errors[] = "Please select a valid academic year.";

}


/* ---------- GENDER ---------- */

$allowedGenders = [
    "Male",
    "Female",
    "Other"
];

if (
    $gender === "" ||
    !in_array($gender, $allowedGenders, true)
) {

    $errors[] = "Please select a valid gender option.";

}


/* ---------- TERMS ---------- */

if ($terms !== "accepted") {

    $errors[] =
        "You must accept the terms and conditions.";

}


/* =========================================================
   5. IF VALIDATION FAILS
   ========================================================= */

if (!empty($errors)) {

    $message = implode(" ", $errors);

    header(
        "Location: ../pages/register.html?error="
        . urlencode($message)
    );

    exit;

}


/* =========================================================
   6. CHECK DUPLICATE EMAIL
   ========================================================= */

$checkSql =
    "SELECT student_id
     FROM students
     WHERE email = ?
     LIMIT 1";

$checkStmt = $conn->prepare($checkSql);


if ($checkStmt === false) {

    die(
        "Database error: "
        . $conn->error
    );

}


$checkStmt->bind_param(
    "s",
    $email
);

$checkStmt->execute();

$checkStmt->store_result();


if ($checkStmt->num_rows > 0) {

    $checkStmt->close();

    header(
        "Location: ../pages/register.html?error="
        . urlencode("This email address is already registered.")
    );

    exit;

}


$checkStmt->close();


/* =========================================================
   7. HASH PASSWORD
   ========================================================= */

$hashedPassword =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/* =========================================================
   8. INSERT STUDENT USING PREPARED STATEMENT
   ========================================================= */

$insertSql =
    "INSERT INTO students
    (
        name,
        email,
        mobile,
        course,
        year,
        gender,
        password
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )";


$insertStmt = $conn->prepare($insertSql);


if ($insertStmt === false) {

    die(
        "Database error: "
        . $conn->error
    );

}


$insertStmt->bind_param(
    "sssssss",
    $name,
    $email,
    $mobile,
    $course,
    $year,
    $gender,
    $hashedPassword
);


/* =========================================================
   9. EXECUTE INSERT
   ========================================================= */

if ($insertStmt->execute()) {

    $insertStmt->close();

    $conn->close();

    header(
        "Location: ../pages/register.html?success=1"
    );

    exit;

}


/* =========================================================
   10. INSERT ERROR
   ========================================================= */

$errorMessage =
    "Registration failed. Please try again.";

$insertStmt->close();

$conn->close();

header(
    "Location: ../pages/register.html?error="
    . urlencode($errorMessage)
);

exit;

?>