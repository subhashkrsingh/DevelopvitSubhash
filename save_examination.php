<?php

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Invalid request method.', [], 405);
}

verify_csrf_or_exit($_POST['csrf_token'] ?? null);

$action = normalize_string($_POST['action'] ?? 'save_container') ?? 'save_container';

try {
    $pdo = pdo();

    if ($action === 'save_container') {
        $container = (int)($_POST['container'] ?? 0);
        if ($container < 1 || $container > 8) {
            json_response(false, 'Invalid container number.');
        }

        $climsId = normalize_string($_POST['clims_id'] ?? null);
        if (!$climsId) {
            json_response(false, 'CLIMS ID is mandatory.');
        }

        $containerFields = get_container_fields();
        $fields = $containerFields[$container];

        if ($container === 1) {
            $fullName = normalize_string($_POST['full_name'] ?? null);
            if (!$fullName) {
                json_response(false, 'Name is required in Container 1.');
            }
        }

        $pdo->beginTransaction();

        $existing = get_medical_examination_by_clims($pdo, $climsId);

        if ($existing && ($existing['data_status'] ?? '') === 'verified') {
            $pdo->rollBack();
            json_response(false, 'This record is already verified and read-only.');
        }

        if (!$existing) {
            $serialNo = normalize_string($_POST['serial_no'] ?? null) ?: generate_serial_no();
            $examDate = normalize_string($_POST['exam_date'] ?? null) ?: date('Y-m-d');

            $insertStmt = $pdo->prepare(
                'INSERT INTO medical_examinations (clims_id, serial_no, exam_date, data_status, created_at, updated_at)
                 VALUES (:clims_id, :serial_no, :exam_date, :data_status, NOW(), NOW())'
            );
            $insertStmt->execute([
                ':clims_id' => $climsId,
                ':serial_no' => $serialNo,
                ':exam_date' => $examDate,
                ':data_status' => 'draft',
            ]);

            $id = (int)$pdo->lastInsertId();
            $existing = get_medical_examination_by_id($pdo, $id);
        }

        $id = (int)$existing['id'];

        $updateData = [];
        foreach ($fields as $field) {
            if ($field === 'clims_id') {
                $updateData[$field] = $climsId;
                continue;
            }

            if ($field === 'worker_photo') {
                $uploadedPhoto = save_uploaded_photo('worker_photo');
                if ($uploadedPhoto !== null) {
                    $updateData['worker_photo'] = $uploadedPhoto;
                }
                continue;
            }

            $raw = $_POST[$field] ?? null;

            if (in_array($field, ['height', 'weight'], true)) {
                $value = is_numeric((string)$raw) ? number_format((float)$raw, 2, '.', '') : null;
                $updateData[$field] = $value;
                continue;
            }

            if (in_array($field, ['exam_date', 'demo_exam_date'], true)) {
                $dateVal = normalize_string($raw);
                if ($dateVal && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
                    $dateVal = null;
                }
                $updateData[$field] = $dateVal;
                continue;
            }

            $updateData[$field] = normalize_string($raw);
        }

        if ($container === 1) {
            if (!has_value($updateData['serial_no'] ?? null)) {
                $updateData['serial_no'] = has_value($existing['serial_no'] ?? null)
                    ? $existing['serial_no']
                    : generate_serial_no();
            }

            if (!has_value($updateData['exam_date'] ?? null)) {
                $updateData['exam_date'] = has_value($existing['exam_date'] ?? null)
                    ? $existing['exam_date']
                    : date('Y-m-d');
            }
        }

        if (!empty($updateData)) {
            $setParts = [];
            $params = [':id' => $id];

            foreach ($updateData as $column => $value) {
                $setParts[] = "{$column} = :{$column}";
                $params[":{$column}"] = $value;
            }

            $sql = 'UPDATE medical_examinations SET ' . implode(', ', $setParts) . ', updated_at = NOW() WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        $updated = get_medical_examination_by_id($pdo, $id);
        $lastCompleted = calculate_last_completed_container($updated ?? []);
        $newStatus = $lastCompleted >= 8 ? 'completed' : 'draft';

        $statusStmt = $pdo->prepare('UPDATE medical_examinations SET data_status = :status, updated_at = NOW() WHERE id = :id');
        $statusStmt->execute([
            ':status' => $newStatus,
            ':id' => $id,
        ]);

        $finalRow = get_medical_examination_by_id($pdo, $id);

        $pdo->commit();

        json_response(true, 'Container ' . $container . ' saved successfully.', [
            'id' => $id,
            'serial_no' => $finalRow['serial_no'] ?? null,
            'worker_photo' => $finalRow['worker_photo'] ?? null,
            'data_status' => $newStatus,
            'last_completed_container' => $lastCompleted,
            'next_step' => min($lastCompleted + 1, 8),
        ]);
    }

    if ($action === 'final_submit') {
        $climsId = normalize_string($_POST['clims_id'] ?? null);
        if (!$climsId) {
            json_response(false, 'CLIMS ID is required for final submit.');
        }

        $row = get_medical_examination_by_clims($pdo, $climsId);
        if (!$row) {
            json_response(false, 'No record found for the provided CLIMS ID.');
        }

        $id = (int)$row['id'];
        $lastCompleted = calculate_last_completed_container($row);

        if ($lastCompleted < 8) {
            json_response(false, 'Please save all 8 containers before final submission.');
        }

        $stmt = $pdo->prepare('UPDATE medical_examinations SET data_status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'verified',
            ':id' => $id,
        ]);

        $_SESSION['current_examination_id'] = $id;

        json_response(true, 'Final submission completed. Redirecting to Form 26.', [
            'id' => $id,
            'data_status' => 'verified',
            'redirect_url' => 'form26/index.php?examination_id=' . urlencode((string)$id),
        ]);
    }

    json_response(false, 'Unsupported action.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, 'Server error while saving examination data.', [
        'error' => APP_DEBUG ? $e->getMessage() : null,
    ], 500);
}

