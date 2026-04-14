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
        'department' => normalize_string($_POST['department'] ?? null),
        'name' => normalize_string($_POST['name'] ?? null),
        'sex' => normalize_string($_POST['sex'] ?? null),
        'age' => is_numeric((string)($_POST['age'] ?? '')) ? (int)$_POST['age'] : null,
        'start_date' => normalize_string($_POST['start_date'] ?? null),
        'leave_transfer' => normalize_string($_POST['leave_transfer'] ?? null),
        'occupation' => normalize_string($_POST['occupation'] ?? null),
        'raw_materials' => normalize_string($_POST['raw_materials'] ?? null),
        'exam_date' => normalize_string($_POST['exam_date'] ?? null),
        'result' => normalize_string($_POST['result'] ?? null),
        'signs_symptoms' => normalize_string($_POST['signs_symptoms'] ?? null),
        'tests_result' => normalize_string($_POST['tests_result'] ?? null),
        'suspension_details' => normalize_string($_POST['suspension_details'] ?? null),
        'certificate_issued' => normalize_string($_POST['certificate_issued'] ?? null),
        'recertified_date' => normalize_string($_POST['recertified_date'] ?? null),
        'surgeon_signature' => normalize_string($_POST['surgeon_signature'] ?? null),
    ];

    foreach (['start_date', 'exam_date', 'recertified_date'] as $dateField) {
        if ($payload[$dateField] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload[$dateField])) {
            $payload[$dateField] = null;
        }
    }

    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare('SELECT id FROM form27 WHERE examination_id = :examination_id LIMIT 1');
    $checkStmt->execute([':examination_id' => $examId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $updateStmt = $pdo->prepare(
            'UPDATE form27 SET
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
             WHERE examination_id = :examination_id'
        );

        $updateStmt->execute([
            ':serial_number' => $payload['serial_number'],
            ':department' => $payload['department'],
            ':name' => $payload['name'],
            ':sex' => $payload['sex'],
            ':age' => $payload['age'],
            ':start_date' => $payload['start_date'],
            ':leave_transfer' => $payload['leave_transfer'],
            ':occupation' => $payload['occupation'],
            ':raw_materials' => $payload['raw_materials'],
            ':exam_date' => $payload['exam_date'],
            ':result' => $payload['result'],
            ':signs_symptoms' => $payload['signs_symptoms'],
            ':tests_result' => $payload['tests_result'],
            ':suspension_details' => $payload['suspension_details'],
            ':certificate_issued' => $payload['certificate_issued'],
            ':recertified_date' => $payload['recertified_date'],
            ':surgeon_signature' => $payload['surgeon_signature'],
            ':examination_id' => $examId,
        ]);

        $form27Id = (int)$existing['id'];
    } else {
        $insertStmt = $pdo->prepare(
            'INSERT INTO form27 (
                examination_id, serial_number, department, name, sex, age, start_date,
                leave_transfer, occupation, raw_materials, exam_date, result,
                signs_symptoms, tests_result, suspension_details, certificate_issued,
                recertified_date, surgeon_signature, created_at
             ) VALUES (
                :examination_id, :serial_number, :department, :name, :sex, :age, :start_date,
                :leave_transfer, :occupation, :raw_materials, :exam_date, :result,
                :signs_symptoms, :tests_result, :suspension_details, :certificate_issued,
                :recertified_date, :surgeon_signature, NOW()
             )'
        );

        $insertStmt->execute([
            ':examination_id' => $examId,
            ':serial_number' => $payload['serial_number'],
            ':department' => $payload['department'],
            ':name' => $payload['name'],
            ':sex' => $payload['sex'],
            ':age' => $payload['age'],
            ':start_date' => $payload['start_date'],
            ':leave_transfer' => $payload['leave_transfer'],
            ':occupation' => $payload['occupation'],
            ':raw_materials' => $payload['raw_materials'],
            ':exam_date' => $payload['exam_date'],
            ':result' => $payload['result'],
            ':signs_symptoms' => $payload['signs_symptoms'],
            ':tests_result' => $payload['tests_result'],
            ':suspension_details' => $payload['suspension_details'],
            ':certificate_issued' => $payload['certificate_issued'],
            ':recertified_date' => $payload['recertified_date'],
            ':surgeon_signature' => $payload['surgeon_signature'],
        ]);

        $form27Id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

    json_response(true, 'Form 27 saved successfully.', [
        'id' => $form27Id,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, 'Unable to save Form 27.', [
        'error' => APP_DEBUG ? $e->getMessage() : null,
    ], 500);
}

