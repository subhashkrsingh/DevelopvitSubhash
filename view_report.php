<?php
require_once __DIR__ . '/config.php';

session_start();

// Get parameters
$examination_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_SESSION['last_examination_id']) ? (int)$_SESSION['last_examination_id'] : 0);
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

try {
    $pdo = pdo();
    
    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(m.full_name LIKE :search OR m.clims_id LIKE :search OR m.serial_no LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($date_from)) {
        $conditions[] = "DATE(m.created_at) >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $conditions[] = "DATE(m.created_at) <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // Get all records
    $sql = "SELECT 
                m.id, m.serial_no, m.clims_id, m.full_name, m.age_sex, m.address, 
                m.mobile_no, m.exam_date, m.blood_group, m.opinion, m.created_at,
                f26.serial_number as form26_serial, f26.fitness_status, f26.surgeon_signature as form26_signature,
                f27.id as form27_id, f27.department, f27.result as health_result, f27.exam_date as health_exam_date
            FROM medical_examinations m
            LEFT JOIN form26 f26 ON m.id = f26.examination_id
            LEFT JOIN form27 f27 ON m.id = f27.examination_id
            $whereClause
            ORDER BY m.id DESC";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $records = $stmt->fetchAll();
    
    // Get single record if ID specified
    $single_record = null;
    if ($examination_id > 0) {
        $single_sql = "SELECT 
                        m.*,
                        f26.serial_number as form26_serial, f26.father_name, f26.employment_details, 
                        f26.designation as form26_designation, f26.fitness_status, f26.further_exam_period,
                        f26.previous_certificate_no, f26.surgeon_signature as form26_signature,
                        f27.serial_number as form27_serial, f27.department, f27.sex, f27.age as form27_age,
                        f27.start_date, f27.leave_transfer, f27.occupation, f27.raw_materials,
                        f27.exam_date as health_exam_date, f27.result as health_result,
                        f27.signs_symptoms, f27.tests_result, f27.suspension_details,
                        f27.certificate_issued, f27.recertified_date, f27.surgeon_signature as form27_signature
                    FROM medical_examinations m
                    LEFT JOIN form26 f26 ON m.id = f26.examination_id
                    LEFT JOIN form27 f27 ON m.id = f27.examination_id
                    WHERE m.id = :id";
        $single_stmt = $pdo->prepare($single_sql);
        $single_stmt->execute([':id' => $examination_id]);
        $single_record = $single_stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Throwable $e) {
    $error = APP_DEBUG ? $e->getMessage() : 'Unable to load records.';
    $records = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLIMS - Complete View Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .report-header {
            background: linear-gradient(135deg, #4f46e5, #a855f7);
            color: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
        }
        .stats-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .stats-number {
            font-size: 28px;
            font-weight: 800;
            color: #4f46e5;
        }
        .fitness-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .fitness-fit { background: #d4edda; color: #155724; }
        .fitness-unfit { background: #f8d7da; color: #721c24; }
        .fitness-conditional { background: #fff3cd; color: #856404; }
        .search-filters {
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
        }
        .nav-tabs .nav-link {
            color: #4f46e5;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #4f46e5, #a855f7);
            color: white;
            border: none;
        }
        .detail-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-label {
            font-weight: 700;
            color: #4f46e5;
            font-size: 0.75rem;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 0.9rem;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .btn-print {
            background: linear-gradient(105deg, #4f46e5, #9333ea);
            border: none;
            border-radius: 40px;
            padding: 10px 25px;
            color: white;
            font-weight: 600;
        }
        .btn-excel {
            background: linear-gradient(105deg, #10b981, #059669);
            border: none;
            border-radius: 40px;
            padding: 10px 25px;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="page-shell" style="max-width: 1400px;">
    <div class="report-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-chart-line me-2"></i>CLIMS - Complete View Report</h1>
                <p class="mb-0 opacity-75">Medical Examination, Form 26 & Form 27 Combined Report</p>
            </div>
            <div>
                <button class="btn-print me-2" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
                <button class="btn-excel" id="exportExcelBtn">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="search-filters">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" placeholder="Search by Name, CLIMS ID, Serial No..." 
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <div class="stats-number"><?php echo count($records); ?></div>
                <div class="text-muted">Total Records</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <div class="stats-number">
                    <?php 
                    $fitCount = 0;
                    foreach ($records as $r) {
                        if ($r['fitness_status'] == 'FIT') $fitCount++;
                    }
                    echo $fitCount;
                    ?>
                </div>
                <div class="text-muted">Fit for Work</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                <div class="stats-number">
                    <?php 
                    $unfitCount = 0;
                    foreach ($records as $r) {
                        if ($r['fitness_status'] == 'UNFIT') $unfitCount++;
                    }
                    echo $unfitCount;
                    ?>
                </div>
                <div class="text-muted">Unfit for Work</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-file-alt fa-2x text-info mb-2"></i>
                <div class="stats-number">
                    <?php 
                    $form27Count = 0;
                    foreach ($records as $r) {
                        if ($r['form27_id']) $form27Count++;
                    }
                    echo $form27Count;
                    ?>
                </div>
                <div class="text-muted">Health Registers Completed</div>
            </div>
        </div>
    </div>

    <!-- Tabs for List and Detail View -->
    <ul class="nav nav-tabs mb-4" id="reportTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo !$single_record ? 'active' : ''; ?>" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" type="button" role="tab">
                <i class="fas fa-list me-2"></i>List View
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $single_record ? 'active' : ''; ?>" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail-view" type="button" role="tab">
                <i class="fas fa-file-alt me-2"></i>Detailed View
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- List View Tab -->
        <div class="tab-pane fade <?php echo !$single_record ? 'show active' : ''; ?>" id="list-view" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>CLIMS ID</th>
                            <th>Name</th>
                            <th>Age/Sex</th>
                            <th>Exam Date</th>
                            <th>Blood Group</th>
                            <th>Medical Opinion</th>
                            <th>Fitness Status</th>
                            <th>Health Result</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4">No records found.<?php echo isset($error) ? ' ' . $error : ''; ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo $record['id']; ?></td>
                                    <td><code><?php echo htmlspecialchars($record['clims_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($record['full_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($record['age_sex'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($record['exam_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($record['blood_group'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $opinion = $record['opinion'] ?? '';
                                            echo $opinion == 'MEDICALLY FIT' ? 'success' : ($opinion == 'UNFIT' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo htmlspecialchars($opinion ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fitness-badge <?php 
                                            $status = $record['fitness_status'] ?? '';
                                            echo $status == 'FIT' ? 'fitness-fit' : ($status == 'UNFIT' ? 'fitness-unfit' : 'fitness-conditional'); 
                                        ?>">
                                            <?php echo htmlspecialchars($status ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fitness-badge <?php 
                                            $result = $record['health_result'] ?? '';
                                            echo $result == 'Fit' ? 'fitness-fit' : ($result == 'Unfit' ? 'fitness-unfit' : 'fitness-conditional'); 
                                        ?>">
                                            <?php echo htmlspecialchars($result ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($record['created_at'])); ?></td>
                                    <td>
                                        <a href="view_report.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="26/view_form26.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary" title="View Form 26">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        <a href="27/print.php?id=<?php echo $record['form27_id']; ?>" class="btn btn-sm btn-secondary" title="Print Form 27" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detailed View Tab -->
        <div class="tab-pane fade <?php echo $single_record ? 'show active' : ''; ?>" id="detail-view" role="tabpanel">
            <?php if ($single_record): ?>
                <div class="d-flex justify-content-end mb-3 gap-2">
                    <a href="26/view_form26.php?id=<?php echo $examination_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-file-alt me-1"></i>View Form 26
                    </a>
                    <a href="27/print.php?id=<?php echo $single_record['form27_id']; ?>" class="btn btn-info btn-sm" target="_blank">
                        <i class="fas fa-print me-1"></i>Print Form 27
                    </a>
                    <a href="view_report.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to List
                    </a>
                </div>

                <!-- Medical Examination Details -->
                <div class="detail-section">
                    <h4><i class="fas fa-stethoscope me-2 text-primary"></i>Medical Examination Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Serial Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['serial_no'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">CLIMS ID</div>
                            <div class="detail-value"><code><?php echo htmlspecialchars($single_record['clims_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></code></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['full_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-label">Age & Sex</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['age_sex'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-label">Mobile Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['mobile_no'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="detail-label">Address</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_record['address'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Examination Date</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['exam_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Blood Group</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['blood_group'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Medical Opinion</div>
                            <div class="detail-value">
                                <span class="badge bg-<?php 
                                    $opinion = $single_record['opinion'] ?? '';
                                    echo $opinion == 'MEDICALLY FIT' ? 'success' : ($opinion == 'UNFIT' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo htmlspecialchars($opinion ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form 26 Details -->
                <div class="detail-section">
                    <h4><i class="fas fa-file-certificate me-2 text-primary"></i>Form 26 - Certificate of Fitness</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Form 26 Serial Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['form26_serial'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Father's/Husband's Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['father_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Employment Details</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['employment_details'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Designation</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['form26_designation'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Fitness Status</div>
                            <div class="detail-value">
                                <span class="fitness-badge <?php 
                                    $status = $single_record['fitness_status'] ?? '';
                                    echo $status == 'FIT' ? 'fitness-fit' : ($status == 'UNFIT' ? 'fitness-unfit' : 'fitness-conditional'); 
                                ?>">
                                    <?php echo htmlspecialchars($status ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Further Examination Period</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['further_exam_period'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="detail-label">Certifying Surgeon Signature</div>
                            <div class="detail-value signature-elegant" style="border: 1px dashed #a5b4fc; padding: 10px; border-radius: 12px;">
                                <?php echo htmlspecialchars($single_record['form26_signature'] ?? 'Not Signed', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form 27 Details -->
                <div class="detail-section">
                    <h4><i class="fas fa-notes-medical me-2 text-primary"></i>Form 27 - Health Register</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Form 27 Serial Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['form27_serial'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Department</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['department'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-label">Sex</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['sex'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="detail-label">Age (Form 27)</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['form27_age'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Date of Employment</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['start_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Occupation</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['occupation'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Leave/Transfer Details</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_record['leave_transfer'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Raw Materials Exposed To</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_record['raw_materials'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Health Examination Date</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['health_exam_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Health Result</div>
                            <div class="detail-value">
                                <span class="fitness-badge <?php 
                                    $result = $single_record['health_result'] ?? '';
                                    echo $result == 'Fit' ? 'fitness-fit' : ($result == 'Unfit' ? 'fitness-unfit' : 'fitness-conditional'); 
                                ?>">
                                    <?php echo htmlspecialchars($result ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Re-certified Date</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['recertified_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Signs and Symptoms</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_record['signs_symptoms'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Tests and Results</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_record['tests_result'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Suspension Details</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($single_record['suspension_details'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Certificate Issued</div>
                            <div class="detail-value"><?php echo htmlspecialchars($single_record['certificate_issued'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="detail-label">Certifying Surgeon Signature (Form 27)</div>
                            <div class="detail-value signature-elegant" style="border: 1px dashed #a5b4fc; padding: 10px; border-radius: 12px;">
                                <?php echo htmlspecialchars($single_record['form27_signature'] ?? 'Not Signed', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p class="text-muted">Record created on: <?php echo date('d-m-Y H:i:s', strtotime($single_record['created_at'])); ?></p>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Select a record from the List View or click "View Details" to see complete report.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('exportExcelBtn')?.addEventListener('click', function() {
    const table = document.querySelector('#list-view table');
    const html = table.outerHTML;
    const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    const link = document.createElement('a');
    link.download = 'clims_report.xls';
    link.href = url;
    link.click();
});
</script>
</body>
</html>