<?php
require 'functions.php';
$filter = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['gender'])) $filter['gender'] = $_POST['gender'];
    if (isset($_POST['year_level'])) $filter['year_level'] = $_POST['year_level'];
}
$students = getStudents($filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li class="active"><a href="index.php">Dashboard</a></li>
            <li><a href="students.php">Student Management</a></li>
            <li><a href="pending.php">Pending Requests</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <h1>Dashboard</h1>
            <div class="user-info">
                <span>Admin</span>
                <a class="logout" href="/home/home.html">Logout</a>
            </div>
        </header>

        <div class="filter-section">
            <form method="POST">
                <select name="gender">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                
                <select name="year_level">
                    <option value="">All Year Levels</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
                
                <button type="submit">Filter</button>
            </form>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Students</h3>
                <p><?php echo count(getStudents()); ?></p>
            </div>
            <div class="stat-card">
                <h3>Male Students</h3>
                <p><?php echo count(getStudents(['gender' => 'male'])); ?></p>
            </div>
            <div class="stat-card">
                <h3>Female Students</h3>
                <p><?php echo count(getStudents(['gender' => 'female'])); ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Requests</h3>
                <p><?php echo count(getPendingRequests()); ?></p>
            </div>
        </div>

        <div class="students-table">
            <h2>Accepted Students</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Admission Type</th>
                        <th>Gender</th>
                        <th>Year Level</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['admission_type']); ?></td>
                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                        <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>