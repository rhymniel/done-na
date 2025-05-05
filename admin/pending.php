<?php
session_start();
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['accept'])) {
            if (empty($_POST['student_id'])) {
                throw new Exception("Student ID is required");
            }

            $result = acceptStudent($_POST['student_id']);
            
            if (is_array($result)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => sprintf(
                        "Student accepted successfully!<br>Student ID: %d<br>Username: %s<br>Temporary Password: %s",
                        $result['student_id'],
                        $result['username'],
                        $result['temp_password']
                    )
                ];
            } else {
                throw new Exception($result);
            }
            
        } elseif (isset($_POST['decline'])) {
            if (empty($_POST['student_id'])) {
                throw new Exception("Student ID is required");
            }

            $success = declineStudent($_POST['student_id']);
            
            if (!$success) {
                throw new Exception("Failed to decline student");
            }

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Student declined successfully'
            ];
        }

        header("Location: pending.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        header("Location: pending.php");
        exit();
    }
}

$pendingStudents = getPendingRequests();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="students.php">Student Management</a></li>
            <li class="active"><a href="pending.php">Pending Requests</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <h1>Pending Requests</h1>
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
            <h2>Pending Enrollment Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Admission Type</th>
                        <th>Gender</th>
                        <th>Year Level</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingStudents as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars(array_key_exists('id',$student) ? $student['id'] : '') ?></td>
                        <td><?= htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name']) )?></td>
                        <td><?= htmlspecialchars($student['admission_type']) ?></td>
                        <td><?= htmlspecialchars($student['gender']) ?></td>
                        <td><?= htmlspecialchars($student['year_level']) ?></td>
                        <td><?= htmlspecialchars($student['contact']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                <button type="submit" name="accept" class="accept">Accept</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                <button type="submit" name="decline" class="decline">Decline</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>