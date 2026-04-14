<?php
if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Invalid request method.', [], 405);
}

verify_csrf_or_exit($_POST['csrf_token'] ?? null);

$climsId = normalize_string($_POST['clims_id'] ?? null);
if (!$climsId) {
    json_response(false, 'CLIMS ID is required.');
}

$isTestMode = is_test_clims_id($climsId);
$testMessage = '⚠️ TEST MODE: Using dummy CLIMS ID. This is for local testing only.';

try {
    $pdo = pdo();
    $row = get_medical_examination_by_clims($pdo, $climsId);

    if (!$row) {
        $pdo->beginTransaction();

        try {
            $insertStmt = $pdo->prepare(
                'INSERT INTO medical_examinations (
                    clims_id, serial_no, exam_date, record_status, current_container, created_at, updated_at
                 ) VALUES (
                    :clims_id, :serial_no, :exam_date, :record_status, :current_container, NOW(), NOW()
                 )'
            );

            $insertStmt->execute([
                ':clims_id' => $climsId,
                ':serial_no' => generate_serial_no(),
                ':exam_date' => date('Y-m-d'),
                ':record_status' => 'draft',
                ':current_container' => 1,
            ]);

            $newId = (int)$pdo->lastInsertId();
            $row = get_medical_examination_by_id($pdo, $newId);

            $pdo->commit();
        } catch (Throwable $createError) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Handle race condition where same CLIMS ID got created between SELECT and INSERT.
            $row = get_medical_examination_by_clims($pdo, $climsId);
            if (!$row) {
                throw $createError;
            }
        }

        $progress = derive_progress_state($row ?: []);

        if (
            ($row['record_status'] ?? null) !== $progress['record_status'] ||
            (int)($row['current_container'] ?? 0) !== (int)$progress['current_container']
        ) {
            $syncStmt = $pdo->prepare(
                'UPDATE medical_examinations
                 SET record_status = :record_status, current_container = :current_container, updated_at = NOW()
                 WHERE id = :id'
            );
            $syncStmt->execute([
                ':record_status' => $progress['record_status'],
                ':current_container' => $progress['current_container'],
                ':id' => (int)$row['id'],
            ]);

            $row = get_medical_examination_by_id($pdo, (int)$row['id']) ?: $row;
        }

        json_response(true, $isTestMode ? $testMessage : 'No record found. New draft record initialized.', [
            'found' => false,
            'created_new' => true,
            'is_test_mode' => $isTestMode,
            'test_message' => $isTestMode ? $testMessage : null,
            'id' => (int)$row['id'],
            'data' => $row,
            'record_status' => $progress['record_status'],
            'current_container' => $progress['current_container'],
            'last_completed_container' => $progress['last_completed_container'],
        ]);
    }

    $progress = derive_progress_state($row);

    if (
        ($row['record_status'] ?? null) !== $progress['record_status'] ||
        (int)($row['current_container'] ?? 0) !== (int)$progress['current_container']
    ) {
        $syncStmt = $pdo->prepare(
            'UPDATE medical_examinations
             SET record_status = :record_status, current_container = :current_container, updated_at = NOW()
             WHERE id = :id'
        );
        $syncStmt->execute([
            ':record_status' => $progress['record_status'],
            ':current_container' => $progress['current_container'],
            ':id' => (int)$row['id'],
        ]);

        $row = get_medical_examination_by_id($pdo, (int)$row['id']) ?: $row;
    }

    json_response(true, $isTestMode ? $testMessage : 'Record loaded successfully.', [
        'found' => true,
        'created_new' => false,
        'is_test_mode' => $isTestMode,
        'test_message' => $isTestMode ? $testMessage : null,
        'id' => (int)$row['id'],
        'data' => $row,
        'record_status' => $progress['record_status'],
        'current_container' => $progress['current_container'],
        'last_completed_container' => $progress['last_completed_container'],
    ]);
} catch (Throwable $e) {
    json_response(false, 'Unable to fetch patient data.', [
        'error' => APP_DEBUG ? $e->getMessage() : null,
    ], 500);
}
?>