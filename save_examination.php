<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

// Database connection
$conn = new mysqli('localhost', 'root', '', 'clinic');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $conn->connect_error]);
    exit;
}

// Helper function to get POST value
function getVal($key, $default = null) {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
}

// Generate serial number
$serial_no = getVal('serial_no');
if (empty($serial_no)) {
    $serial_no = 'CLIMS/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . rand(1000, 9999);
}

// Handle photo upload - FIXED: removed bin2hex(random_bytes(8))
$photo_path = null;
if (isset($_FILES['worker_photo']) && $_FILES['worker_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/photos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES['worker_photo']['name'], PATHINFO_EXTENSION));
    // Simple filename generation without random_bytes
    $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
    $upload_path = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['worker_photo']['tmp_name'], $upload_path)) {
        $photo_path = $upload_path;
    }
}

// Get all form values
$full_name = getVal('full_name');
$age_sex = getVal('age_sex');
$aadhar_no = getVal('aadhar_no');
$address = getVal('address');
$mobile_no = getVal('mobile_no');
$exam_date = getVal('exam_date', date('Y-m-d'));
$blood_group = getVal('blood_group');
$opinion = getVal('opinion');

// Validate required fields
if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Please enter the name of the contract worker.']);
    exit;
}

// Simple insert query
$sql = "INSERT INTO medical_examinations (
    serial_no, full_name, age_sex, aadhar_no, address, mobile_no, 
    exam_date, blood_group, opinion, worker_photo, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('ssssssssss', 
    $serial_no,
    $full_name,
    $age_sex,
    $aadhar_no,
    $address,
    $mobile_no,
    $exam_date,
    $blood_group,
    $opinion,
    $photo_path
);

if ($stmt->execute()) {
    $inserted_id = $conn->insert_id;
    
    // Store in session for Form 26
    $_SESSION['last_examination_id'] = $inserted_id;
    $_SESSION['last_serial_number'] = $serial_no;
    $_SESSION['last_patient_name'] = $full_name;
    $_SESSION['last_patient_age'] = $age_sex;
    $_SESSION['last_patient_address'] = $address;
    
    echo json_encode([
        'success' => true,
        'message' => 'Medical examination saved successfully!',
        'id' => $inserted_id,
        'serial_number' => $serial_no
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>