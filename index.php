
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Management System</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">Student Attendance System</div>

        <nav>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Student Attendance Management System</h1>

            <p>
                Manage student attendance easily.
                View attendance records and reports
                in one place.
            </p>

            <a href="login.php" class="btn">Login Now</a>
        </div>
    </section>

    <section class="features">
        <h2>Our Features</h2>

        <div class="feature-container">

            <div class="feature-card">
                <h3>Student Management</h3>
                <p>Manage student information and records.</p>
            </div>

            <div class="feature-card">
                <h3>Attendance Tracking</h3>
                <p>Record daily student attendance.</p>
            </div>

            <div class="feature-card">
                <h3>Attendance Reports</h3>
                <p>View attendance history and percentage.</p>
            </div>

        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date("Y"); ?>
        Student Attendance Management System</p>
    </footer>

    <script src="js/script.js"></script>

</body>
</html>