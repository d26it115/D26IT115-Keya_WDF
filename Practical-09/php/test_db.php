<?php

require_once "db.php";

$sql = "SELECT * FROM students WHERE email = :email";

$stmt = $pdo->prepare($sql);

$email = "hardi@gmail.com";

$stmt->execute([
    ":email" => $email
]);

$student = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Database Test</title>
</head>

<body>

<h1>StudentHub Database Test</h1>

<?php if ($student): ?>

    <h2>Student Found</h2>

    <p>
        <strong>Name:</strong>
        <?= htmlspecialchars($student["name"]) ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?= htmlspecialchars($student["email"]) ?>
    </p>

    <p>
        <strong>Mobile:</strong>
        <?= htmlspecialchars($student["mobile"]) ?>
    </p>

    <p>
        <strong>Course:</strong>
        <?= htmlspecialchars($student["course"]) ?>
    </p>

<?php else: ?>

    <p>Student not found.</p>

<?php endif; ?>

</body>

</html>