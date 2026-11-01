<?php
session_start();
require_once "db.php";

// Only admin and teacher can access this page
if (
    !isset($_SESSION["user_id"]) ||
    !in_array($_SESSION["role"] ?? "", ["admin", "teacher"], true)
) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Add a new student
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (($_SESSION["role"] ?? "") !== "admin") {
        $error = "Only an admin can add students.";
    } else {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $roll_no = trim($_POST["roll_no"] ?? "");
        $course = trim($_POST["course"] ?? "");
        $semester = (int) ($_POST["semester"] ?? 0);
        $phone = trim($_POST["phone"] ?? "");

        if (
            $name === "" ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($password) < 8 ||
            $roll_no === "" ||
            $course === "" ||
            $semester < 1 ||
            $semester > 12
        ) {
            $error = "Please enter valid details. Password must be at least 8 characters.";
        } else {

            $conn->begin_transaction();

            try {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Create student login account
                $sql = "INSERT INTO users
                        (name, email, password, role)
                        VALUES (?, ?, ?, 'student')";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sss",
                    $name,
                    $email,
                    $hashedPassword
                );
                $stmt->execute();

                $user_id = $conn->insert_id;
                $stmt->close();

                // Save student information
                $sql = "INSERT INTO students
                        (user_id, roll_no, course, semester, phone)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "issis",
                    $user_id,
                    $roll_no,
                    $course,
                    $semester,
                    $phone
                );
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                $message = "Student added successfully!";

            } catch (mysqli_sql_exception $e) {
                $conn->rollback();

                if ($e->getCode() === 1062) {
                    $error = "This email or roll number already exists.";
                } else {
                    $error = "Unable to add student. Please check the details and database tables.";
                }
            }
        }
    }
}

// Fetch student list
$sql = "SELECT
            s.student_id,
            s.roll_no,
            s.course,
            s.semester,
            s.phone,
            u.name,
            u.email
        FROM students s
        INNER JOIN users u ON s.user_id = u.user_id
        ORDER BY s.student_id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">Student Attendance System</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="page-container">

    <h1>Student Management</h1>

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

    <?php if (($_SESSION["role"] ?? "") === "admin"): ?>

    <section class="form-container">
        <h2>Add New Student</h2>

        <form method="POST" action="students.php">

            <div class="form-group">
                <label for="name">Student Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Student Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Student Login Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-group">
                <label for="roll_no">Roll Number</label>
                <input type="text" id="roll_no" name="roll_no" required>
            </div>

            <div class="form-group">
                <label for="course">Course</label>
                <select id="course" name="course" required>
                    <option value="">Select Course</option>
                    <option value="BCA">BCA</option>
                    <option value="BBA">BBA</option>
                    <option value="BTech">BTech</option>
                    <option value="MCA">MCA</option>
                </select>
            </div>

            <div class="form-group">
                <label for="semester">Semester</label>
                <select id="semester" name="semester" required>
                    <option value="">Select Semester</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>">
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="15"
                >
            </div>

            <button type="submit">Add Student</button>

        </form>
    </section>

    <?php endif; ?>

    <h2>Student List</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roll Number</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Phone</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($student = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int) $student["student_id"] ?></td>
                            <td><?= htmlspecialchars($student["name"]) ?></td>
                            <td><?= htmlspecialchars($student["email"]) ?></td>
                            <td><?= htmlspecialchars($student["roll_no"]) ?></td>
                            <td><?= htmlspecialchars($student["course"]) ?></td>
                            <td><?= (int) $student["semester"] ?></td>
                            <td><?= htmlspecialchars($student["phone"] ?? "") ?></td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7">No students added yet.</td>
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