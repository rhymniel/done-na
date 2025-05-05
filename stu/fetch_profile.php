<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '', 'enrollment_db');

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Ensure user ID exists in session
$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    die("<h2>Profile</h2><p>Session expired. Please log in again.</p>");
}

// Simplified query - only using students table
$query = "SELECT * FROM students WHERE student_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    die("<h2>Profile</h2><p>No student data found.</p>");
}

// Handle missing fields gracefully
$contact = !empty($row['contact']) ? htmlspecialchars($row['contact']) : 'N/A';
$email = !empty($row['email']) ? htmlspecialchars($row['email']) : 'N/A';
$address = !empty($row['address']) ? htmlspecialchars($row['address']) : 'N/A';
$street = !empty($row['street']) ? htmlspecialchars($row['street']) : 'N/A';
$city = !empty($row['city']) ? htmlspecialchars($row['city']) : 'N/A';
$province = !empty($row['province']) ? htmlspecialchars($row['province']) : 'N/A';
$postal = !empty($row['postal']) ? htmlspecialchars($row['postal']) : 'N/A';

// Rest of your code remains the same...
// Get Emergency Contact Details
$contactQuery = "SELECT contact_type, first_name, middle_name, last_name, contact 
                 FROM emergency_contacts WHERE student_id = ?";

$stmt = $conn->prepare($contactQuery);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$contactResult = $stmt->get_result();
$contacts = [];

while ($contactRow = $contactResult->fetch_assoc()) {
    $contacts[$contactRow['contact_type']] = $contactRow;
}
$stmt->close();


// Start HTML Output
echo "<div class='container'>";
echo "<div class='profile-card'>";
echo "<h2 class='section-title'><img src='/images/images.png' alt='Profile Icon' class='icon'> Profile Information</h2>";

// **Profile Details**
echo "<div class='profile-container'>";
echo "<div class='profile-column'>";
echo "<p><span>Name:</span> " . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['middle_name']) . " " . htmlspecialchars($row['last_name']) . "</p>";
echo "<p><span>Student ID:</span> " . htmlspecialchars($row['student_id']) . "</p>";
echo "<p><span>Admission Type:</span> " . htmlspecialchars($row['admission_type']) . "</p>";
echo "<p><span>Year Level:</span> " . (isset($row['year_level']) ? htmlspecialchars($row['year_level']) : 'N/A') . " Year</p>";
echo "</div>";

echo "<div class='profile-column'>";
echo "<p><span>Gender:</span> " . htmlspecialchars($row['gender']) . "</p>";
echo "<p><span>Civil Status:</span> " . htmlspecialchars($row['civil_status']) . "</p>";
echo "<p><span>Religion:</span> " . htmlspecialchars($row['religion']) . "</p>";
echo "<p><span>Birthday:</span> " . htmlspecialchars($row['birthday']) . "</p>";
echo "</div>";

echo "<div class='profile-column'>";
echo "<p><span>Working Student:</span> " . htmlspecialchars($row['working_student']) . "</p>";
echo "<p><span>Contact:</span> $contact</p>";
echo "<p><span>Email:</span> $email</p>";
echo "<p><span>Address:</span> $address, $street, $city, $province, $postal</p>";
echo "</div>";
echo "</div>";

// **Family Information**
echo "<h3 class='section-title'>Family Information</h3>";
echo "<div class='family-info'>";

$familyRoles = ['father', 'mother', 'guardian'];
foreach ($familyRoles as $role) {
    if (isset($contacts[$role])) {
        echo "<p><span>" . ucfirst($role) . "'s Name:</span> " . htmlspecialchars($contacts[$role]['first_name']) . " " . htmlspecialchars($contacts[$role]['middle_name']) . " " . htmlspecialchars($contacts[$role]['last_name']) . "</p>";
        echo "<p><span>" . ucfirst($role) . "'s Contact:</span> " . htmlspecialchars($contacts[$role]['contact']) . "</p>";
    } else {
        echo "<p><span>" . ucfirst($role) . "'s Name:</span> N/A</p>";
        echo "<p><span>" . ucfirst($role) . "'s Contact:</span> N/A</p>";
    }
}

echo "</div></div></div>";

$conn->close();
?>