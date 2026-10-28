<?php
/**
 * StudentHub - Registration Processing
 * Validates student submission and appends to registrations CSV
 */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/register.html");
    exit;
}

// Retrieve and sanitize inputs
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$terms = trim($_POST["terms"] ?? "");

$errors = [];

// Name validation
if ($name === "") {
    $errors[] = "Name is required.";
} elseif (!preg_match("/^[A-Za-z ]{2,50}$/", $name)) {
    $errors[] = "Name must contain only letters and spaces (2-50 characters).";
}

// Email validation
if ($email === "") {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid email address.";
}

// Mobile validation
if ($mobile === "") {
    $errors[] = "Mobile number is required.";
} elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
    $errors[] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
}

// Password validation
$passwordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}$/";
if ($password === "") {
    $errors[] = "Password is required.";
} elseif (!preg_match($passwordRegex, $password)) {
    $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, numbers, and symbols.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Password and confirm password do not match.";
}

// Course validation
$allowedCourses = ["BCA", "BBA", "B.Sc", "B.Com", "B.E.", "B.Tech", "MCA"];
if ($course === "" || !in_array($course, $allowedCourses, true)) {
    $errors[] = "Please select a valid course.";
}

// Year validation
$allowedYears = ["First Year", "Second Year", "Third Year", "Fourth Year"];
if ($year === "" || !in_array($year, $allowedYears, true)) {
    $errors[] = "Please select a valid academic year.";
}

// Gender validation
$allowedGenders = ["Male", "Female", "Other"];
if ($gender === "" || !in_array($gender, $allowedGenders, true)) {
    $errors[] = "Please select a valid gender option.";
}

// Terms validation
if ($terms !== "accepted") {
    $errors[] = "You must accept the terms and conditions.";
}

// Handle validation errors
if (!empty($errors)) {
    header("Location: ../pages/register.html?error=1");
    exit;
}

// Target CSV data directory
$dataFolder = __DIR__ . "/../data";
if (!is_dir($dataFolder)) {
    mkdir($dataFolder, 0777, true);
}

$csvFile = $dataFolder . "/registrations.csv";
$fileExists = file_exists($csvFile);

$file = fopen($csvFile, "a");
if ($file === false) {
    header("Location: ../pages/register.html?error=1");
    exit;
}

// Add header row if new file
if (!$fileExists || filesize($csvFile) === 0) {
    fputcsv($file, [
        "Name",
        "Email",
        "Mobile",
        "Password",
        "Course",
        "Year",
        "Gender",
        "Terms"
    ]);
}

// Hash password for security
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Append student record
fputcsv($file, [
    $name,
    $email,
    $mobile,
    $hashedPassword,
    $course,
    $year,
    $gender,
    "Accepted"
]);

fclose($file);

// Redirect with success parameter
header("Location: ../pages/register.html?success=1");
exit;
?>