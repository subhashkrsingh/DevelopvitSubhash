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

    if (!table_exists($pdo, 'medical_examinations')) {
        $checked = true;
        return;
    }

    $hasRecordStatus = column_exists($pdo, 'medical_examinations', 'record_status');
    $hasCurrentContainer = column_exists($pdo, 'medical_examinations', 'current_container');
    $hasDataStatus = column_exists($pdo, 'medical_examinations', 'data_status');

    if (!$hasRecordStatus) {
        $pdo->exec(
            "ALTER TABLE medical_examinations
             ADD COLUMN record_status ENUM('draft','partial','completed','submitted') DEFAULT 'draft'"
        );
        $hasRecordStatus = true;
    }

    if (!$hasCurrentContainer) {
        $pdo->exec(
            'ALTER TABLE medical_examinations
             ADD COLUMN current_container INT DEFAULT 1'
        );
        $hasCurrentContainer = true;
    }

    if ($hasRecordStatus && $hasDataStatus) {
        $pdo->exec(
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

function extract_age(?string $ageSex): ?int
{
    if (!$ageSex) {
        return null;
    }

    if (preg_match('/(\d{1,3})/', $ageSex, $matches)) {
        return (int)$matches[1];
    }

    return null;
}

function extract_sex(?string $ageSex): ?string
{
    if (!$ageSex) {
        return null;
    }

    if (preg_match('/\b(M|F|Male|Female|Other)\b/i', $ageSex, $matches)) {
        $sex = strtolower($matches[1]);
        if ($sex === 'm' || $sex === 'male') {
            return 'Male';
        }
        if ($sex === 'f' || $sex === 'female') {
            return 'Female';
        }
        return 'Other';
    }

    return null;
}

ensure_csrf_token();

