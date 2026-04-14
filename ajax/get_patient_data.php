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

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableSafe = $conn->real_escape_string($table);
    $columnSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
    $res = $conn->query($sql);
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

function ensure_status_columns(mysqli $conn): void
{
    $hasRecordStatus = column_exists($conn, 'medical_examinations', 'record_status');
    $hasCurrentContainer = column_exists($conn, 'medical_examinations', 'current_container');
    $hasDataStatus = column_exists($conn, 'medical_examinations', 'data_status');

    if (!$hasRecordStatus) {
        $conn->query(
            "ALTER TABLE medical_examinations
             ADD COLUMN record_status ENUM('draft','partial','completed','submitted') DEFAULT 'draft'"
        );
        $hasRecordStatus = true;
    }

    if (!$hasCurrentContainer) {
        $conn->query(
            'ALTER TABLE medical_examinations
             ADD COLUMN current_container INT DEFAULT 1'
        );
        $hasCurrentContainer = true;
    }

    if ($hasRecordStatus && $hasDataStatus) {
        $conn->query(
            "UPDATE medical_examinations
             SET record_status = CASE
                 WHEN data_status = 'verified' THEN 'submitted'
                 WHEN data_status = 'completed' THEN 'completed'
                 WHEN data_status = 'draft' THEN 'draft'
                 ELSE COALESCE(record_status, 'draft')
             END
             WHERE record_status IS NULL OR record_status = '' OR record_status = 'draft'"
        );
    }

    if ($hasCurrentContainer) {
        $conn->query(
            "UPDATE medical_examinations
             SET current_container = CASE
                 WHEN record_status IN ('completed', 'submitted') THEN 8
                 WHEN current_container IS NULL OR current_container < 1 THEN 1
                 WHEN current_container > 8 THEN 8
                 ELSE current_container
             END"
        );
    }
}

try {
    $conn = new mysqli('localhost', 'root', '', 'clinic');

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    ensure_status_columns($conn);

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
        $check_stmt = $conn->prepare('SELECT * FROM medical_examinations WHERE clims_id = ?');
        if (!$check_stmt) {
            throw new Exception('Prepare failed while checking dummy record: ' . $conn->error);
        }

        $check_stmt->bind_param('s', $clims_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $insert_stmt = $conn->prepare(
                "INSERT INTO medical_examinations (clims_id, full_name, age_sex, address, mobile_no, record_status, current_container, created_at)
                 VALUES (?, 'Test User', '30/M', 'Test Address, NTPC Dadri', '9999999999', 'draft', 1, NOW())"
            );

            if (!$insert_stmt) {
                throw new Exception('Prepare failed while creating dummy record: ' . $conn->error);
            }

            $insert_stmt->bind_param('s', $clims_id);
            $insert_stmt->execute();
            $insert_stmt->close();
            $created_new = true;
        }

        $check_stmt->close();
    }

    $stmt = $conn->prepare('SELECT * FROM medical_examinations WHERE clims_id = ?');
    if (!$stmt) {
        throw new Exception('Prepare failed while fetching record: ' . $conn->error);
    }

    $stmt->bind_param('s', $clims_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $insert_stmt = $conn->prepare(
            "INSERT INTO medical_examinations (clims_id, record_status, current_container, created_at)
             VALUES (?, 'draft', 1, NOW())"
        );

        if (!$insert_stmt) {
            throw new Exception('Prepare failed while creating new record: ' . $conn->error);
        }

        $insert_stmt->bind_param('s', $clims_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        $created_new = true;

        $stmt->close();
        $stmt = $conn->prepare('SELECT * FROM medical_examinations WHERE clims_id = ?');
        if (!$stmt) {
            throw new Exception('Prepare failed while refetching record: ' . $conn->error);
        }

        $stmt->bind_param('s', $clims_id);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $data = $result->fetch_assoc();
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $record_status = isset($data['record_status']) && $data['record_status'] !== '' ? $data['record_status'] : 'draft';
    $current_container = isset($data['current_container']) ? (int)$data['current_container'] : 1;

    if (is_array($data)) {
        unset($data['id']);
    }

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
        'message' => $e->getMessage(),
        'data' => null,
        'is_test_mode' => false,
    ]);
}
