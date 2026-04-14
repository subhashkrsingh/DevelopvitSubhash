<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (ob_get_level() === 0) {
    ob_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('DUMMY_CLIMS_ID', 'CLIMS-NTPC-2026-000');

function send_json(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_status_columns(PDO $pdo): void
{
    try {
        // Check if record_status column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM medical_examinations LIKE 'record_status'");
        $stmt->execute();
        $hasRecordStatus = $stmt->fetch() !== false;

        if (!$hasRecordStatus) {
            $pdo->exec(
                "ALTER TABLE medical_examinations
                 ADD COLUMN record_status ENUM('draft','partial','completed','submitted') DEFAULT 'draft'"
            );
        }

        // Check if current_container column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM medical_examinations LIKE 'current_container'");
        $stmt->execute();
        $hasCurrentContainer = $stmt->fetch() !== false;

        if (!$hasCurrentContainer) {
            $pdo->exec(
                'ALTER TABLE medical_examinations
                 ADD COLUMN current_container INT DEFAULT 1'
            );
        }

        // Check if data_status column exists (for migration)
        $stmt = $pdo->prepare("SHOW COLUMNS FROM medical_examinations LIKE 'data_status'");
        $stmt->execute();
        $hasDataStatus = $stmt->fetch() !== false;

        if ($hasRecordStatus && $hasDataStatus) {
            $pdo->exec(
                "UPDATE medical_examinations
                 SET record_status = CASE
                     WHEN data_status = 'verified' THEN 'submitted'
                     WHEN data_status = 'completed' THEN 'completed'
                     WHEN data_status = 'draft' THEN 'draft'
                     ELSE 'draft'
                 END
                 WHERE record_status IS NULL OR record_status = ''"
            );
        }

        if ($hasCurrentContainer) {
            $pdo->exec(
                "UPDATE medical_examinations
                 SET current_container = CASE
                     WHEN record_status IN ('completed', 'submitted') THEN 8
                     WHEN current_container IS NULL OR current_container < 1 THEN 1
                     WHEN current_container > 8 THEN 8
                     ELSE current_container
                 END"
            );
        }
    } catch (Exception $e) {
        // Log the error but don't fail the request
        error_log('Error ensuring status columns: ' . $e->getMessage());
    }
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clinic;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensure_status_columns($pdo);

    $clims_id = null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $clims_id = $_POST['clims_id'] ?? null;
    } else {
        $clims_id = $_GET['clims_id'] ?? null;
    }

    if (!$clims_id) {
        throw new Exception('CLIMS ID is required');
    }

    $clims_id = trim((string)$clims_id);
    $is_test_mode = ($clims_id === DUMMY_CLIMS_ID);
    $created_new = false;

    if ($is_test_mode) {
        $stmt = $pdo->prepare('SELECT * FROM medical_examinations WHERE clims_id = ?');
        $stmt->execute([$clims_id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $stmt = $pdo->prepare(
                "INSERT INTO medical_examinations (clims_id, full_name, age_sex, address, mobile_no, record_status, current_container, created_at)
                 VALUES (?, 'Test User', '30/M', 'Test Address, NTPC Dadri', '9999999999', 'draft', 1, NOW())"
            );
            $stmt->execute([$clims_id]);
            $created_new = true;
        }
    }

    $stmt = $pdo->prepare('SELECT * FROM medical_examinations WHERE clims_id = ?');
    $stmt->execute([$clims_id]);
    $data = $stmt->fetch();

    if (!$data) {
        $stmt = $pdo->prepare(
            "INSERT INTO medical_examinations (clims_id, record_status, current_container, created_at)
             VALUES (?, 'draft', 1, NOW())"
        );
        $stmt->execute([$clims_id]);
        $created_new = true;

        $stmt = $pdo->prepare('SELECT * FROM medical_examinations WHERE clims_id = ?');
        $stmt->execute([$clims_id]);
        $data = $stmt->fetch();
    }

    if (!$data) {
        throw new Exception('Failed to create or retrieve record');
    }

    $id = (int)($data['id'] ?? 0);
    $record_status = $data['record_status'] ?? 'draft';
    $current_container = (int)($data['current_container'] ?? 1);

    // Remove internal fields from response data
    unset($data['id']);

    $response = [
        'success' => true,
        'message' => $is_test_mode ? 'TEST MODE: Using dummy CLIMS ID' : 'Record found successfully',
        'found' => !$created_new,
        'created_new' => $created_new,
        'id' => $id,
        'data' => $data,
        'is_test_mode' => $is_test_mode,
        'clims_id' => $clims_id,
        'record_status' => $record_status,
        'current_container' => $current_container,
    ];

    send_json($response);
} catch (Exception $e) {
    error_log('ajax/get_patient_data.php Error: ' . $e->getMessage());
    send_json([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => null,
        'is_test_mode' => false,
    ]);
}
