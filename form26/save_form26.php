<?php
// Disable error display to browser, log instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if session is already started before starting it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Clear any output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/json');

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== csrf_token()) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

$examination_id = isset($_POST['examination_id']) ? (int)$_POST['examination_id'] : 0;

if (!$examination_id) {
    echo json_encode(['success' => false, 'message' => 'No examination ID found.']);
    exit;
}

try {
    $pdo = pdo();
    
    // Create form26 table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `form26` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `examination_id` int(11) NOT NULL,
            `serial_number` varchar(100) DEFAULT NULL,
            `patient_name` varchar(255) DEFAULT NULL,
            `father_name` varchar(255) DEFAULT NULL,
            `address` text,
            `employment_details` varchar(255) DEFAULT NULL,
            `designation` varchar(255) DEFAULT NULL,
            `age` int(11) DEFAULT NULL,
            `fitness_status` varchar(50) DEFAULT NULL,
            `further_exam_period` varchar(255) DEFAULT NULL,
            `previous_certificate_no` varchar(100) DEFAULT NULL,
            `surgeon_signature` varchar(255) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `examination_id` (`examination_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Check if record exists
    $checkStmt = $pdo->prepare("SELECT id FROM form26 WHERE examination_id = :examination_id LIMIT 1");
    $checkStmt->execute([':examination_id' => $examination_id]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    $result = false;
    
    if ($exists) {
        // Update existing record
        $sql = "UPDATE form26 SET 
            serial_number = :serial_number,
            patient_name = :patient_name,
            father_name = :father_name,
            address = :address,
            employment_details = :employment_details,
            designation = :designation,
            age = :age,
            fitness_status = :fitness_status,
            further_exam_period = :further_exam_period,
            previous_certificate_no = :previous_certificate_no,
            surgeon_signature = :surgeon_signature
            WHERE examination_id = :examination_id";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':serial_number' => $_POST['serial'] ?? '',
            ':patient_name' => $_POST['patient_name'] ?? '',
            ':father_name' => $_POST['father_name'] ?? '',
            ':address' => $_POST['address'] ?? '',
            ':employment_details' => $_POST['employment_details'] ?? '',
            ':designation' => $_POST['designation'] ?? '',
            ':age' => (int)($_POST['age'] ?? 0),
            ':fitness_status' => $_POST['fitness_status'] ?? '',
            ':further_exam_period' => $_POST['further_exam_period'] ?? '',
            ':previous_certificate_no' => $_POST['previous_certificate_no'] ?? '',
            ':surgeon_signature' => $_POST['surgeon_signature'] ?? '',
            ':examination_id' => $examination_id
        ]);
    } else {
        // Insert new record
        $sql = "INSERT INTO form26 (
            examination_id, serial_number, patient_name, father_name, address,
            employment_details, designation, age, fitness_status,
            further_exam_period, previous_certificate_no, surgeon_signature, created_at
        ) VALUES (
            :examination_id, :serial_number, :patient_name, :father_name, :address,
            :employment_details, :designation, :age, :fitness_status,
            :further_exam_period, :previous_certificate_no, :surgeon_signature, NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':examination_id' => $examination_id,
            ':serial_number' => $_POST['serial'] ?? '',
            ':patient_name' => $_POST['patient_name'] ?? '',
            ':father_name' => $_POST['father_name'] ?? '',
            ':address' => $_POST['address'] ?? '',
            ':employment_details' => $_POST['employment_details'] ?? '',
            ':designation' => $_POST['designation'] ?? '',
            ':age' => (int)($_POST['age'] ?? 0),
            ':fitness_status' => $_POST['fitness_status'] ?? '',
            ':further_exam_period' => $_POST['further_exam_period'] ?? '',
            ':previous_certificate_no' => $_POST['previous_certificate_no'] ?? '',
            ':surgeon_signature' => $_POST['surgeon_signature'] ?? ''
        ]);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Form 26 saved successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while saving.']);
    }
    
} catch (Throwable $e) {
    error_log('Form26 Save Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>