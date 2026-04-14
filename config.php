<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (PHP_SAPI !== 'cli' && ob_get_level() === 0) {
    ob_start();
}

date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'clinic');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_DEBUG', true);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('WORKER_UPLOAD_DIR', __DIR__ . '/uploads/workers');
define('WORKER_UPLOAD_WEB_PATH', 'uploads/workers');
define('DUMMY_CLIMS_ID', 'CLIMS-NTPC-2026-000');

if (!is_dir(WORKER_UPLOAD_DIR)) {
    @mkdir(WORKER_UPLOAD_DIR, 0775, true);
}

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : 0);

function pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        ensure_medical_examinations_schema($pdo);
    } catch (Throwable $e) {
        if (PHP_SAPI === 'cli') {
            throw $e;
        }
        json_response(false, 'Database connection failed. Please check DB settings.', [
            'error' => APP_DEBUG ? $e->getMessage() : null,
        ], 500);
    }

    return $pdo;
}

function ensure_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token(): string
{
    return ensure_csrf_token();
}

function verify_csrf_or_exit(?string $token): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($sessionToken, $token)) {
        json_response(false, 'Invalid CSRF token.', [], 419);
    }
}

function json_response(bool $success, string $message, array $extra = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');

    $payload = array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra);

    if (ob_get_length()) {
        ob_clean();
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    echo remove_bom($json === false ? '{}' : $json);
    exit;
}

function remove_bom(string $data): string
{
    return preg_replace('/^\xEF\xBB\xBF/', '', $data) ?? $data;
}

function normalize_string($value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function has_value($value): bool
{
    return $value !== null && trim((string)$value) !== '';
}

function generate_serial_no(): string
{
    return sprintf(
        'CLIMS/%s/%s/%04d',
        date('Y'),
        date('md'),
        random_int(1000, 9999)
    );
}

function is_test_clims_id(string $climsId): bool
{
    $normalized = strtoupper(trim($climsId));
    if ($normalized === strtoupper(DUMMY_CLIMS_ID)) {
        return true;
    }
    return strpos($normalized, 'DUMMY') !== false;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema_name AND TABLE_NAME = :table_name LIMIT 1'
    );
    $stmt->execute([
        ':schema_name' => DB_NAME,
        ':table_name' => $table,
    ]);
    return (bool)$stmt->fetchColumn();
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :schema_name AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $stmt->execute([
        ':schema_name' => DB_NAME,
        ':table_name' => $table,
        ':column_name' => $column,
    ]);
    return (bool)$stmt->fetchColumn();
}

