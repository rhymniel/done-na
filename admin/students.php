<?php
session_start();
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_POST['new_password'])) {
    if (updateStudentPassword($_POST['student_id'], $_POST['new_password'])) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Password updated successfully'
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Failed to update password'
        ];
    }
    header("Location: students.php");
    exit();
}

$students = getStudents();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li class="active"><a href="students.php">Student Management</a></li>
            <li><a href="pending.php">Pending Requests</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <h1>Student Management</h1>
            <div class="user-info">
                <span>Admin</span>
                <a class="logout" href="/home/home.html">Logout</a>
            </div>
        </header>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert <?= $_SESSION['flash_message']['type'] ?>">
                <?= $_SESSION['flash_message']['message'] ?>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <div class="students-table">
            <h2>Student Accounts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Full Name</th>
                        <th>Year Level</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                        <td><?= htmlspecialchars($student['username'] ?? 'N/A') ?></td>
                        <td>••••••••</td>
                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        <td><?= htmlspecialchars($student['year_level']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td>
                            <button class="change-password" data-id="<?= $student['student_id'] ?>">Change Password</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Change Password</h2>
            <form id="passwordForm" method="POST">
                <input type="hidden" id="studentId" name="student_id">
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>