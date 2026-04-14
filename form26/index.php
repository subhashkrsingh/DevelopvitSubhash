<?php

require_once __DIR__ . '/../config.php';

$csrfToken = csrf_token();
$examId = isset($_GET['examination_id']) ? (int)$_GET['examination_id'] : (int)($_SESSION['current_examination_id'] ?? 0);

$exam = null;
$form26 = null;
$error = null;

// Determine if this is edit mode (Form 26 already exists)
$isEditMode = false;

if ($examId <= 0) {
    $error = 'Missing examination ID. Please complete the CLIMS form first.';
} else {
    try {
        $pdo = pdo();
        $exam = get_medical_examination_by_id($pdo, $examId);
        if (!$exam) {
            $error = 'Medical examination record not found.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM form26 WHERE examination_id = :id ORDER BY id DESC LIMIT 1');
            $stmt->execute([':id' => $examId]);
            $form26 = $stmt->fetch() ?: null;
            $isEditMode = $form26 !== null;
        }
    } catch (Throwable $e) {
        $error = APP_DEBUG ? $e->getMessage() : 'Unable to load Form 26.';
    }
}

$defaults = [
    'serial_number' => $form26['serial_number'] ?? ($exam['serial_no'] ?? ''),
    'patient_name' => $form26['patient_name'] ?? ($exam['full_name'] ?? ''),
    'father_name' => $form26['father_name'] ?? '',
    'address' => $form26['address'] ?? ($exam['address'] ?? ''),
    'employment_details' => $form26['employment_details'] ?? ($exam['contractor_agency'] ?? ''),
    'designation' => $form26['designation'] ?? '',
    'age' => $form26['age'] ?? (extract_age($exam['age_sex'] ?? null) ?? ''),
    'fitness_status' => $form26['fitness_status'] ?? ($exam['opinion'] ?? ''),
    'further_exam_period' => $form26['further_exam_period'] ?? '',
    'previous_certificate_no' => $form26['previous_certificate_no'] ?? '',
    'surgeon_signature' => $form26['surgeon_signature'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CLIMS - Form 26</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-card">
        <div class="spinner-border text-primary" role="status"></div>
        <p id="loadingText">Saving Form 26...</p>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="page-shell" style="max-width: 1100px;">
    <div class="page-head">
        <h1><i class="fa-solid fa-file-medical me-2"></i>Form 26 - Fitness Certificate</h1>
        <p>Corporate Medical Cell Information Management System (CLIMS)</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <a class="btn btn-outline-soft" href="../index.php">Back To Main Form</a>
    <?php else: ?>
        <form id="form26Form" autocomplete="off" novalidate>
            <input autocomplete="off" id="csrf_token" type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input autocomplete="off" id="examination_id" type="hidden" name="examination_id" value="<?php echo (int)$examId; ?>">
            <input autocomplete="off" type="hidden" name="form26_id" id="form26_id" value="<?php echo (int)($form26['id'] ?? 0); ?>">

            <section class="clims-section">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa-solid fa-user-check"></i>Certificate Details</h2>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="serial_number">Serial Number</label>
                        <input autocomplete="off" id="serial_number" type="text" class="form-control" name="serial_number" value="<?php echo htmlspecialchars((string)$defaults['serial_number'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="patient_name">Patient Name <span class="text-danger">*</span></label>
                        <input autocomplete="name" id="patient_name" type="text" class="form-control" name="patient_name" value="<?php echo htmlspecialchars((string)$defaults['patient_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="father_name">Father Name</label>
                        <input autocomplete="name" id="father_name" type="text" class="form-control" name="father_name" value="<?php echo htmlspecialchars((string)$defaults['father_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label" for="address">Address</label>
                        <textarea autocomplete="street-address" id="address" class="form-control" rows="2" name="address"><?php echo htmlspecialchars((string)$defaults['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="age">Age</label>
                        <input autocomplete="off" id="age" type="number" class="form-control" name="age" value="<?php echo htmlspecialchars((string)$defaults['age'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="employment_details">Employment Details</label>
                        <input autocomplete="off" id="employment_details" type="text" class="form-control" name="employment_details" value="<?php echo htmlspecialchars((string)$defaults['employment_details'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="designation">Designation</label>
                        <input autocomplete="off" id="designation" type="text" class="form-control" name="designation" value="<?php echo htmlspecialchars((string)$defaults['designation'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="fitness_status">Fitness Status <span class="text-danger">*</span></label>
                        <select autocomplete="off" id="fitness_status" class="form-select" name="fitness_status" required>
                            <option value="">Select</option>
                            <?php
                            $fitnessOptions = ['MEDICALLY FIT', 'UNFIT', 'TEMPORARILY UNFIT', 'CONDITIONALLY FIT'];
                            foreach ($fitnessOptions as $option):
                                $selected = ((string)$defaults['fitness_status'] === $option) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="further_exam_period">Further Examination Period</label>
                        <input autocomplete="off" id="further_exam_period" type="text" class="form-control" name="further_exam_period" value="<?php echo htmlspecialchars((string)$defaults['further_exam_period'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="previous_certificate_no">Previous Certificate No.</label>
                        <input autocomplete="off" id="previous_certificate_no" type="text" class="form-control" name="previous_certificate_no" value="<?php echo htmlspecialchars((string)$defaults['previous_certificate_no'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="surgeon_signature">Certifying Surgeon Signature</label>
                        <input autocomplete="off" id="surgeon_signature" type="text" class="form-control" name="surgeon_signature" value="<?php echo htmlspecialchars((string)$defaults['surgeon_signature'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </section>

            <div class="footer-actions d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-gradient btn-pill" id="saveForm26Btn">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    <?php echo $isEditMode ? 'Save Changes and Open Form 27' : 'Submit'; ?>
                </button>
                <?php if ($isEditMode): ?>
                    <a class="btn btn-outline-soft" id="nextToForm27" href="../form27/index.php?examination_id=<?php echo (int)$examId; ?>">
                        <i class="fa-solid fa-arrow-right me-1"></i>Skip to Form 27
                    </a>
                <?php endif; ?>
                <a href="../index.php" class="btn btn-outline-secondary btn-pill">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back to CLIMS
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if (!$error): ?>
<script>
window.FORM26_APP = {
    saveUrl: 'save_form26.php'
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/form26.js"></script>
<?php endif; ?>
</body>
</html>



