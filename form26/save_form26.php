<?php


if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Invalid request method.', [], 405);
}

verify_csrf_or_exit($_POST['csrf_token'] ?? null);

$examId = (int)($_POST['examination_id'] ?? 0);
if ($examId <= 0) {
    json_response(false, 'Invalid examination ID.');
}

try {
    $pdo = pdo();

    $exam = get_medical_examination_by_id($pdo, $examId);
    if (!$exam) {
        json_response(false, 'Examination record not found.');
    }

    $payload = [
        'serial_number' => normalize_string($_POST['serial_number'] ?? null),
        'patient_name' => normalize_string($_POST['patient_name'] ?? null),
        'father_name' => normalize_string($_POST['father_name'] ?? null),
        'address' => normalize_string($_POST['address'] ?? null),
        'employment_details' => normalize_string($_POST['employment_details'] ?? null),
        'designation' => normalize_string($_POST['designation'] ?? null),
        'age' => is_numeric((string)($_POST['age'] ?? '')) ? (int)$_POST['age'] : null,
        'fitness_status' => normalize_string($_POST['fitness_status'] ?? null),
        'further_exam_period' => normalize_string($_POST['further_exam_period'] ?? null),
        'previous_certificate_no' => normalize_string($_POST['previous_certificate_no'] ?? null),
        'surgeon_signature' => normalize_string($_POST['surgeon_signature'] ?? null),
    ];

    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare('SELECT id FROM form26 WHERE examination_id = :examination_id LIMIT 1');
    $checkStmt->execute([':examination_id' => $examId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $updateStmt = $pdo->prepare(
            'UPDATE form26 SET
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
             WHERE examination_id = :examination_id'
        );

        $updateStmt->execute([
            ':serial_number' => $payload['serial_number'],
            ':patient_name' => $payload['patient_name'],
            ':father_name' => $payload['father_name'],
            ':address' => $payload['address'],
            ':employment_details' => $payload['employment_details'],
            ':designation' => $payload['designation'],
            ':age' => $payload['age'],
            ':fitness_status' => $payload['fitness_status'],
            ':further_exam_period' => $payload['further_exam_period'],
            ':previous_certificate_no' => $payload['previous_certificate_no'],
            ':surgeon_signature' => $payload['surgeon_signature'],
            ':examination_id' => $examId,
        ]);

        $form26Id = (int)$existing['id'];
    } else {
        $insertStmt = $pdo->prepare(
            'INSERT INTO form26 (
                examination_id, serial_number, patient_name, father_name, address,
                employment_details, designation, age, fitness_status, further_exam_period,
                previous_certificate_no, surgeon_signature, created_at
             ) VALUES (
                :examination_id, :serial_number, :patient_name, :father_name, :address,
                :employment_details, :designation, :age, :fitness_status, :further_exam_period,
                :previous_certificate_no, :surgeon_signature, NOW()
             )'
        );

        $insertStmt->execute([
            ':examination_id' => $examId,
            ':serial_number' => $payload['serial_number'],
            ':patient_name' => $payload['patient_name'],
            ':father_name' => $payload['father_name'],
            ':address' => $payload['address'],
            ':employment_details' => $payload['employment_details'],
            ':designation' => $payload['designation'],
            ':age' => $payload['age'],
            ':fitness_status' => $payload['fitness_status'],
            ':further_exam_period' => $payload['further_exam_period'],
            ':previous_certificate_no' => $payload['previous_certificate_no'],
            ':surgeon_signature' => $payload['surgeon_signature'],
        ]);

        $form26Id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

    // Determine redirect based on operation type
    if ($existing) {
        // This was an UPDATE - redirect to Form 27
        $message = 'Form 26 updated successfully. Redirecting to Form 27...';
        $redirectUrl = '../form27/index.php?examination_id=' . urlencode((string)$examId);
    } else {
        // This was an INSERT - redirect back to Form 26 in edit mode
        $message = 'Form 26 saved successfully. You can now edit it before moving to Form 27.';
        $redirectUrl = 'index.php?examination_id=' . urlencode((string)$examId);
    }

    json_response(true, $message, [
        'id' => $form26Id,
        'redirect_url' => $redirectUrl,
        'was_update' => (bool)$existing,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, 'Unable to save Form 26.', [
        'error' => APP_DEBUG ? $e->getMessage() : null,
    ], 500);
}

