<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION["name"] ?? "User";
$role = $_SESSION["role"] ?? "student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Student Attendance System</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">

    <div class="logo">Attendance System</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="dashboard-container">

    <h1>Welcome, <?= htmlspecialchars($name) ?>!</h1>

    <p>
        Your role:
        <strong><?= htmlspecialchars(ucfirst($role)) ?></strong>
    </p>

    <div class="dashboard-cards">

        <?php if ($role === "admin" || $role === "teacher"): ?>

            <div class="dashboard-card">
                <h3>Student Management</h3>
                <p>
                    <a href="students.php">Manage Students</a>
                </p>
            </div>

            <div class="dashboard-card">
                <h3>Attendance</h3>
                <p>
                    <a href="attendance.php">Manage Attendance</a>
                </p>
            </div>

            <div class="dashboard-card">
                <h3>Reports</h3>
                <p>
                    <a href="reports.php">View Reports</a>
                </p>
            </div>

        <?php else: ?>

            <div class="dashboard-card">
                <h3>My Attendance</h3>
                <p>
                    <a href="reports.php">View My Report</a>
                </p>
            </div>

        <?php endif; ?>

    </div>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Student Attendance Management System</p>
</footer>

</body>
</html>