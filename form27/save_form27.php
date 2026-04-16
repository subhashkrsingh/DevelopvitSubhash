<?php
// No spaces or any characters before <?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean all output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

require_once __DIR__ . '/../config.php';

// Ensure no output before JSON
ob_start();

header('Content-Type: application/json');

// Check if this is a POST request
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== csrf_token()) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

// Get examination ID
$examId = isset($_POST['examination_id']) ? (int)$_POST['examination_id'] : 0;
if ($examId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid examination ID.']);
    exit;
}

// Check if this is an update or insert
$recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
$isUpdate = ($recordId > 0);

try {
    $pdo = pdo();
    
    // Verify examination exists
    $exam = get_medical_examination_by_id($pdo, $examId);
    if (!$exam) {
        echo json_encode(['success' => false, 'message' => 'Examination record not found.']);
        exit;
    }
    
    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `form27` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `examination_id` int(11) NOT NULL,
            `serial_number` varchar(100) DEFAULT NULL,
            `department` varchar(255) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `sex` varchar(20) DEFAULT NULL,
            `age` int(11) DEFAULT NULL,
            `start_date` date DEFAULT NULL,
            `leave_transfer` text,
            `occupation` varchar(255) DEFAULT NULL,
            `raw_materials` text,
            `exam_date` date DEFAULT NULL,
            `result` varchar(50) DEFAULT NULL,
            `signs_symptoms` text,
            `tests_result` text,
            `suspension_details` text,
            `certificate_issued` varchar(255) DEFAULT NULL,
            `recertified_date` date DEFAULT NULL,
            `surgeon_signature` varchar(255) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `examination_id` (`examination_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Prepare data
    $data = [
        'serial_number' => isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null,
        'department' => isset($_POST['department']) ? trim($_POST['department']) : null,
        'name' => isset($_POST['name']) ? trim($_POST['name']) : null,
        'sex' => isset($_POST['sex']) ? trim($_POST['sex']) : null,
        'age' => isset($_POST['age']) && is_numeric($_POST['age']) ? (int)$_POST['age'] : null,
        'start_date' => isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'leave_transfer' => isset($_POST['leave_transfer']) ? trim($_POST['leave_transfer']) : null,
        'occupation' => isset($_POST['occupation']) ? trim($_POST['occupation']) : null,
        'raw_materials' => isset($_POST['raw_materials']) ? trim($_POST['raw_materials']) : null,
        'exam_date' => isset($_POST['exam_date']) && !empty($_POST['exam_date']) ? $_POST['exam_date'] : null,
        'result' => isset($_POST['result']) ? trim($_POST['result']) : null,
        'signs_symptoms' => isset($_POST['signs_symptoms']) ? trim($_POST['signs_symptoms']) : null,
        'tests_result' => isset($_POST['tests_result']) ? trim($_POST['tests_result']) : null,
        'suspension_details' => isset($_POST['suspension_details']) ? trim($_POST['suspension_details']) : null,
        'certificate_issued' => isset($_POST['certificate_issued']) ? trim($_POST['certificate_issued']) : null,
        'recertified_date' => isset($_POST['recertified_date']) && !empty($_POST['recertified_date']) ? $_POST['recertified_date'] : null,
        'surgeon_signature' => isset($_POST['surgeon_signature']) ? trim($_POST['surgeon_signature']) : null,
    ];
    
    // Validate required fields
    if (empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Name of worker is required.']);
        exit;
    }
    
    // Validate date formats
    foreach (['start_date', 'exam_date', 'recertified_date'] as $dateField) {
        if (!empty($data[$dateField]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$dateField])) {
            $data[$dateField] = null;
        }
    }
    
    $pdo->beginTransaction();
    
    if ($isUpdate) {
        // Update existing record
        $sql = "UPDATE form27 SET 
            serial_number = :serial_number,
            department = :department,
            name = :name,
            sex = :sex,
            age = :age,
            start_date = :start_date,
            leave_transfer = :leave_transfer,
            occupation = :occupation,
            raw_materials = :raw_materials,
            exam_date = :exam_date,
            result = :result,
            signs_symptoms = :signs_symptoms,
            tests_result = :tests_result,
            suspension_details = :suspension_details,
            certificate_issued = :certificate_issued,
            recertified_date = :recertified_date,
            surgeon_signature = :surgeon_signature
            WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $data['id'] = $recordId;
        $result = $stmt->execute($data);
        $savedId = $recordId;
    } else {
        // Check if record exists for this examination
        $checkStmt = $pdo->prepare("SELECT id FROM form27 WHERE examination_id = :examination_id LIMIT 1");
        $checkStmt->execute([':examination_id' => $examId]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing record
            $sql = "UPDATE form27 SET 
                serial_number = :serial_number,
                department = :department,
                name = :name,
                sex = :sex,
                age = :age,
                start_date = :start_date,
                leave_transfer = :leave_transfer,
                occupation = :occupation,
                raw_materials = :raw_materials,
                exam_date = :exam_date,
                result = :result,
                signs_symptoms = :signs_symptoms,
                tests_result = :tests_result,
                suspension_details = :suspension_details,
                certificate_issued = :certificate_issued,
                recertified_date = :recertified_date,
                surgeon_signature = :surgeon_signature
                WHERE examination_id = :examination_id";
            
            $stmt = $pdo->prepare($sql);
            $data['examination_id'] = $examId;
            $result = $stmt->execute($data);
            $savedId = $existing['id'];
        } else {
            // Insert new record
            $sql = "INSERT INTO form27 (
                examination_id, serial_number, department, name, sex, age, 
                start_date, leave_transfer, occupation, raw_materials, 
                exam_date, result, signs_symptoms, tests_result, 
                suspension_details, certificate_issued, recertified_date, 
                surgeon_signature, created_at
            ) VALUES (
                :examination_id, :serial_number, :department, :name, :sex, :age,
                :start_date, :leave_transfer, :occupation, :raw_materials,
                :exam_date, :result, :signs_symptoms, :tests_result,
                :suspension_details, :certificate_issued, :recertified_date,
                :surgeon_signature, NOW()
            )";
            
            $stmt = $pdo->prepare($sql);
            $data['examination_id'] = $examId;
            $result = $stmt->execute($data);
            $savedId = (int)$pdo->lastInsertId();
        }
    }
    
    if ($result) {
        $pdo->commit();
        
        // Clear any output buffers before sending JSON
        ob_clean();
        
        echo json_encode([
            'success' => true, 
            'message' => $isUpdate ? 'Form 27 updated successfully!' : 'Form 27 saved successfully!',
            'id' => $savedId
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error while saving.']);
    }
    
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Form27 Save Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>