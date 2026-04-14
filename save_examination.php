<?php


if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Invalid request method.', [], 405);
}

verify_csrf_or_exit($_POST['csrf_token'] ?? null);

$action = normalize_string($_POST['action'] ?? 'save_container') ?? 'save_container';

function normalize_field_value(string $field, $raw)
{
    if (in_array($field, ['height', 'weight'], true)) {
        return is_numeric((string)$raw) ? number_format((float)$raw, 2, '.', '') : null;
    }

    if (in_array($field, ['exam_date', 'demo_exam_date'], true)) {
        $dateVal = normalize_string($raw);
        if ($dateVal && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal)) {
            return null;
        }
        return $dateVal;
    }

    return normalize_string($raw);
}

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
                json_response(false, 'Name is required in demographics.');
            }
        }

        $pdo->beginTransaction();

        $existing = get_medical_examination_by_clims($pdo, $climsId);

        if (!$existing) {
            $serialNo = normalize_string($_POST['serial_no'] ?? null) ?: generate_serial_no();
            $examDate = normalize_string($_POST['exam_date'] ?? null) ?: date('Y-m-d');

            $insertStmt = $pdo->prepare(
                'INSERT INTO medical_examinations (
                    clims_id, serial_no, exam_date, record_status, current_container, created_at, updated_at
                ) VALUES (
                    :clims_id, :serial_no, :exam_date, :record_status, :current_container, NOW(), NOW()
                )'
            );
            $insertStmt->execute([
                ':clims_id' => $climsId,
                ':serial_no' => $serialNo,
                ':exam_date' => $examDate,
                ':record_status' => 'draft',
                ':current_container' => 1,
            ]);

            $existing = get_medical_examination_by_id($pdo, (int)$pdo->lastInsertId());
        }

        $progress = derive_progress_state($existing);
        if (($progress['record_status'] ?? 'draft') === 'submitted') {
            $pdo->rollBack();
            json_response(false, 'This record is already submitted and read-only.');
        }

        $expected = (int)($progress['current_container'] ?? 1);
        if ($container !== $expected) {
            $pdo->rollBack();
            json_response(false, 'Please save only the currently unlocked container (Container ' . $expected . ').');
        }

        $id = (int)$existing['id'];
        $updateData = [];

        foreach ($fields as $field) {
            if ($field === 'clims_id') {
                continue;
            }

            if ($field === 'worker_photo') {
                $uploadedPhoto = save_uploaded_photo('worker_photo');
                if ($uploadedPhoto !== null) {
                    $updateData['worker_photo'] = $uploadedPhoto;
                }
                continue;
            }

            $updateData[$field] = normalize_field_value($field, $_POST[$field] ?? null);
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
        $newProgress = derive_progress_state($updated ?? []);

        $statusStmt = $pdo->prepare(
            'UPDATE medical_examinations
             SET record_status = :record_status, current_container = :current_container, updated_at = NOW()
             WHERE id = :id'
        );
        $statusStmt->execute([
            ':record_status' => $newProgress['record_status'],
            ':current_container' => $newProgress['current_container'],
            ':id' => $id,
        ]);

        $finalRow = get_medical_examination_by_id($pdo, $id);

        $pdo->commit();

        json_response(true, 'Container ' . $container . ' saved successfully.', [
            'id' => $id,
            'serial_no' => $finalRow['serial_no'] ?? null,
            'worker_photo' => $finalRow['worker_photo'] ?? null,
            'record_status' => $newProgress['record_status'],
            'current_container' => $newProgress['current_container'],
            'last_completed_container' => $newProgress['last_completed_container'],
        ]);
    }

    if ($action === 'save_all') {
        $climsId = normalize_string($_POST['clims_id'] ?? null);
        if (!$climsId) {
            json_response(false, 'CLIMS ID is required to save changes.');
        }

        $fullName = normalize_string($_POST['full_name'] ?? null);
        if (!$fullName) {
            json_response(false, 'Name is required.');
        }

        $pdo->beginTransaction();

        $row = get_medical_examination_by_clims($pdo, $climsId);
        if (!$row) {
            $pdo->rollBack();
            json_response(false, 'Record not found for CLIMS ID: ' . $climsId);
        }

        $progress = derive_progress_state($row);
        if (($progress['record_status'] ?? 'draft') === 'submitted') {
            $pdo->rollBack();
            json_response(false, 'Submitted records cannot be edited.');
        }

        $id = (int)$row['id'];
        $updateData = [];

        $containerFields = get_container_fields();
        foreach ($containerFields as $container => $fields) {
            foreach ($fields as $field) {
                if ($field === 'clims_id') {
                    continue;
                }

                if ($field === 'worker_photo') {
                    continue;
                }

                $updateData[$field] = normalize_field_value($field, $_POST[$field] ?? null);
            }
        }

        $uploadedPhoto = save_uploaded_photo('worker_photo');
        if ($uploadedPhoto !== null) {
            $updateData['worker_photo'] = $uploadedPhoto;
        }

        if (!has_value($updateData['serial_no'] ?? null)) {
            $updateData['serial_no'] = has_value($row['serial_no'] ?? null)
                ? $row['serial_no']
                : generate_serial_no();
        }

        if (!has_value($updateData['exam_date'] ?? null)) {
            $updateData['exam_date'] = has_value($row['exam_date'] ?? null)
                ? $row['exam_date']
                : date('Y-m-d');
        }

        $setParts = [];
        $params = [':id' => $id];

        foreach ($updateData as $column => $value) {
            $setParts[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }

        $sql = 'UPDATE medical_examinations SET ' . implode(', ', $setParts) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $updated = get_medical_examination_by_id($pdo, $id);
        $newProgress = derive_progress_state($updated ?? []);

        $statusStmt = $pdo->prepare(
            'UPDATE medical_examinations
             SET record_status = :record_status, current_container = :current_container, updated_at = NOW()
             WHERE id = :id'
        );
        $statusStmt->execute([
            ':record_status' => $newProgress['record_status'],
            ':current_container' => $newProgress['current_container'],
            ':id' => $id,
        ]);

        $finalRow = get_medical_examination_by_id($pdo, $id);

        $pdo->commit();

        json_response(true, 'Record changes saved successfully.', [
            'id' => $id,
            'serial_no' => $finalRow['serial_no'] ?? null,
            'worker_photo' => $finalRow['worker_photo'] ?? null,
            'record_status' => $newProgress['record_status'],
            'current_container' => $newProgress['current_container'],
            'last_completed_container' => $newProgress['last_completed_container'],
        ]);
    }

    if ($action === 'final_submit') {
        $climsId = normalize_string($_POST['clims_id'] ?? null);
        if (!$climsId) {
            json_response(false, 'CLIMS ID is required for submission.');
        }

        $row = get_medical_examination_by_clims($pdo, $climsId);
        if (!$row) {
            json_response(false, 'Record not found for CLIMS ID: ' . $climsId);
        }

        $id = (int)$row['id'];
        $progress = derive_progress_state($row);

        if ($progress['record_status'] === 'submitted') {
            $_SESSION['current_examination_id'] = $id;
            json_response(true, 'Record already submitted. Opening Form 26.', [
                'id' => $id,
                'record_status' => 'submitted',
                'redirect_url' => 'form26/index.php?examination_id=' . urlencode((string)$id),
            ]);
        }

        if ((int)$progress['last_completed_container'] < 8) {
            json_response(false, 'Please save all 8 containers before submission.');
        }

        $stmt = $pdo->prepare(
            'UPDATE medical_examinations
             SET record_status = :record_status, current_container = 8, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':record_status' => 'submitted',
            ':id' => $id,
        ]);

        $_SESSION['current_examination_id'] = $id;

        json_response(true, 'Submission successful. Redirecting to Form 26.', [
            'id' => $id,
            'record_status' => 'submitted',
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
