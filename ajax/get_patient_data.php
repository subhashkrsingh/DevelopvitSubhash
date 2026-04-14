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

try {
    $conn = new mysqli('localhost', 'root', '', 'clinic');

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

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
