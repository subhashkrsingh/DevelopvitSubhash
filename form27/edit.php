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

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 27 - Edit Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .form-section {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .form-section h4 {
            color: #4f46e5;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-card {
            background: white;
            padding: 30px 50px;
            border-radius: 30px;
            text-align: center;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }
        .signature-elegant {
            border: 2px dashed #a5b4fc;
            background: linear-gradient(120deg, #fef9ff, #ffffff);
            border-radius: 20px;
            padding: 12px 18px;
        }
    </style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-card">
        <div class="spinner-border text-primary" role="status"></div>
        <p>Saving changes...</p>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<div id="alertContainer"></div>

<div class="page-shell" style="max-width: 1200px;">
    <div class="page-head">
        <h1><i class="fa-solid fa-edit me-2"></i>Form 27 - Edit Record</h1>
        <p>Update Health Register Information</p>
    </div>

    <form id="editForm" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="record_id" value="<?php echo $id; ?>">
        <input type="hidden" name="examination_id" value="<?php echo $record['examination_id']; ?>">

        <!-- Section 1: Basic Information -->
        <div class="form-section">
            <h4><i class="fas fa-user me-2"></i> Basic Information</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-control" name="serial_number" 
                           value="<?php echo htmlspecialchars($record['serial_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" name="department" 
                           value="<?php echo htmlspecialchars($record['department'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Name of Worker <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" 
                           value="<?php echo htmlspecialchars($record['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sex</label>
                    <input type="text" class="form-control" name="sex" 
                           value="<?php echo htmlspecialchars($record['sex'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age</label>
                    <input type="number" class="form-control" name="age" 
                           value="<?php echo $record['age'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Employment</label>
                    <input type="date" class="form-control" name="start_date" 
                           value="<?php echo htmlspecialchars($record['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="occupation" 
                           value="<?php echo htmlspecialchars($record['occupation'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Leave/Transfer Details</label>
                    <textarea class="form-control" name="leave_transfer" rows="2"><?php echo htmlspecialchars($record['leave_transfer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Raw Materials Exposed To</label>
                    <textarea class="form-control" name="raw_materials" rows="2"><?php echo htmlspecialchars($record['raw_materials'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Medical Examination Details -->
        <div class="form-section">
            <h4><i class="fas fa-stethoscope me-2"></i> Medical Examination Details</h4>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Examination Date</label>
                    <input type="date" class="form-control" name="exam_date" 
                           value="<?php echo htmlspecialchars($record['exam_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Result</label>
                    <select class="form-select" name="result">
                        <option value="">Select Result</option>
                        <option value="Fit" <?php echo ($record['result'] == 'Fit') ? 'selected' : ''; ?>>Fit</option>
                        <option value="Unfit" <?php echo ($record['result'] == 'Unfit') ? 'selected' : ''; ?>>Unfit</option>
                        <option value="Temporarily Unfit" <?php echo ($record['result'] == 'Temporarily Unfit') ? 'selected' : ''; ?>>Temporarily Unfit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Re-certified Date</label>
                    <input type="date" class="form-control" name="recertified_date" 
                           value="<?php echo htmlspecialchars($record['recertified_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Signs and Symptoms</label>
                    <textarea class="form-control" name="signs_symptoms" rows="2"><?php echo htmlspecialchars($record['signs_symptoms'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Tests and Results</label>
                    <textarea class="form-control" name="tests_result" rows="2"><?php echo htmlspecialchars($record['tests_result'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Suspension Details</label>
                    <textarea class="form-control" name="suspension_details" rows="2"><?php echo htmlspecialchars($record['suspension_details'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Certificate Issued</label>
                    <input type="text" class="form-control" name="certificate_issued" 
                           value="<?php echo htmlspecialchars($record['certificate_issued'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>

        <!-- Section 3: Signature -->
        <div class="form-section">
            <h4><i class="fas fa-signature me-2"></i> Certification</h4>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Signature of Certifying Surgeon</label>
                    <input type="text" class="form-control signature-elegant" name="surgeon_signature" 
                           value="<?php echo htmlspecialchars($record['surgeon_signature'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Dr. Name & Stamp">
                </div>
            </div>
        </div>

        <div class="footer-actions d-flex justify-content-center gap-3">
            <button type="submit" class="btn btn-gradient">
                <i class="fas fa-save me-2"></i>Update Record
            </button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const toastContainer = document.getElementById('toastContainer');

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.querySelector('[name="name"]').value.trim();
        if(!name) {
            showToast('Please enter the name of the worker.', 'danger');
            return;
        }
        
        loadingOverlay.style.display = 'flex';
        
        const formData = new FormData(form);
        
        fetch('save_form27.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingOverlay.style.display = 'none';
            if(data.success) {
                showToast('✅ Record updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'view.php?id=<?php echo $id; ?>';
                }, 1500);
            } else {
                showToast('❌ Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            loadingOverlay.style.display = 'none';
            showToast('Error: ' + error.message, 'danger');
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>