function ensure_medical_examinations_schema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `medical_examinations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `clims_id` VARCHAR(100) UNIQUE,
            `serial_no` VARCHAR(100),
            `attested_by_eic` VARCHAR(255),
            `exam_date` DATE,
            `full_name` VARCHAR(255),
            `age_sex` VARCHAR(50),
            `aadhar_no` VARCHAR(50),
            `address` TEXT,
            `mobile_no` VARCHAR(20),
            `demo_exam_date` DATE,
            `contractor_agency` VARCHAR(255),
            `ntpc_eic` VARCHAR(255),
            `diabetes` VARCHAR(10),
            `hypertension` VARCHAR(10),
            `vertigo` VARCHAR(10),
            `epilepsy` VARCHAR(10),
            `height_phobia` VARCHAR(10),
            `skin_diseases` VARCHAR(10),
            `asthma` VARCHAR(10),
            `alcohol_intake` VARCHAR(10),
            `mental_illness` VARCHAR(10),
            `tobacco_chewing` VARCHAR(10),
            `cancer` VARCHAR(10),
            `piles` VARCHAR(10),
            `hearing_problem` VARCHAR(10),
            `chronic_illness` VARCHAR(10),
            `deformity` VARCHAR(10),
            `past_accident` VARCHAR(10),
            `smoking` VARCHAR(10),
            `medicine_history` TEXT,
            `contractor_signature` VARCHAR(255),
            `height` DECIMAL(10,2),
            `weight` DECIMAL(10,2),
            `bp` VARCHAR(50),
            `bmi` VARCHAR(20),
            `chest_insp` VARCHAR(50),
            `chest_exp` VARCHAR(50),
            `pulse_spo2_temp` VARCHAR(100),
            `pallor` VARCHAR(20),
            `icterus` VARCHAR(20),
            `clubbing` VARCHAR(20),
            `built` VARCHAR(20),
            `tongue` VARCHAR(100),
            `teeth` VARCHAR(100),
            `other_finding` TEXT,
            `cardio_system` TEXT,
            `respiratory_system` TEXT,
            `cns` TEXT,
            `system_other` TEXT,
            `distant_r_with` VARCHAR(20),
            `distant_r_without` VARCHAR(20),
            `distant_l_with` VARCHAR(20),
            `distant_l_without` VARCHAR(20),
            `near_r_with` VARCHAR(20),
            `near_r_without` VARCHAR(20),
            `near_l_with` VARCHAR(20),
            `near_l_without` VARCHAR(20),
            `colour_vision` VARCHAR(100),
            `eye_disorder` TEXT,
            `lmp` VARCHAR(50),
            `menstrual_cycle` VARCHAR(50),
            `pregnancy_duration` VARCHAR(50),
            `cbc` VARCHAR(255),
            `random_blood_sugar` VARCHAR(50),
            `urine_rm` VARCHAR(255),
            `blood_group` VARCHAR(10),
            `lft_kft` VARCHAR(255),
            `ecg` VARCHAR(20),
            `chest_xray` VARCHAR(20),
            `height_pass_test` VARCHAR(20),
            `other_tests` TEXT,
            `opinion` VARCHAR(100),
            `remarks` TEXT,
            `worker_signature` VARCHAR(255),
            `doctor_signature` VARCHAR(255),
            `worker_photo` VARCHAR(500),
            `record_status` ENUM('draft','partial','completed','submitted') DEFAULT 'draft',
            `current_container` INT DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $checked = true;
}

function save_uploaded_photo(string $inputName = 'worker_photo'): ?string
{
    if (!isset($_FILES[$inputName])) {
        return null;
    }

    $file = $_FILES[$inputName];
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Photo upload failed with error code: ' . (int)$file['error']);
    }

    if ((int)($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('Photo size exceeds 5MB limit.');
    }

    $originalName = (string)($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Only JPG, JPEG, PNG, and WEBP image files are allowed.');
    }

    $newName = sprintf('worker_%s_%s.%s', date('YmdHis'), bin2hex(random_bytes(5)), $extension);
    $targetAbs = WORKER_UPLOAD_DIR . '/' . $newName;

    if (!move_uploaded_file((string)$file['tmp_name'], $targetAbs)) {
        throw new RuntimeException('Unable to save uploaded photo.');
    }

    return WORKER_UPLOAD_WEB_PATH . '/' . $newName;
}

function get_container_fields(): array
{
    return [
        1 => [
            'serial_no', 'attested_by_eic', 'exam_date', 'full_name', 'age_sex', 'aadhar_no',
            'address', 'mobile_no', 'demo_exam_date', 'contractor_agency', 'clims_id', 'ntpc_eic', 'worker_photo',
        ],
        2 => [
            'diabetes', 'hypertension', 'vertigo', 'epilepsy', 'height_phobia', 'skin_diseases',
            'asthma', 'alcohol_intake', 'mental_illness', 'tobacco_chewing', 'cancer', 'piles',
            'hearing_problem', 'chronic_illness', 'deformity', 'past_accident', 'smoking', 'medicine_history',
            'contractor_signature',
        ],
        3 => [
            'height', 'weight', 'bp', 'bmi', 'chest_insp', 'chest_exp', 'pulse_spo2_temp',
            'pallor', 'icterus', 'clubbing', 'built', 'tongue', 'teeth', 'other_finding',
        ],
        4 => ['cardio_system', 'respiratory_system', 'cns', 'system_other'],
        5 => [
            'distant_r_with', 'distant_r_without', 'distant_l_with', 'distant_l_without',
            'near_r_with', 'near_r_without', 'near_l_with', 'near_l_without',
            'colour_vision', 'eye_disorder',
        ],
        6 => ['lmp', 'menstrual_cycle', 'pregnancy_duration'],
        7 => [
            'cbc', 'random_blood_sugar', 'urine_rm', 'blood_group', 'lft_kft', 'ecg',
            'chest_xray', 'height_pass_test', 'other_tests',
        ],
        8 => ['opinion', 'remarks', 'worker_signature', 'doctor_signature'],
    ];
}

function get_medical_examination_by_clims(PDO $pdo, string $climsId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM medical_examinations WHERE clims_id = :clims_id LIMIT 1');
    $stmt->execute([':clims_id' => $climsId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_medical_examination_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM medical_examinations WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function normalize_record_status(?string $status): string
{
    $normalized = strtolower(trim((string)$status));
    if ($normalized === 'submitted') {
        return 'submitted';
    }
    if ($normalized === 'completed') {
        return 'completed';
    }
    if ($normalized === 'partial') {
        return 'partial';
    }
    if ($normalized === 'verified') {
        return 'submitted';
    }
    return 'draft';
}

function get_record_status_from_row(array $row): string
{
    if (array_key_exists('record_status', $row)) {
        return normalize_record_status((string)$row['record_status']);
    }

    if (array_key_exists('data_status', $row)) {
        return normalize_record_status((string)$row['data_status']);
    }

    return 'draft';
}

function calculate_last_completed_container(array $row): int
{
    $status = get_record_status_from_row($row);
    if ($status === 'submitted' || $status === 'completed') {
        return 8;
    }

    $containerFields = get_container_fields();
    $last = 0;

    for ($i = 1; $i <= 8; $i++) {
        if ($i === 1) {
            if (has_value($row['full_name'] ?? null) && has_value($row['clims_id'] ?? null)) {
                $last = 1;
                continue;
            }
            break;
        }

        $hasAnyValue = false;
        foreach ($containerFields[$i] as $field) {
            if (has_value($row[$field] ?? null)) {
                $hasAnyValue = true;
                break;
            }
        }

        if (!$hasAnyValue) {
            break;
        }

        $last = $i;
    }

    return $last;
}

function derive_progress_state(array $row): array
{
    $lastCompleted = calculate_last_completed_container($row);
    $status = get_record_status_from_row($row);

    if ($status !== 'submitted') {
        if ($lastCompleted >= 8) {
            $status = 'completed';
        } elseif ($lastCompleted >= 1) {
            $status = 'partial';
        } else {
            $status = 'draft';
        }
    }

    $currentContainer = 1;
    if ($status === 'submitted' || $status === 'completed') {
        $currentContainer = 8;
    } else {
        $currentContainer = min($lastCompleted + 1, 8);
    }

    if (isset($row['current_container']) && is_numeric((string)$row['current_container'])) {
        $rowContainer = (int)$row['current_container'];
        if ($rowContainer >= 1 && $rowContainer <= 8 && $status !== 'submitted' && $status !== 'completed') {
            $currentContainer = max($currentContainer, $rowContainer);
        }
    }

    return [
        'record_status' => $status,
        'last_completed_container' => $lastCompleted,
        'current_container' => $currentContainer,
    ];
}

ensure_csrf_token();
?>