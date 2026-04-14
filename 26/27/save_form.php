<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "clinic";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Helper function to get POST value
function getVal($key, $default = null) {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
}

// Generate serial number if not provided
$serial_number = getVal('serial');
if (empty($serial_number)) {
    $serial_number = 'FORM27/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . rand(1000, 9999);
}

// Get all values into variables first (to avoid "only variables should be passed by reference" notice)
$department = getVal('department');
$name = getVal('name');
$sex = getVal('sex');
$age = getVal('age', 0);
$start_date = getVal('start_date');
$leave_transfer = getVal('leave_transfer');
$occupation = getVal('occupation');
$raw_materials = getVal('raw_materials');
$exam_date = getVal('exam_date');
$result = getVal('result');
$signs_symptoms = getVal('signs_symptoms');
$tests_result = getVal('tests_result');
$suspension_details = getVal('suspension_details');
$certificate_issued = getVal('certificate_issued');
$recertified_date = getVal('recertified_date');
$surgeon_signature = getVal('surgeon_signature');

// Prepare SQL statement
$sql = "INSERT INTO health_registers (
    serial_number, 
    department, 
    name, 
    sex, 
    age, 
    start_date, 
    leave_transfer, 
    occupation, 
    raw_materials, 
    exam_date, 
    result, 
    signs_symptoms, 
    tests_result, 
    suspension_details, 
    certificate_issued, 
    recertified_date, 
    surgeon_signature, 
    created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// Now bind using the variables (not function calls)
$stmt->bind_param('ssssiisssssssssss',
    $serial_number,
    $department,
    $name,
    $sex,
    $age,
    $start_date,
    $leave_transfer,
    $occupation,
    $raw_materials,
    $exam_date,
    $result,
    $signs_symptoms,
    $tests_result,
    $suspension_details,
    $certificate_issued,
    $recertified_date,
    $surgeon_signature
);

if ($stmt->execute()) {
    $inserted_id = $conn->insert_id;
    echo json_encode([
        'success' => true, 
        'message' => 'FORM 27 saved successfully!',
        'id' => $inserted_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>