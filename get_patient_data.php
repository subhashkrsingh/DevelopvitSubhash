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
        json_response(false, 'No record found for CLIMS ID: ' . $climsId);
    }

    $status = $row['data_status'] ?? 'draft';
    $lastCompleted = calculate_last_completed_container($row);

    json_response(true, 'Record loaded successfully.', [
        'id' => (int)$row['id'],
        'data' => $row,
        'data_status' => $status,
        'last_completed_container' => $lastCompleted,
    ]);
} catch (Throwable $e) {
    json_response(false, 'Unable to fetch patient data.', [
        'error' => APP_DEBUG ? $e->getMessage() : null,
    ], 500);
}

