<?php
session_start();
require_once __DIR__ . "/db_mysqli.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function escape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function redirectWithMessage(string $message, string $type = "success"): never
{
    header("Location: student_management.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit;
}

$allowedCourses = ["B.Tech", "BCA", "B.Sc", "B.Com", "MCA", "Other"];
$allowedYears = ["1st Year", "2nd Year", "3rd Year", "4th Year", "Fourth Year", "Final Year", "Other"];
$allowedGenders = ["Male", "Female", "Other"];

$message = $_GET["message"] ?? "";
$messageType = ($_GET["type"] ?? "success") === "error" ? "error" : "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "add" || $action === "update") {
            $studentId = filter_var($_POST["student_id"] ?? "", FILTER_VALIDATE_INT);
            $name = trim($_POST["name"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $mobile = trim($_POST["mobile"] ?? "");
            $course = trim($_POST["course"] ?? "");
            $year = trim($_POST["year"] ?? "");
            $gender = trim($_POST["gender"] ?? "");

            if ($name === "" || $email === "" || $mobile === "" || $course === "" || $year === "" || $gender === "") {
                throw new InvalidArgumentException("Please fill in all student details.");
            }
            if (mb_strlen($name) > 100) {
                throw new InvalidArgumentException("Name must not exceed 100 characters.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
                throw new InvalidArgumentException("Please enter a valid email address (maximum 100 characters).");
            }
            if (!preg_match('/^[0-9+()\-\s]{7,15}$/', $mobile)) {
                throw new InvalidArgumentException("Enter a valid mobile number (7–15 characters).");
            }
            if (!in_array($course, $allowedCourses, true)) {
                throw new InvalidArgumentException("Please select a valid course.");
            }
            if (!in_array($year, $allowedYears, true)) {
                throw new InvalidArgumentException("Please select a valid academic year.");
            }
            if (!in_array($gender, $allowedGenders, true)) {
                throw new InvalidArgumentException("Please select a valid gender.");
            }

            if ($action === "add") {
                $check = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
                $check->bind_param("s", $email);
            } else {
                if (!$studentId || $studentId < 1) {
                    throw new InvalidArgumentException("Invalid student ID.");
                }
                $check = $conn->prepare("SELECT student_id FROM students WHERE email = ? AND student_id <> ?");
                $check->bind_param("si", $email, $studentId);
            }
            $check->execute();
            $duplicateEmail = $check->get_result()->num_rows > 0;
            $check->close();
            if ($duplicateEmail) {
                throw new InvalidArgumentException("That email address is already used by another student.");
            }

            if ($action === "add") {
                // Password is blank because this CRUD form manages student details, not login credentials.
                $password = "";
                $stmt = $conn->prepare("INSERT INTO students (name, email, mobile, course, year, gender, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $name, $email, $mobile, $course, $year, $gender, $password);
                $stmt->execute();
                $stmt->close();
                redirectWithMessage("Student added successfully.");
            }

            $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ? WHERE student_id = ?");
            $stmt->bind_param("ssssssi", $name, $email, $mobile, $course, $year, $gender, $studentId);
            $stmt->execute();
            $changed = $stmt->affected_rows;
            $stmt->close();
            redirectWithMessage($changed >= 0 ? "Student details updated successfully." : "No changes were made.");
        }

        if ($action === "delete") {
            $studentId = filter_var($_POST["student_id"] ?? "", FILTER_VALIDATE_INT);
            if (!$studentId || $studentId < 1) {
                throw new InvalidArgumentException("Invalid student ID.");
            }
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
            redirectWithMessage($deleted > 0 ? "Student deleted successfully." : "Student record was not found.", $deleted > 0 ? "success" : "error");
        }

        throw new InvalidArgumentException("Invalid operation requested.");
    } catch (InvalidArgumentException $e) {
        redirectWithMessage($e->getMessage(), "error");
    } catch (mysqli_sql_exception $e) {
        error_log("Student management database error: " . $e->getMessage());
        $msg = $e->getCode() === 1062 ? "That email address is already in use." : "The database operation failed. Check the database structure and try again.";
        redirectWithMessage($msg, "error");
    }
}

$editStudent = null;
$editId = filter_var($_GET["edit"] ?? "", FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $stmt = $conn->prepare("SELECT student_id, name, email, mobile, course, year, gender FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editStudent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editStudent) {
        $message = "Student record was not found.";
        $messageType = "error";
    }
}

$search = trim($_GET["search"] ?? "");
$filterCourse = trim($_GET["course"] ?? "");
if (!in_array($filterCourse, $allowedCourses, true)) {
    $filterCourse = "";
}

$sql = "SELECT student_id, name, email, mobile, course, year, gender FROM students WHERE 1=1";
$params = [];
$types = "";
if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR course LIKE ?)";
    $term = "%" . $search . "%";
    array_push($params, $term, $term, $term);
    $types .= "sss";
}
if ($filterCourse !== "") {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
    $types .= "s";
}
$sql .= " ORDER BY student_id DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();
$totalStudents = $students->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | StudentHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .student-management{max-width:1200px;margin:32px auto;padding:0 20px}.sm-heading{margin-bottom:22px}.sm-card{background:var(--card-bg,#fff);color:var(--text,#222);padding:24px;margin:20px 0;border-radius:12px;box-shadow:0 3px 14px #00000012}.sm-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.sm-field{display:flex;flex-direction:column;gap:7px}.sm-field label{font-weight:600}.sm-field input,.sm-field select,.sm-search input,.sm-search select{width:100%;box-sizing:border-box;padding:11px;border:1px solid #cbd5e1;border-radius:6px;font:inherit}.sm-actions{grid-column:1/-1;display:flex;gap:10px;flex-wrap:wrap}.sm-btn{display:inline-block;padding:9px 14px;border:0;border-radius:6px;cursor:pointer;text-decoration:none;font:inherit;font-size:14px;text-align:center}.sm-primary{background:#2563eb;color:white}.sm-secondary{background:#64748b;color:white}.sm-edit{background:#0f766e;color:white}.sm-delete{background:#dc2626;color:white}.sm-search{display:grid;grid-template-columns:minmax(180px,2fr) minmax(150px,1fr) auto auto;gap:10px;margin:18px 0}.sm-table-wrap{overflow-x:auto}.sm-table{width:100%;min-width:850px;border-collapse:collapse}.sm-table th,.sm-table td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0}.sm-table th{background:#eff6ff;color:#1e3a8a}.sm-inline-actions{display:flex;align-items:center;gap:7px}.sm-inline-actions form{margin:0}.sm-message{padding:13px 16px;border-radius:7px;margin:18px 0}.sm-success{background:#dcfce7;color:#166534}.sm-error{background:#fee2e2;color:#991b1b}.sm-empty{text-align:center;padding:25px}.sm-back{display:inline-block;margin-bottom:12px}@media(max-width:700px){.sm-form,.sm-search{grid-template-columns:1fr}.sm-card{padding:16px}}
    </style>
</head>
<body>
<header>
    <nav class="navbar" aria-label="Main navigation">
        <a href="../pages/index.html" class="logo-link">
            <img src="../images/logo.png" alt="StudentHub Logo" class="logo-image">
            <span class="logo-text">StudentHub</span>
        </a>
        <button id="menuButton" class="hamburger" type="button" aria-expanded="false" aria-label="Open navigation menu">☰</button>
        <ul id="navigationMenu" class="navigation-menu">
            <li><a href="../pages/index.html">Home</a></li>
            <li><a href="../pages/admin.html">Admin Panel</a></li>
            <li><a href="../pages/events.html">Events</a></li>
        </ul>
    </nav>
</header>
<main class="student-management">
    <a class="sm-back" href="../pages/admin.html">&larr; Back to Admin Panel</a>
    <div class="sm-heading"><h1>Student Management</h1><p>Add, view, edit, delete, search and filter student records.</p></div>

    <?php if ($message !== ""): ?>
        <div class="sm-message <?= $messageType === "error" ? "sm-error" : "sm-success" ?>" role="status"><?= escape($message) ?></div>
    <?php endif; ?>

    <section class="sm-card">
        <h2><?= $editStudent ? "Edit Student" : "Add New Student" ?></h2>
        <form method="post" action="student_management.php" class="sm-form">
            <input type="hidden" name="action" value="<?= $editStudent ? "update" : "add" ?>">
            <?php if ($editStudent): ?><input type="hidden" name="student_id" value="<?= (int)$editStudent["student_id"] ?>"><?php endif; ?>
            <div class="sm-field"><label for="name">Full Name</label><input id="name" name="name" maxlength="100" required value="<?= escape($editStudent["name"] ?? "") ?>"></div>
            <div class="sm-field"><label for="email">Email Address</label><input type="email" id="email" name="email" maxlength="100" required value="<?= escape($editStudent["email"] ?? "") ?>"></div>
            <div class="sm-field"><label for="mobile">Mobile Number</label><input type="tel" id="mobile" name="mobile" maxlength="15" required value="<?= escape($editStudent["mobile"] ?? "") ?>"></div>
            <div class="sm-field"><label for="course">Course</label><select id="course" name="course" required><option value="">Select Course</option><?php foreach ($allowedCourses as $option): ?><option value="<?= escape($option) ?>" <?= ($editStudent["course"] ?? "") === $option ? "selected" : "" ?>><?= escape($option) ?></option><?php endforeach; ?></select></div>
            <div class="sm-field"><label for="year">Academic Year</label><select id="year" name="year" required><option value="">Select Year</option><?php foreach ($allowedYears as $option): ?><option value="<?= escape($option) ?>" <?= ($editStudent["year"] ?? "") === $option ? "selected" : "" ?>><?= escape($option) ?></option><?php endforeach; ?></select></div>
            <div class="sm-field"><label for="gender">Gender</label><select id="gender" name="gender" required><option value="">Select Gender</option><?php foreach ($allowedGenders as $option): ?><option value="<?= escape($option) ?>" <?= ($editStudent["gender"] ?? "") === $option ? "selected" : "" ?>><?= escape($option) ?></option><?php endforeach; ?></select></div>
            <div class="sm-actions"><button class="sm-btn sm-primary" type="submit"><?= $editStudent ? "Update Student" : "Add Student" ?></button><?php if ($editStudent): ?><a class="sm-btn sm-secondary" href="student_management.php">Cancel Edit</a><?php endif; ?></div>
        </form>
    </section>

    <section class="sm-card">
        <h2>Student Records</h2><p>Showing <strong><?= $totalStudents ?></strong> matching record(s).</p>
        <form class="sm-search" method="get" action="student_management.php">
            <input type="search" name="search" aria-label="Search students" placeholder="Search by name, email or course" value="<?= escape($search) ?>">
            <select name="course" aria-label="Filter by course"><option value="">All Courses</option><?php foreach ($allowedCourses as $option): ?><option value="<?= escape($option) ?>" <?= $filterCourse === $option ? "selected" : "" ?>><?= escape($option) ?></option><?php endforeach; ?></select>
            <button class="sm-btn sm-primary" type="submit">Search / Filter</button><a class="sm-btn sm-secondary" href="student_management.php">Reset</a>
        </form>
        <?php if ($totalStudents === 0): ?><div class="sm-empty">No student records found. Try changing your search or filter.</div><?php else: ?>
        <div class="sm-table-wrap"><table class="sm-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th><th>Year</th><th>Gender</th><th>Actions</th></tr></thead><tbody>
        <?php while ($student = $students->fetch_assoc()): ?><tr><td><?= (int)$student["student_id"] ?></td><td><?= escape($student["name"]) ?></td><td><?= escape($student["email"]) ?></td><td><?= escape($student["mobile"]) ?></td><td><?= escape($student["course"]) ?></td><td><?= escape($student["year"]) ?></td><td><?= escape($student["gender"]) ?></td><td><div class="sm-inline-actions"><a class="sm-btn sm-edit" href="student_management.php?edit=<?= (int)$student["student_id"] ?>">Edit</a><form method="post" action="student_management.php" onsubmit="return confirm('Are you sure you want to delete this student? This action cannot be undone.');"><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= (int)$student["student_id"] ?>"><button class="sm-btn sm-delete" type="submit">Delete</button></form></div></td></tr><?php endwhile; ?>
        </tbody></table></div><?php endif; ?>
    </section>
</main>
<footer><p>&copy; 2026 StudentHub. All rights reserved.</p></footer>
<script src="../js/script.js"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
