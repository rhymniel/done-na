<?php
$conn = new mysqli('localhost', 'root', '', 'enrollment_db');

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// Helper function for secure input retrieval
function getPost($key) {
    return filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING) ?: null;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(["status" => "error", "message" => "Error: Invalid request method."]));
}

// Required fields validation
$requiredFields = ['email', 'lastName', 'firstName', 'yrlvl', 'birthday', 'contact', 'address', 'street', 'city', 'province'];
foreach ($requiredFields as $field) {
    if (empty(getPost($field))) {
        die(json_encode(["status" => "error", "message" => "Error: Missing required field '$field'."]));
    }
}

// Map year level
$yearMap = ['1st year' => 1, '2nd year' => 2, '3rd year' => 3, '4th year' => 4];
$yearLevel = $yearMap[getPost('yrlvl')] ?? null;

if (!$yearLevel) {
    die(json_encode(["status" => "error", "message" => "Error: Invalid Year Level Selection."]));
}

// Retrieve Form Data
$admissionType  = getPost('admissionType') ?: 'General';
$lastName       = getPost('lastName');
$firstName      = getPost('firstName');
$middleName     = getPost('middleName') ?: '';
$gender         = getPost('gender') ?: 'Not Specified';
$civilStatus    = getPost('civilStatus') ?: 'Single';
$religion       = getPost('religion') ?: 'Not Specified';
$birthday       = getPost('birthday');
$contact        = getPost('contact');
$email          = getPost('email');
$address        = getPost('address');
$street         = getPost('street');
$city           = getPost('city');
$province       = getPost('province');
$postal         = getPost('postal') ?: '';
$workingStudent = getPost('workingStudent') ?: 'no';

// Begin transaction
$conn->begin_transaction();

try {
    // Insert into pending_enrollments
    $stmt = $conn->prepare("
        INSERT INTO pending_enrollments (
            admission_type, last_name, first_name, middle_name, working_student, gender,
            civil_status, religion, year_level, birthday, contact, email,
            address, street, city, province, postal, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $status = 'pending';

    $stmt->bind_param("ssssssssisssssssss",
        $admissionType, $lastName, $firstName, $middleName, $workingStudent, $gender,
        $civilStatus, $religion, $yearLevel, $birthday, $contact, $email,
        $address, $street, $city, $province, $postal, $status
    );

    if (!$stmt->execute()) {
        throw new Exception("Error: Enrollment submission failed - " . $stmt->error);
    }

    $studentID = $conn->insert_id;
    $stmt->close();

    // Insert Emergency Contacts (Only if provided)
    $emergencyContacts = [
        ["father", getPost('fatherLastName'), getPost('fatherFirstName'), getPost('fatherMiddleName'), getPost('fatherContact')],
        ["mother", getPost('motherLastName'), getPost('motherFirstName'), getPost('motherMiddleName'), getPost('motherContact')],
        ["guardian", getPost('guardianLastName'), getPost('guardianFirstName'), getPost('guardianMiddleName'), getPost('guardianContact')]
    ];

    $stmt = $conn->prepare("
        INSERT INTO emergency_contacts (student_id, contact_type, last_name, first_name, middle_name, contact) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($emergencyContacts as $ec) {
        list($type, $ecLastName, $ecFirstName, $ecMiddleName, $ecContact) = $ec;

        if (!empty($ecLastName) && !empty($ecFirstName) && !empty($ecContact)) {
            $stmt->bind_param("isssss", $studentID, $type, $ecLastName, $ecFirstName, $ecMiddleName, $ecContact);
            if (!$stmt->execute()) {
                throw new Exception("Error: Emergency contact submission failed - " . $stmt->error);
            }
        }
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Redirect to home/home.html after successful enrollment
    header("Location: ../home/home.html");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    // Redirect back to enroll.html with error message (optional)
    header("Location: enroll.html?error=" . urlencode($e->getMessage()));
    exit();
}

$conn->close();
?>
