
<?php
session_start();
require_once "db.php";

// Check login and role
if (
    !isset($_SESSION["user_id"]) ||
    !in_array($_SESSION["role"] ?? "", ["admin", "teacher"], true)
) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Get selected semester
$selected_semester = (int) (
    $_POST["semester"] ?? $_GET["semester"] ?? 0
);

if ($selected_semester < 1 || $selected_semester > 6) {
    $selected_semester = 0;
}

// Get selected subject and date
$selected_subject = (int) ($_POST["subject_id"] ?? 0);
$selected_date = $_POST["attendance_date"] ?? date("Y-m-d");

// Validate date
$date_object = DateTime::createFromFormat(
    "!Y-m-d",
    $selected_date
);

$valid_date = $date_object &&
    $date_object->format("Y-m-d") === $selected_date;

// Save attendance
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $attendance_data = $_POST["attendance"] ?? [];

    if ($selected_semester < 1 || $selected_semester > 6) {
        $error = "Please select a valid semester.";

    } elseif ($selected_subject <= 0 || !$valid_date) {
        $error = "Please select a valid subject and date.";

    } elseif (!is_array($attendance_data) || empty($attendance_data)) {
        $error = "Please mark attendance for students.";

    } else {

        // Verify subject belongs to selected semester
        $check = $conn->prepare(
            "SELECT subject_id
             FROM subjects
             WHERE subject_id = ? AND semester = ?"
        );

        $check->bind_param(
            "ii",
            $selected_subject,
            $selected_semester
        );

        $check->execute();
        $subject_result = $check->get_result();
        $check->close();

        if ($subject_result->num_rows !== 1) {
            $error = "Selected subject does not belong to this semester.";

        } else {

            $valid = true;

            $student_check = $conn->prepare(
                "SELECT student_id
                 FROM students
                 WHERE student_id = ? AND semester = ?"
            );

            foreach ($attendance_data as $student_id => $status) {

                $student_id = filter_var(
                    $student_id,
                    FILTER_VALIDATE_INT
                );

                if (
                    !$student_id ||
                    !in_array($status, ["Present", "Absent"], true)
                ) {
                    $valid = false;
                    break;
                }

                $student_check->bind_param(
                    "ii",
                    $student_id,
                    $selected_semester
                );

                $student_check->execute();

                if ($student_check->get_result()->num_rows !== 1) {
                    $valid = false;
                    break;
                }
            }

            $student_check->close();

            if (!$valid) {
                $error = "Invalid student or attendance status.";

            } else {

                try {
                    $conn->begin_transaction();

                    $stmt = $conn->prepare(
                        "INSERT INTO attendance
                         (student_id, subject_id, attendance_date, status)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                         status = VALUES(status)"
                    );

                    foreach ($attendance_data as $student_id => $status) {

                        $student_id = (int) $student_id;

                        $stmt->bind_param(
                            "iiss",
                            $student_id,
                            $selected_subject,
                            $selected_date,
                            $status
                        );

                        $stmt->execute();
                    }

                    $stmt->close();
                    $conn->commit();

                    $message = "Attendance saved successfully!";

                } catch (mysqli_sql_exception $e) {

                    $conn->rollback();

                    $error = "Could not save attendance. Please check your database structure.";
                }
            }
        }
    }
}

// Get subjects for selected semester
$subjects = null;

if ($selected_semester > 0) {

    $stmt = $conn->prepare(
        "SELECT subject_id, subject_name, subject_code
         FROM subjects
         WHERE semester = ?
         ORDER BY subject_code"
    );

    $stmt->bind_param("i", $selected_semester);
    $stmt->execute();

    $subjects = $stmt->get_result();
    $stmt->close();
}

// Get students for selected semester
$students = null;

if ($selected_semester > 0) {

    $stmt = $conn->prepare(
        "SELECT s.student_id, s.roll_no, u.name,
                s.course, s.semester
         FROM students s
         INNER JOIN users u ON s.user_id = u.user_id
         WHERE s.semester = ?
         ORDER BY s.roll_no"
    );

    $stmt->bind_param("i", $selected_semester);
    $stmt->execute();

    $students = $stmt->get_result();
    $stmt->close();
}

// Get existing attendance
$existing = [];

