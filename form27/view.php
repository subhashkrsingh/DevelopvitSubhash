<?php
require_once __DIR__ . '/../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Invalid record ID.");
}

try {
    $pdo = pdo();
    $stmt = $pdo->prepare("SELECT * FROM form27 WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        die("Record not found.");
    }
} catch (Throwable $e) {
    die("Error loading record.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 27 - View Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .view-section {
            background: #f8fafc;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .view-label {
            font-weight: 700;
            color: #4f46e5;
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .view-value {
            font-size: 1rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .result-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
        }
        .result-fit { background: #d4edda; color: #155724; }
        .result-unfit { background: #f8d7da; color: #721c24; }
        .result-temp { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
<div class="page-shell" style="max-width: 1000px;">
    <div class="page-head">
        <h1><i class="fa-solid fa-file-alt me-2"></i>Form 27 - Health Register</h1>
        <p>View Record Details</p>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <a href="print.php?id=<?php echo $id; ?>" class="btn btn-info" target="_blank">
            <i class="fas fa-print me-2"></i>Print
        </a>
        <a href="view_records.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <!-- Section 1: Basic Information -->
    <div class="view-section">
        <h4 class="mb-3"><i class="fas fa-user me-2 text-primary"></i>Basic Information</h4>
        <div class="row">
            <div class="col-md-6">
                <div class="view-label">Serial Number</div>
                <div class="view-value"><?php echo htmlspecialchars($record['serial_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="view-label">Department</div>
                <div class="view-value"><?php echo htmlspecialchars($record['department'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="view-label">Name of Worker</div>
                <div class="view-value"><?php echo htmlspecialchars($record['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-3">
                <div class="view-label">Sex</div>
                <div class="view-value"><?php echo htmlspecialchars($record['sex'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-3">
                <div class="view-label">Age</div>
                <div class="view-value"><?php echo $record['age'] ?? 'N/A'; ?></div>
            </div>
            <div class="col-md-6">
                <div class="view-label">Date of Employment</div>
                <div class="view-value"><?php echo htmlspecialchars($record['start_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="view-label">Occupation</div>
                <div class="view-value"><?php echo htmlspecialchars($record['occupation'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-12">
                <div class="view-label">Leave/Transfer Details</div>
                <div class="view-value"><?php echo nl2br(htmlspecialchars($record['leave_transfer'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <div class="col-12">
                <div class="view-label">Raw Materials Exposed To</div>
                <div class="view-value"><?php echo nl2br(htmlspecialchars($record['raw_materials'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
        </div>
    </div>

    <!-- Section 2: Medical Examination Details -->
    <div class="view-section">
        <h4 class="mb-3"><i class="fas fa-stethoscope me-2 text-primary"></i>Medical Examination Details</h4>
        <div class="row">
            <div class="col-md-4">
                <div class="view-label">Examination Date</div>
                <div class="view-value"><?php echo htmlspecialchars($record['exam_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-4">
                <div class="view-label">Result</div>
                <div class="view-value">
                    <?php
                    $result = $record['result'] ?? '';
                    $class = '';
                    if ($result == 'Fit') $class = 'result-fit';
                    elseif ($result == 'Unfit') $class = 'result-unfit';
                    elseif ($result == 'Temporarily Unfit') $class = 'result-temp';
                    ?>
                    <span class="result-badge <?php echo $class; ?>"><?php echo htmlspecialchars($result ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="view-label">Re-certified Date</div>
                <div class="view-value"><?php echo htmlspecialchars($record['recertified_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-12">
                <div class="view-label">Signs and Symptoms</div>
                <div class="view-value"><?php echo nl2br(htmlspecialchars($record['signs_symptoms'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <div class="col-12">
                <div class="view-label">Tests and Results</div>
                <div class="view-value"><?php echo nl2br(htmlspecialchars($record['tests_result'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <div class="col-12">
                <div class="view-label">Suspension Details</div>
                <div class="view-value"><?php echo nl2br(htmlspecialchars($record['suspension_details'] ?? 'N/A', ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <div class="col-md-6">
                <div class="view-label">Certificate Issued</div>
                <div class="view-value"><?php echo htmlspecialchars($record['certificate_issued'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    </div>

    <!-- Section 3: Signature -->
    <div class="view-section">
        <h4 class="mb-3"><i class="fas fa-signature me-2 text-primary"></i>Certification</h4>
        <div class="row">
            <div class="col-12">
                <div class="view-label">Signature of Certifying Surgeon</div>
                <div class="view-value signature-elegant" style="border: 2px dashed #a5b4fc; padding: 15px; border-radius: 20px;">
                    <?php echo htmlspecialchars($record['surgeon_signature'] ?? 'Not Signed', ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3 no-print">
        <a href="view_records.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>
</body>
</html>