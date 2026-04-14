<?php
require_once 'db.php';
header('Content-Type: application/json');
session_start();

try {
    $sql = "INSERT INTO form26 (examination_id, serial_number, patient_name, father_name, address, employment_details, designation, age, fitness_status, further_exam_period, previous_certificate_no, surgeon_signature) 
            VALUES (:exam_id, :serial, :name, :father, :address, :employment, :designation, :age, :fitness, :further, :prev, :surgeon)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':exam_id' => $_SESSION['last_examination_id'] ?? null,
        ':serial' => $_POST['serial'] ?? '',
        ':name' => $_POST['patient_name'] ?? '',
        ':father' => $_POST['father_name'] ?? '',
        ':address' => $_POST['address'] ?? '',
        ':employment' => $_POST['employment'] ?? '',
        ':designation' => $_POST['designation'] ?? '',
        ':age' => $_POST['age'] ?? 0,
        ':fitness' => $_POST['fitness_status'] ?? '',
        ':further' => $_POST['further_exam'] ?? '',
        ':prev' => $_POST['prev_cert'] ?? '',
        ':surgeon' => $_POST['surgeon_signature'] ?? ''
    ]);
    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>