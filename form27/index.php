<?php
require_once __DIR__ . '/../config.php';

// Define helper functions
function extract_age($ageSex) {
    if (empty($ageSex)) return null;
    if (preg_match('/(\d{1,3})/', $ageSex, $matches)) {
        return (int)$matches[1];
    }
    return null;
}

function extract_sex($ageSex) {
    if (empty($ageSex)) return null;
    if (preg_match('/\b(M|F|Male|Female|Other)\b/i', $ageSex, $matches)) {
        $sex = strtolower($matches[1]);
        if ($sex === 'm' || $sex === 'male') return 'Male';
        if ($sex === 'f' || $sex === 'female') return 'Female';
        return 'Other';
    }
    return null;
}

$csrfToken = csrf_token();
$examId = isset($_GET['examination_id']) ? (int)$_GET['examination_id'] : (int)($_SESSION['current_examination_id'] ?? 0);

$exam = null;
$form27 = null;
$error = null;

if ($examId <= 0) {
    $error = 'Missing examination ID. Please complete Form 26 first.';
} else {
    try {
        $pdo = pdo();
        $exam = get_medical_examination_by_id($pdo, $examId);
        if (!$exam) {
            $error = 'Medical examination record not found.';
        } else {
            // Create form27 table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `form27` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `examination_id` int(11) NOT NULL,
                    `serial_number` varchar(100) DEFAULT NULL,
                    `department` varchar(255) DEFAULT NULL,
                    `name` varchar(255) DEFAULT NULL,
                    `sex` varchar(20) DEFAULT NULL,
                    `age` int(11) DEFAULT NULL,
                    `start_date` date DEFAULT NULL,
                    `leave_transfer` text,
                    `occupation` varchar(255) DEFAULT NULL,
                    `raw_materials` text,
                    `exam_date` date DEFAULT NULL,
                    `result` varchar(50) DEFAULT NULL,
                    `signs_symptoms` text,
                    `tests_result` text,
                    `suspension_details` text,
                    `certificate_issued` varchar(255) DEFAULT NULL,
                    `recertified_date` date DEFAULT NULL,
                    `surgeon_signature` varchar(255) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `examination_id` (`examination_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            $stmt = $pdo->prepare('SELECT * FROM form27 WHERE examination_id = :id ORDER BY id DESC LIMIT 1');
            $stmt->execute([':id' => $examId]);
            $form27 = $stmt->fetch() ?: null;
        }
    } catch (Throwable $e) {
        $error = APP_DEBUG ? $e->getMessage() : 'Unable to load Form 27.';
    }
}

// Generate serial number if not exists
$serialNumber = $form27['serial_number'] ?? ('FORM27/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . rand(1000, 9999));

$defaults = [
    'serial_number' => $serialNumber,
    'department' => $form27['department'] ?? '',
    'name' => $form27['name'] ?? ($exam['full_name'] ?? ''),
    'sex' => $form27['sex'] ?? (extract_sex($exam['age_sex'] ?? null) ?? ''),
    'age' => $form27['age'] ?? (extract_age($exam['age_sex'] ?? null) ?? ''),
    'start_date' => $form27['start_date'] ?? ($exam['demo_exam_date'] ?? date('Y-m-d')),
    'leave_transfer' => $form27['leave_transfer'] ?? '',
    'occupation' => $form27['occupation'] ?? '',
    'raw_materials' => $form27['raw_materials'] ?? '',
    'exam_date' => $form27['exam_date'] ?? ($exam['exam_date'] ?? date('Y-m-d')),
    'result' => $form27['result'] ?? ($exam['opinion'] ?? ''),
    'signs_symptoms' => $form27['signs_symptoms'] ?? '',
    'tests_result' => $form27['tests_result'] ?? '',
    'suspension_details' => $form27['suspension_details'] ?? '',
    'certificate_issued' => $form27['certificate_issued'] ?? '',
    'recertified_date' => $form27['recertified_date'] ?? '',
    'surgeon_signature' => $form27['surgeon_signature'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CLIMS - Form 27 Health Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .signature-elegant {
            border: 2px dashed #a5b4fc;
            background: linear-gradient(120deg, #fef9ff, #ffffff);
            border-radius: 20px;
            padding: 12px 18px;
            font-family: 'Courier New', monospace;
        }
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
        .btn-final {
            background: linear-gradient(105deg, #dc2626, #b91c1c);
            border: none;
            border-radius: 40px;
            padding: 14px 42px;
            font-weight: 700;
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-final:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px -10px #dc2626;
        }
        .btn-final:active {
            transform: translateY(0);
        }
        .footer-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-card">
        <div class="spinner-border text-primary" role="status"></div>
        <p id="loadingText">Finalizing Form 27...</p>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<div id="alertContainer"></div>

<div class="page-shell" style="max-width: 1200px;">
    <div class="page-head">
        <h1><i class="fa-solid fa-notes-medical me-2"></i>Form 27 - Health Register</h1>
        <p>Corporate Medical Cell Information Management System (CLIMS)</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="text-center mt-3">
            <a class="btn btn-gradient" href="../form26/index.php?examination_id=<?php echo $examId; ?>">
                <i class="fas fa-arrow-left me-2"></i>Back to Form 26
            </a>
        </div>
    <?php else: ?>
        <form id="form27Form" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="examination_id" value="<?php echo (int)$examId; ?>">
            <input type="hidden" name="form27_id" id="form27_id" value="<?php echo (int)($form27['id'] ?? 0); ?>">

            <!-- Section 1: Basic Information -->
            <div class="form-section">
                <h4><i class="fas fa-user me-2"></i> Basic Information</h4>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="serial_number">Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" 
                               value="<?php echo htmlspecialchars($defaults['serial_number'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="department">Department / Work</label>
                        <input type="text" class="form-control" id="department" name="department" 
                               value="<?php echo htmlspecialchars($defaults['department'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="name">Name of Worker <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($defaults['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="sex">Sex</label>
                        <input type="text" class="form-control" id="sex" name="sex" 
                               value="<?php echo htmlspecialchars($defaults['sex'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="age">Age</label>
                        <input type="number" class="form-control" id="age" name="age" 
                               value="<?php echo htmlspecialchars($defaults['age'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="start_date">Date of Employment</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo htmlspecialchars($defaults['start_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="occupation">Nature of Job / Occupation</label>
                        <input type="text" class="form-control" id="occupation" name="occupation" 
                               value="<?php echo htmlspecialchars($defaults['occupation'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="leave_transfer">Date of Leaving or Transfer (with reasons)</label>
                        <textarea class="form-control" id="leave_transfer" name="leave_transfer" rows="2"><?php echo htmlspecialchars($defaults['leave_transfer'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="raw_materials">Raw Materials / By-products Exposed To</label>
                        <textarea class="form-control" id="raw_materials" name="raw_materials" rows="2"><?php echo htmlspecialchars($defaults['raw_materials'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Medical Examination Details -->
            <div class="form-section">
                <h4><i class="fas fa-stethoscope me-2"></i> Medical Examination Details</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="exam_date">Date of Examination</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" 
                               value="<?php echo htmlspecialchars($defaults['exam_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="result">Result</label>
                        <select class="form-select" id="result" name="result">
                            <option value="">Select Result</option>
                            <option value="Fit" <?php echo ($defaults['result'] == 'Fit') ? 'selected' : ''; ?>>Fit</option>
                            <option value="Unfit" <?php echo ($defaults['result'] == 'Unfit') ? 'selected' : ''; ?>>Unfit</option>
                            <option value="Temporarily Unfit" <?php echo ($defaults['result'] == 'Temporarily Unfit') ? 'selected' : ''; ?>>Temporarily Unfit</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="recertified_date">Re-certified Fit to Resume Duty On</label>
                        <input type="date" class="form-control" id="recertified_date" name="recertified_date" 
                               value="<?php echo htmlspecialchars($defaults['recertified_date'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="signs_symptoms">Signs and Symptoms Observed</label>
                        <textarea class="form-control" id="signs_symptoms" name="signs_symptoms" rows="2"><?php echo htmlspecialchars($defaults['signs_symptoms'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tests_result">Nature of Tests and Results</label>
                        <textarea class="form-control" id="tests_result" name="tests_result" rows="2"><?php echo htmlspecialchars($defaults['tests_result'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="suspension_details">Period of Suspension with Reasons (if declared unfit)</label>
                        <textarea class="form-control" id="suspension_details" name="suspension_details" rows="2"><?php echo htmlspecialchars($defaults['suspension_details'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="certificate_issued">Certificate of Unfitness Issued</label>
                        <input type="text" class="form-control" id="certificate_issued" name="certificate_issued" 
                               value="<?php echo htmlspecialchars($defaults['certificate_issued'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Yes/No with details">
                    </div>
                </div>
            </div>

            <!-- Section 3: Signature -->
            <div class="form-section">
                <h4><i class="fas fa-signature me-2"></i> Certification</h4>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="surgeon_signature">Signature of Certifying Surgeon</label>
                        <input type="text" class="form-control signature-elegant" id="surgeon_signature" name="surgeon_signature" 
                               value="<?php echo htmlspecialchars($defaults['surgeon_signature'], ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="Dr. Name & Stamp">
                    </div>
                </div>
            </div>

            <!-- Only Final Submit Button -->
            <div class="footer-actions">
                <button type="button" class="btn-final" id="finalSubmitBtn">
                    <i class="fa-solid fa-check-circle me-2"></i>Final Submit
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('form27Form');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const alertContainer = document.getElementById('alertContainer');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const toastContainer = document.getElementById('toastContainer');
    const examinationId = <?php echo $examId; ?>;

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
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

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-fixed`;
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        alertContainer.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    function saveAndSubmit() {
        const workerName = document.getElementById('name').value.trim();
        if(!workerName) {
            showAlert('Please enter the name of the worker.', 'danger');
            document.getElementById('name').focus();
            return false;
        }
        
        loadingOverlay.style.display = 'flex';
        document.getElementById('loadingText').innerText = 'Finalizing Form 27 submission...';
        
        const formData = new FormData(form);
        
        fetch('save_form27.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            let cleanText = text.replace(/^\uFEFF/, '').trim();
            try {
                return JSON.parse(cleanText);
            } catch(e) {
                console.error('Raw response:', cleanText);
                throw new Error('Invalid JSON response: ' + cleanText.substring(0, 100));
            }
        })
        .then(data => {
            loadingOverlay.style.display = 'none';
            if(data.success) {
                showToast('✅ Form 27 completed successfully! Redirecting to main page...', 'success');
                setTimeout(() => {
                    window.location.href = '../index.php?submitted=1';
                }, 1500);
                return true;
            } else {
                showAlert('❌ Error: ' + data.message, 'danger');
                return false;
            }
        })
        .catch(error => {
            loadingOverlay.style.display = 'none';
            console.error('Error:', error);
            showAlert('Error: ' + error.message, 'danger');
            return false;
        });
        
        return false;
    }

    if(finalSubmitBtn) {
        finalSubmitBtn.addEventListener('click', function() {
            if(confirm('Are you sure you want to finalize and submit Form 27? This will complete the health register and redirect to the main page.')) {
                saveAndSubmit();
            }
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>