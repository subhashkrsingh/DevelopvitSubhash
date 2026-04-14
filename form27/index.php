<?php

require_once __DIR__ . '/../config.php';

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
            $stmt = $pdo->prepare('SELECT * FROM form27 WHERE examination_id = :id ORDER BY id DESC LIMIT 1');
            $stmt->execute([':id' => $examId]);
            $form27 = $stmt->fetch() ?: null;
        }
    } catch (Throwable $e) {
        $error = APP_DEBUG ? $e->getMessage() : 'Unable to load Form 27.';
    }
}

$defaults = [
    'serial_number' => $form27['serial_number'] ?? ($exam['serial_no'] ?? ''),
    'department' => $form27['department'] ?? '',
    'name' => $form27['name'] ?? ($exam['full_name'] ?? ''),
    'sex' => $form27['sex'] ?? (extract_sex($exam['age_sex'] ?? null) ?? ''),
    'age' => $form27['age'] ?? (extract_age($exam['age_sex'] ?? null) ?? ''),
    'start_date' => $form27['start_date'] ?? ($exam['demo_exam_date'] ?? ''),
    'leave_transfer' => $form27['leave_transfer'] ?? '',
    'occupation' => $form27['occupation'] ?? '',
    'raw_materials' => $form27['raw_materials'] ?? '',
    'exam_date' => $form27['exam_date'] ?? ($exam['exam_date'] ?? ''),
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
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-card">
        <div class="spinner-border text-primary" role="status"></div>
        <p id="loadingText">Saving Form 27...</p>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="page-shell" style="max-width: 1150px;">
    <div class="page-head">
        <h1><i class="fa-solid fa-notes-medical me-2"></i>Form 27 - Health Register</h1>
        <p>Corporate Medical Cell Information Management System (CLIMS)</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <a class="btn btn-outline-soft" href="../index.php">Back To Main Form</a>
    <?php else: ?>
        <form id="form27Form" autocomplete="off" novalidate>
            <input autocomplete="off" id="csrf_token" type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input autocomplete="off" id="examination_id" type="hidden" name="examination_id" value="<?php echo (int)$examId; ?>">
            <input autocomplete="off" type="hidden" name="form27_id" id="form27_id" value="<?php echo (int)($form27['id'] ?? 0); ?>">

            <section class="clims-section">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa-solid fa-book-medical"></i>Register Entry</h2>
                </div>

                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label" for="serial_number">Serial Number</label>
                        <input autocomplete="off" id="serial_number" type="text" class="form-control" name="serial_number" value="<?php echo htmlspecialchars((string)$defaults['serial_number'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="department">Department</label>
                        <input autocomplete="off" id="department" type="text" class="form-control" name="department" value="<?php echo htmlspecialchars((string)$defaults['department'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="name">Name</label>
                        <input autocomplete="name" id="name" type="text" class="form-control" name="name" value="<?php echo htmlspecialchars((string)$defaults['name'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-1"><label class="form-label" for="sex">Sex</label>
                        <input autocomplete="off" id="sex" type="text" class="form-control" name="sex" value="<?php echo htmlspecialchars((string)$defaults['sex'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-2"><label class="form-label" for="age">Age</label>
                        <input autocomplete="off" id="age" type="number" class="form-control" name="age" value="<?php echo htmlspecialchars((string)$defaults['age'], ENT_QUOTES, 'UTF-8'); ?>"></div>

                    <div class="col-md-3"><label class="form-label" for="start_date">Start Date</label>
                        <input autocomplete="off" id="start_date" type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars((string)$defaults['start_date'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="occupation">Occupation</label>
                        <input autocomplete="off" id="occupation" type="text" class="form-control" name="occupation" value="<?php echo htmlspecialchars((string)$defaults['occupation'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="exam_date">Examination Date</label>
                        <input autocomplete="off" id="exam_date" type="date" class="form-control" name="exam_date" value="<?php echo htmlspecialchars((string)$defaults['exam_date'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="result">Result</label>
                        <input autocomplete="off" id="result" type="text" class="form-control" name="result" value="<?php echo htmlspecialchars((string)$defaults['result'], ENT_QUOTES, 'UTF-8'); ?>"></div>

                    <div class="col-md-6"><label class="form-label" for="leave_transfer">Leave / Transfer</label>
                        <textarea autocomplete="off" id="leave_transfer" class="form-control" rows="2" name="leave_transfer"><?php echo htmlspecialchars((string)$defaults['leave_transfer'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="col-md-6"><label class="form-label" for="raw_materials">Raw Materials</label>
                        <textarea autocomplete="off" id="raw_materials" class="form-control" rows="2" name="raw_materials"><?php echo htmlspecialchars((string)$defaults['raw_materials'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>

                    <div class="col-md-6"><label class="form-label" for="signs_symptoms">Signs &amp; Symptoms</label>
                        <textarea autocomplete="off" id="signs_symptoms" class="form-control" rows="2" name="signs_symptoms"><?php echo htmlspecialchars((string)$defaults['signs_symptoms'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="col-md-6"><label class="form-label" for="tests_result">Tests Result</label>
                        <textarea autocomplete="off" id="tests_result" class="form-control" rows="2" name="tests_result"><?php echo htmlspecialchars((string)$defaults['tests_result'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>

                    <div class="col-md-6"><label class="form-label" for="suspension_details">Suspension Details</label>
                        <textarea autocomplete="off" id="suspension_details" class="form-control" rows="2" name="suspension_details"><?php echo htmlspecialchars((string)$defaults['suspension_details'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="col-md-3"><label class="form-label" for="certificate_issued">Certificate Issued</label>
                        <input autocomplete="off" id="certificate_issued" type="text" class="form-control" name="certificate_issued" value="<?php echo htmlspecialchars((string)$defaults['certificate_issued'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="col-md-3"><label class="form-label" for="recertified_date">Recertified Date</label>
                        <input autocomplete="off" id="recertified_date" type="date" class="form-control" name="recertified_date" value="<?php echo htmlspecialchars((string)$defaults['recertified_date'], ENT_QUOTES, 'UTF-8'); ?>"></div>

                    <div class="col-12"><label class="form-label" for="surgeon_signature">Certifying Surgeon Signature</label>
                        <input autocomplete="off" id="surgeon_signature" type="text" class="form-control" name="surgeon_signature" value="<?php echo htmlspecialchars((string)$defaults['surgeon_signature'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
            </section>

            <div class="footer-actions d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-gradient btn-pill" id="saveForm27Btn">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Form 27
                </button>
                <a class="btn btn-outline-soft" href="../form26/index.php?examination_id=<?php echo (int)$examId; ?>">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back To Form 26
                </a>
                <a class="btn btn-outline-soft" href="../index.php">
                    <i class="fa-solid fa-house me-1"></i>Back To Main Form
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if (!$error): ?>
<script>
window.FORM27_APP = {
    saveUrl: 'save_form27.php'
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/form27.js"></script>
<?php endif; ?>
</body>
</html>



