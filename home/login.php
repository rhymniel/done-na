<?php
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'enrollment_db');

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("An error occurred. Please try again later.");
}

// Retrieve and sanitize inputs
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Input Validation
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Username and Password are required.';
    header('Location: /admin/login.html');
    exit();
}

// Initialize login attempts tracking
if (!isset($_SESSION['attempts'][$username])) {
    $_SESSION['attempts'][$username] = 0;
}

// Lockout mechanism (5 attempts = 1-minute cooldown)
$max_attempts = 5;
$lockout_duration = 60; // seconds

if ($_SESSION['attempts'][$username] >= $max_attempts) {
    $lockout_time = $_SESSION['lockout'][$username] ?? 0;
    if (time() - $lockout_time < $lockout_duration) {
        $_SESSION['login_error'] = 'Too many failed attempts. Try again after 1 minute.';
        header('Location: /home/home.html');
        exit();
    } else {
        // Cooldown is over, reset attempts
        $_SESSION['attempts'][$username] = 0;
        unset($_SESSION['lockout'][$username]);
    }
}

// Determine login type and query
$query = "";
$redirect = "";
$user_type = "";

// Updated username pattern to match s10001 format (5 digits total)
if (preg_match('/^s\d{5}$/', $username)) {
    // Student login - format s10001, s20001, etc.
    $query = "SELECT student_id AS user_id, password_hash FROM student_logins WHERE username = ?";
    $redirect = "/stu/stud.html";
    $user_type = "student";
} elseif ($username === 'admin') {
    // Admin login
    $query = "SELECT admin_id AS user_id, password_hash FROM admin WHERE username = ?";
    $redirect = "/admin/admin.html";
    $user_type = "admin";
} else {
    $_SESSION['login_error'] = 'Invalid username format. Student usernames start with "s" followed by 5 digits.';
    header('Location: /home/home.html');
    exit();
}

// Prepare and execute login query
$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Database prepare failed: " . $conn->error);
    $_SESSION['login_error'] = 'An internal error occurred. Please try again later.';
    header('Location: /home/home.html');
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $storedPasswordHash = $row['password_hash'];

    // Verify password (make sure passwords are stored using password_hash())
    if (password_verify($password, $storedPasswordHash)) {
        // Login successful
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_type'] = $user_type;

        if ($user_type === 'admin') {
            $_SESSION['admin_logged_in'] = true;
        }

        // Reset attempts on successful login
        unset($_SESSION['attempts'][$username]);
        unset($_SESSION['lockout'][$username]);

        header("Location: $redirect");
        exit();
    }
}

// Failed login attempt handling
$_SESSION['attempts'][$username]++;
if ($_SESSION['attempts'][$username] >= $max_attempts) {
    $_SESSION['lockout'][$username] = time();
    $_SESSION['login_error'] = 'Too many failed attempts. Try again in 1 minute.';
} else {
    $_SESSION['login_error'] = "Incorrect credentials. Attempt " . $_SESSION['attempts'][$username] . "/" . $max_attempts;
}

header('Location: /home/home.html');
exit();

$stmt->close();
$conn->close();
?>