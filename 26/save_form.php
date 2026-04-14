<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

// Database connection
require_once '../db.php';

// Collect and sanitize form data
$serial = mysqli_real_escape_string($conn, $_POST['serial'] ?? '');
$patient_name = mysqli_real_escape_string($conn, $_POST['patient_name'] ?? '');
$father_name = mysqli_real_escape_string($conn, $_POST['father_name'] ?? '');
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$employment_details = mysqli_real_escape_string($conn, $_POST['employment_details'] ?? '');
$designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');
$age = intval($_POST['age'] ?? 0);
$fitness_status = mysqli_real_escape_string($conn, $_POST['fitness_status'] ?? '');
$further_exam_period = mysqli_real_escape_string($conn, $_POST['further_exam_period'] ?? '');
$previous_certificate_no = mysqli_real_escape_string($conn, $_POST['previous_certificate_no'] ?? '');
$surgeon_signature = mysqli_real_escape_string($conn, $_POST['surgeon_signature'] ?? '');

// Validate required fields
if (empty($serial) || empty($patient_name) || empty($father_name) || empty($address) || 
    empty($employment_details) || empty($designation) || empty($age) || empty($fitness_status)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

// Insert into DB
$sql = "INSERT INTO form26 
(serial_number, patient_name, father_name, address, employment_details, designation, age, fitness_status, further_exam_period, previous_certificate_no, surgeon_signature)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssissss",
    $serial, $patient_name, $father_name, $address,
    $employment_details, $designation, $age, $fitness_status,
    $further_exam_period, $previous_certificate_no, $surgeon_signature
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Form saved successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>