<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $serial_number   = $_POST['serial'] ?? '';
    $department      = $_POST['department'] ?? '';
    $name            = $_POST['name'] ?? '';
    $sex             = $_POST['sex'] ?? '';
    $age             = $_POST['age'] ?? '';
    $start_date      = $_POST['start_date'] ?? '';
    $leave_transfer  = $_POST['leave_transfer'] ?? '';
    $occupation      = $_POST['occupation'] ?? '';
    $raw_materials   = $_POST['raw_materials'] ?? '';
    $exam_date       = $_POST['exam_date'] ?? '';
    $result          = $_POST['result'] ?? '';
    $signs_symptoms  = $_POST['signs_symptoms'] ?? '';
    $tests_result    = $_POST['tests_result'] ?? '';
    $suspension      = $_POST['suspension_details'] ?? '';
    $certificate     = $_POST['certificate_issued'] ?? '';
    $recertified     = $_POST['recertified_date'] ?? '';
    $signature       = $_POST['surgeon_signature'] ?? '';
    $date            = $_POST['date'] ?? '';

    $stmt = $conn->prepare("INSERT INTO health_register
        (serial_number, department, name, sex, age, start_date, leave_transfer, occupation, raw_materials, exam_date, result, signs_symptoms, tests_result, suspension_details, certificate_issued, recertified_date, surgeon_signature, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssisssssssssssss",
        $serial_number, $department, $name, $sex, $age, $start_date, $leave_transfer, $occupation, $raw_materials, $exam_date, $result, $signs_symptoms, $tests_result, $suspension, $certificate, $recertified, $signature, $date
    );

    if ($stmt->execute()) {
        echo "Form data saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
