<?php
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'enrollment_db');

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Retrieve and sanitize inputs
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username)) {
    $_SESSION['login_error'] = 'Username is required';
    header('Location: /home/home.html');
    exit();
}

if (empty($password)) {
    $_SESSION['login_error'] = 'Password is required';
    header('Location: /home/home.html');
    exit();
}

// Initialize login attempts tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

// Lockout mechanism (5 attempts = 1-minute cooldown)
$max_attempts = 5;
$lockout_duration = 60; // seconds

// Check if user is locked out
if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_since_last_attempt = time() - $_SESSION['last_attempt_time'];
    
    if ($time_since_last_attempt < $lockout_duration) {
        $remaining_time = $lockout_duration - $time_since_last_attempt;
        $_SESSION['login_error'] = 'Too many failed attempts. Please try again in ' . $remaining_time . ' seconds.';
        header('Location: /home/home.html');
        exit();
    } else {
        // Reset attempts if lockout period has passed
        $_SESSION['login_attempts'] = 0;
    }
}

// Determine user type and prepare appropriate query
$user_type = '';
$query = '';
$redirect = '';

if (preg_match('/^s\d{5}$/', $username)) {
    // Student login - format s10001, s20001, etc.
    $query = "SELECT s.student_id, sl.password_hash 
              FROM students s
              JOIN student_logins sl ON s.student_id = sl.student_id
              WHERE sl.username = ? AND s.status = 'accepted'";
    $redirect = "/stu/stud.html";
    $user_type = "student";
} elseif ($username === 'admin') {
    // Admin login
    $query = "SELECT admin_id, password FROM admin WHERE username = ?";
    $redirect = "/admin/index.php";
    $user_type = "admin";
} else {
    die(json_encode([
        'status' => 'error',
        'message' => 'Invalid username format. Student usernames start with "s" followed by 5 digits.'
    ]));
}

// Prepare and execute login query
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Database prepare failed: " . $conn->error);
    die(json_encode([
        'status' => 'error',
        'message' => 'Database error. Please try again later.'
    ]));
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Verify password
    if ($user_type === 'admin') {
        // Admin passwords are stored in plain text, compare directly
        $passwordValid = ($password === $row['password']);
    } else {
        // For students, check if stored password is hashed (starts with $2y$ or $argon2)
        $passwordHash = $row['password_hash'];
        if (preg_match('/^\$2y\$|^\$argon2/', $passwordHash)) {
            $passwordValid = password_verify($password, $passwordHash);
        } else {
            // Assume plain text password
            $passwordValid = ($password === $passwordHash);
        }
    }
    if ($passwordValid) {
        // Login successful
        $_SESSION['user_id'] = $row[$user_type === 'student' ? 'student_id' : 'admin_id'];
        $_SESSION['user_type'] = $user_type;
        
        // Reset attempts on successful login
        $_SESSION['login_attempts'] = 0;
        
        // Set admin flag if admin
        if ($user_type === 'admin') {
            $_SESSION['admin_logged_in'] = true;
        }
        
        // Redirect to the appropriate page
        header("Location: $redirect");
        exit();
    }
}

$_SESSION['login_attempts']++;
$_SESSION['last_attempt_time'] = time();

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $_SESSION['login_error'] = 'Too many failed attempts. Please try again in 1 minute.';
} else {
    $remaining_attempts = $max_attempts - $_SESSION['login_attempts'];
    $_SESSION['login_error'] = "Incorrect credentials. You have $remaining_attempts attempts remaining.";
}

header('Location: /home/home.html');
exit();

$stmt->close();
$conn->close();
?>
