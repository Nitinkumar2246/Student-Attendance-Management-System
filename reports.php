<?php
session_start();
require_once "db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION["role"] ?? "";
$user_id = (int) $_SESSION["user_id"];

$sql = "SELECT
            u.name,
            s.roll_no,
            s.course,
            s.semester,
            sub.subject_name,
            a.attendance_date,
            a.status
        FROM attendance a
        INNER JOIN students s
            ON a.student_id = s.student_id
        INNER JOIN users u
            ON s.user_id = u.user_id
        INNER JOIN subjects sub
            ON a.subject_id = sub.subject_id";

if ($role === "student") {
    $sql .= " WHERE s.user_id = ?";
}

$sql .= " ORDER BY a.attendance_date DESC, u.name";

if ($role === "student") {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Calculate attendance summary for current student
$summary = [];

if ($role === "student") {
    $summary_sql = "SELECT
                        sub.subject_name,
                        COUNT(*) AS total_classes,
                        SUM(a.status = 'Present') AS present_classes,
                        ROUND(
                            100.0 * SUM(a.status = 'Present') / COUNT(*),
                            2
                        ) AS percentage
                    FROM attendance a
                    INNER JOIN subjects sub
                        ON a.subject_id = sub.subject_id
                    INNER JOIN students s
                        ON a.student_id = s.student_id
                    WHERE s.user_id = ?
                    GROUP BY sub.subject_id, sub.subject_name";

    $summary_stmt = $conn->prepare($summary_sql);
    $summary_stmt->bind_param("i", $user_id);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();

    while ($row = $summary_result->fetch_assoc()) {
        $summary[] = $row;
    }

    $summary_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">Student Attendance System</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>

        <?php if (in_array($role, ["admin", "teacher"], true)): ?>
            <a href="students.php">Students</a>
            <a href="attendance.php">Attendance</a>
        <?php endif; ?>

        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="page-container">

    <h1>Attendance Reports</h1>

    <?php if ($role === "student"): ?>

        <h2>My Attendance Summary</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Total Classes</th>
                        <th>Present</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($summary) > 0): ?>
                        <?php foreach ($summary as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item["subject_name"]) ?></td>
                                <td><?= (int) $item["total_classes"] ?></td>
                                <td><?= (int) $item["present_classes"] ?></td>
                                <td><?= htmlspecialchars((string) $item["percentage"]) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No attendance records found yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <h2>Attendance Records</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <?php if ($role !== "student"): ?>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Course</th>
                        <th>Semester</th>
                    <?php endif; ?>

                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php if ($role !== "student"): ?>
                                <td><?= htmlspecialchars($row["name"]) ?></td>
                                <td><?= htmlspecialchars($row["roll_no"]) ?></td>
                                <td><?= htmlspecialchars($row["course"]) ?></td>
                                <td><?= (int) $row["semester"] ?></td>
                            <?php endif; ?>

                            <td><?= htmlspecialchars($row["subject_name"]) ?></td>
                            <td><?= htmlspecialchars($row["attendance_date"]) ?></td>
                            <td>
                                <span class="status <?= strtolower($row["status"]) ?>">
                                    <?= htmlspecialchars($row["status"]) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="<?= $role === "student" ? 3 : 7 ?>">
                            No attendance records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Student Attendance Management System</p>
</footer>

</body>
</html>