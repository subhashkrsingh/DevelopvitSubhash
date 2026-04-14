<?php

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Invalid request method.', [], 405);
}

verify_csrf_or_exit($_POST['csrf_token'] ?? null);

$climsId = normalize_string($_POST['clims_id'] ?? null);
if (!$climsId) {
    json_response(false, 'CLIMS ID is required.');
}

try {
    $pdo = pdo();
    $row = get_medical_examination_by_clims($pdo, $climsId);

    if (!$row) {
        json_response(true, 'No record found for CLIMS ID: ' . $climsId, [
            'found' => false,
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

    json_response(true, 'Record loaded successfully.', [
        'found' => true,
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
