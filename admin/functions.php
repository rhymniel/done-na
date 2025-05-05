<?php
require 'config.php';

function generateUsername($student_id, $year_level) {
    static $year_counters = []; // Tracks sequential IDs per year
    
    if (!isset($year_counters[$year_level])) {
        $year_counters[$year_level] = 0; // Initialize counter for this year
    }
    
    $year_counters[$year_level]++; // Increment count
    
    return 's' . $year_level . str_pad($year_counters[$year_level], 4, '0', STR_PAD_LEFT);
}


function getStudents($filter = null) {
    global $pdo;
    $sql = "SELECT s.*, sl.username 
            FROM students s
            LEFT JOIN student_logins sl ON s.student_id = sl.student_id
            WHERE s.status = 'accepted'";
    
    if ($filter && is_array($filter)) {
        if (!empty($filter['gender'])) {
            $sql .= " AND s.gender = :gender";
        }
        if (!empty($filter['year_level'])) {
            $sql .= " AND s.year_level = :year_level";
        }
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($filter && is_array($filter)) {
        if (!empty($filter['gender'])) {
            $stmt->bindValue(':gender', $filter['gender']);
        }
        if (!empty($filter['year_level'])) {
            $stmt->bindValue(':year_level', $filter['year_level'], PDO::PARAM_INT);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPendingRequests() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM pending_enrollments WHERE status = 'pending'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function acceptStudent($pending_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Get pending student data
        $stmt = $pdo->prepare("SELECT * FROM pending_enrollments WHERE id = ?");
        $stmt->execute([$pending_id]);
        $pending_student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pending_student) {
            throw new Exception("Pending student not found");
        }

        // 2. Generate password as (lastname)8080
        $temp_password = generateRandomPassword($pending_student['last_name']);
        $password_hash = password_hash($temp_password, PASSWORD_BCRYPT);
        
        // 3. Insert into students table
        $stmt = $pdo->prepare("
            INSERT INTO students (
                admission_type, last_name, first_name, middle_name, working_student,
                gender, civil_status, religion, year_level, birthday,
                contact, email, address, street, city, province, postal, status, password
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'accepted', ?
            )
        ");
        
        $stmt->execute([
            $pending_student['admission_type'],
            $pending_student['last_name'],
            $pending_student['first_name'],
            $pending_student['middle_name'],
            $pending_student['working_student'],
            $pending_student['gender'],
            $pending_student['civil_status'],
            $pending_student['religion'],
            $pending_student['year_level'],
            $pending_student['birthday'],
            $pending_student['contact'],
            $pending_student['email'],
            $pending_student['address'],
            $pending_student['street'],
            $pending_student['city'],
            $pending_student['province'],
            $pending_student['postal'],
            $password_hash
        ]);
        
        $student_id = $pdo->lastInsertId();
        
        // 4. Create login credentials with new username format
        $username = generateUsername($student_id, $pending_student['year_level']);
        $login_success = createStudentLogin($student_id, $username, $password_hash);
        
        if (!$login_success) {
            throw new Exception("Failed to create login credentials");
        }
        
        // 5. Update pending enrollment status
        $stmt = $pdo->prepare("UPDATE pending_enrollments SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$pending_id]);
        
        $pdo->commit();
        
        return [
            'student_id' => $student_id,
            'username' => $username,
            'temp_password' => $temp_password
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in acceptStudent: " . $e->getMessage());
        return $e->getMessage();
    }
}

function declineStudent($pending_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE pending_enrollments SET status = 'declined' WHERE id = ?");
        return $stmt->execute([$pending_id]);
    } catch (PDOException $e) {
        error_log("Error in declineStudent: " . $e->getMessage());
        return false;
    }
}

function createStudentLogin($student_id, $username, $password_hash) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_logins (student_id, username, password_hash)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$student_id, $username, $password_hash]);
    } catch (PDOException $e) {
        error_log("Error in createStudentLogin: " . $e->getMessage());
        return false;
    }
}


function generateRandomPassword($last_name) {
    return strtolower($last_name) . '8080';
}

function updateStudentPassword($student_id, $new_password) {
    global $pdo;
    try {
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password in both tables
        $pdo->beginTransaction();
        
        $stmt1 = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
        $stmt1->execute([$password_hash, $student_id]);
        
        $stmt2 = $pdo->prepare("UPDATE student_logins SET password_hash = ? WHERE id = ?");
        $stmt2->execute([$password_hash, $student_id]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in updateStudentPassword: " . $e->getMessage());
        return false;
    }
}
?>