if (
    $selected_subject > 0 &&
    $selected_semester > 0 &&
    $valid_date
) {

    $stmt = $conn->prepare(
        "SELECT a.student_id, a.status
         FROM attendance a
         INNER JOIN subjects sub
         ON a.subject_id = sub.subject_id
         WHERE a.subject_id = ?
         AND sub.semester = ?
         AND a.attendance_date = ?"
    );

    $stmt->bind_param(
        "iis",
        $selected_subject,
        $selected_semester,
        $selected_date
    );

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $existing[$row["student_id"]] = $row["status"];
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Attendance</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header class="navbar">

    <div class="logo">
        Student Attendance System
    </div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="page-container">

    <h1>Manage Student Attendance</h1>

    <?php if ($message !== ""): ?>
        <div class="success-message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Semester Selection Form -->
    <form method="GET" action="attendance.php">

        <div class="form-group">

            <label for="semester">
                Select Semester
            </label>

            <select
                id="semester"
                name="semester"
                required
                onchange="this.form.submit()"
            >

                <option value="">Choose Semester</option>

                <?php for ($i = 1; $i <= 6; $i++): ?>

                    <option
                        value="<?= $i ?>"
                        <?= $selected_semester === $i ? "selected" : "" ?>
                    >
                        Semester <?= $i ?>
                    </option>

                <?php endfor; ?>

            </select>

        </div>

        <noscript>
            <button type="submit">Show Subjects</button>
        </noscript>

    </form>

    <?php if ($selected_semester > 0): ?>

        <h2>
            Semester <?= $selected_semester ?> Attendance
        </h2>

        <?php if ($subjects && $subjects->num_rows === 0): ?>

            <p>No subjects found for this semester.</p>

        <?php elseif ($students && $students->num_rows === 0): ?>

            <p>No students found for this semester.</p>

        <?php else: ?>

            <!-- Attendance Form -->
            <form method="POST" action="attendance.php">

                <input
                    type="hidden"
                    name="semester"
                    value="<?= $selected_semester ?>"
                >

                <div class="form-group">

                    <label for="subject_id">
                        Select Subject
                    </label>

                    <select
                        id="subject_id"
                        name="subject_id"
                        required
                    >

                        <option value="">Choose Subject</option>

                        <?php while ($subject = $subjects->fetch_assoc()): ?>

                            <option
                                value="<?= (int) $subject["subject_id"] ?>"
                                <?= $selected_subject === (int) $subject["subject_id"] ? "selected" : "" ?>
                            >

                                <?= htmlspecialchars($subject["subject_name"]) ?>

                                (<?= htmlspecialchars($subject["subject_code"]) ?>)

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="attendance_date">
                        Attendance Date
                    </label>

                    <input
                        type="date"
                        id="attendance_date"
                        name="attendance_date"
                        value="<?= htmlspecialchars($selected_date) ?>"
                        required
                    >

                </div>

                <h2>Student List</h2>

                <div class="table-container">

                    <table>

                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Semester</th>
                                <th>Attendance</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php while ($student = $students->fetch_assoc()): ?>

                            <?php
                            $sid = (int) $student["student_id"];

                            $status = $existing[$sid] ?? "Present";

                            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                                $posted_status =
                                    $_POST["attendance"][$sid] ?? null;

                                if (in_array(
                                    $posted_status,
                                    ["Present", "Absent"],
                                    true
                                )) {
                                    $status = $posted_status;
                                }
                            }
                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($student["roll_no"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student["name"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($student["course"]) ?>
                                </td>

                                <td>
                                    <?= (int) $student["semester"] ?>
                                </td>

                                <td>

                                    <select
                                        name="attendance[<?= $sid ?>]"
                                        required
                                    >

                                        <option
                                            value="Present"
                                            <?= $status === "Present" ? "selected" : "" ?>
                                        >
                                            Present
                                        </option>

                                        <option
                                            value="Absent"
                                            <?= $status === "Absent" ? "selected" : "" ?>
                                        >
                                            Absent
                                        </option>

                                    </select>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <button type="submit">
                    Save Attendance
                </button>

            </form>

        <?php endif; ?>

    <?php else: ?>

        <p>Please select a semester to view its subjects and students.</p>

    <?php endif; ?>

</main>

<footer>
    <p>
        &copy; <?= date("Y") ?>
        Student Attendance Management System
    </p>
</footer>

</body>
</html>
