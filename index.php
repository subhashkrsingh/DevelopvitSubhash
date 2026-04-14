<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corporate Medical Cell | Annual Medical Examination</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 30px 20px;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px 42px;
            border-radius: 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            margin: 0 auto;
            max-width: 1450px;
        }
        @media (max-width: 768px) {
            .form-container { padding: 24px 18px; }
        }
        .corp-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4f46e5;
        }
        .corp-header h2 { font-weight: 800; color: #0f172a; }
        .corp-header h4 { color: #4f46e5; font-weight: 600; }
        .section-title {
            margin-top: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #4f46e5;
            font-weight: 700;
            font-size: 1.4rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-title i {
            background: linear-gradient(135deg, #4f46e5, #a855f7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 10px 16px;
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
        }
        .vision-table {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .vision-table thead {
            background: linear-gradient(98deg, #4f46e5, #7c3aed);
            color: white;
        }
        .vision-table thead th { padding: 14px 8px; font-weight: 600; }
        .signature-elegant {
            border: 2px dashed #a5b4fc;
            background: linear-gradient(120deg, #fef9ff, #ffffff);
            border-radius: 20px;
            padding: 12px 18px;
        }
        .photo-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 24px;
            padding: 12px;
            text-align: center;
            background: #fefce8;
        }
        #photoPreview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 16px;
            margin-top: 8px;
        }
        .btn-submit {
            background: linear-gradient(105deg, #4f46e5, #9333ea);
            border: none;
            border-radius: 40px;
            padding: 14px 42px;
            font-weight: 700;
            color: white;
        }
        .btn-submit:hover { transform: translateY(-3px); }
        .btn-reset {
            border-radius: 40px;
            padding: 14px 34px;
            font-weight: 600;
            border: 2px solid #cbd5e1;
            background: white;
            color: #334155;
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
        .loading-content {
            background: white;
            padding: 30px 50px;
            border-radius: 30px;
            text-align: center;
        }
        .alert-fixed {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
        <p class="mt-3 fw-bold">Saving Medical Record...</p>
        <p class="text-muted small">Please wait</p>
    </div>
</div>

<div id="alertContainer"></div>

<div class="container">
    <div class="form-container">
        <form id="medicalExaminationForm" method="POST" action="save_examination.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="corp-header">
                <h2><i class="fas fa-building me-2"></i> CORPORATE MEDICAL CELL</h2>
                <h4>ANNUAL MEDICAL EXAMINATION OF CONTRACT WORKER IN CLIMS</h4>
                <div class="row mt-3 align-items-end">
                    <div class="col-md-2 text-start">
                        <label class="form-label">S.NO.</label>
                        <input type="text" name="serial_no" class="form-control" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PHOTOGRAPH</label>
                        <div class="photo-upload-area">
                            <input type="file" id="photoUpload" name="worker_photo" accept="image/*" class="form-control form-control-sm">
                            <img id="photoPreview" src="#" alt="Preview" style="display:none;">
                            <small class="text-muted">Upload photo here</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-danger">ATTESTED BY EIC</label>
                        <input type="text" name="attested_by_eic" class="form-control" placeholder="EIC Signature / Name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">DATE OF EXAMINATION</label>
                        <input type="date" name="exam_date" class="form-control" id="examDate">
                    </div>
                </div>
            </div>

            <!-- DEMOGRAPHICS -->
            <h4 class="section-title"><i class="fas fa-id-card"></i> DEMOGRAPHICS</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">NAME*</label><input type="text" name="full_name" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">AGE & SEX</label><input type="text" name="age_sex" class="form-control" placeholder="35/M"></div>
                <div class="col-md-3"><label class="form-label">AADHAR NO.</label><input type="text" name="aadhar_no" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">ADDRESS</label><input type="text" name="address" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">MOBILE NO.</label><input type="text" name="mobile_no" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">DATE OF EXAMIN.</label><input type="date" name="demo_exam_date" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">CONTRACTOR/AGENCY</label><input type="text" name="contractor_agency" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">CLIMS ID / GATE PASS ID</label><input type="text" name="clims_id" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">NTPC EIC</label><input type="text" name="ntpc_eic" class="form-control"></div>
            </div>

            <!-- HISTORY -->
            <h4 class="section-title"><i class="fas fa-notes-medical"></i> HISTORY/SELF DECLARATION</h4>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">DIABETES</label><select name="diabetes" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">HYPERTENSION</label><select name="hypertension" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">VERTIGO</label><select name="vertigo" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">EPILEPSY</label><select name="epilepsy" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">HEIGHT PHOBIA</label><select name="height_phobia" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">SKIN DISEASES</label><select name="skin_diseases" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">ASTHMA</label><select name="asthma" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">ALCOHOL INTAKE</label><select name="alcohol_intake" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">MENTAL ILLNESS</label><select name="mental_illness" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">TOBACCO CHEWING</label><select name="tobacco_chewing" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">CANCER</label><select name="cancer" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">PILES/HERNIA</label><select name="piles" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">HEARING PROBLEM</label><select name="hearing_problem" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">CHRONIC ILLNESS</label><select name="chronic_illness" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">ANY DEFORMITY</label><select name="deformity" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">PAST ACCIDENT/SURGERY</label><select name="past_accident" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-md-3"><label class="form-label">SMOKING</label><select name="smoking" class="form-select"><option value="">Select</option><option>Yes</option><option>No</option></select></div>
                <div class="col-12"><label class="form-label">MEDICINES BEING TAKEN</label><textarea name="medicine_history" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><label class="form-label">SIGNATURE OF CONTRACT WORKER</label><input type="text" name="contractor_signature" class="form-control signature-elegant"></div>
            </div>

            <!-- GENERAL EXAMINATION -->
            <h4 class="section-title"><i class="fas fa-heartbeat"></i> GENERAL EXAMINATION</h4>
            <div class="row g-3">
                <div class="col-md-2"><label class="form-label">HEIGHT (cm)</label><input type="number" id="heightInput" name="height" class="form-control" step="0.1"></div>
                <div class="col-md-2"><label class="form-label">WEIGHT (kg)</label><input type="number" id="weightInput" name="weight" class="form-control" step="0.1"></div>
                <div class="col-md-2"><label class="form-label">BP (mmHg)</label><input type="text" name="bp" class="form-control" placeholder="120/80"></div>
                <div class="col-md-2"><label class="form-label">BMI</label><input type="text" id="bmiField" name="bmi" class="form-control" readonly></div>
                <div class="col-md-2"><label class="form-label">CHEST (INSPIRAT.)</label><input type="text" name="chest_insp" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">CHEST (EXPIRATION)</label><input type="text" name="chest_exp" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">PULSE/SPO2/TEMP</label><input type="text" name="pulse_spo2_temp" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">PALLOR</label><select name="pallor" class="form-select"><option value="">Select</option><option>Present</option><option>Absent</option></select></div>
                <div class="col-md-2"><label class="form-label">ICTERUS</label><select name="icterus" class="form-select"><option value="">Select</option><option>Present</option><option>Absent</option></select></div>
                <div class="col-md-2"><label class="form-label">CLUBBING</label><select name="clubbing" class="form-select"><option value="">Select</option><option>Present</option><option>Absent</option></select></div>
                <div class="col-md-2"><label class="form-label">BUILT</label><select name="built" class="form-select"><option value="">Select</option><option>Thin</option><option>Medium</option><option>Obese</option></select></div>
                <div class="col-md-3"><label class="form-label">TONGUE</label><input type="text" name="tongue" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">TEETH</label><input type="text" name="teeth" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">ANY OTHER FINDING</label><input type="text" name="other_finding" class="form-control"></div>
            </div>

            <!-- SYSTEMIC EXAMINATION -->
            <h4 class="section-title"><i class="fas fa-stethoscope"></i> SYSTEMIC EXAMINATION</h4>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">CARDIOVASCULAR SYSTEM</label><input type="text" name="cardio_system" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">RESPIRATORY SYSTEM</label><input type="text" name="respiratory_system" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">CNS</label><input type="text" name="cns" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">ANY OTHER FINDINGS</label><input type="text" name="system_other" class="form-control"></div>
            </div>

            <!-- VISION TABLE -->
            <h4 class="section-title"><i class="fas fa-eye"></i> VISION</h4>
            <div class="table-responsive">
                <table class="table table-bordered vision-table align-middle text-center">
                    <thead>
                        <tr><th rowspan="2">PARAMETER</th><th colspan="2">RIGHT EYE</th><th colspan="2">LEFT EYE</th></tr>
                        <tr><th>WITH GLASS</th><th>WITHOUT GLASS</th><th>WITH GLASS</th><th>WITHOUT GLASS</th></tr>
                    </thead>
                    <tbody>
                        <tr class="fw-bold">DISTANT VISION</td>
                            <td><select name="distant_r_with" class="form-select"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                            <td><select name="distant_r_without" class="form-select"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                            <td><select name="distant_l_with" class="form-select"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                            <td><select name="distant_l_without" class="form-select"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                        </tr>
                        <tr class="fw-bold">NEAR VISION</td>
                            <td><select name="near_r_with" class="form-select"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                            <td><select name="near_r_without" class="form-select"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                            <td><select name="near_l_with" class="form-select"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                            <td><select name="near_l_without" class="form-select"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                        </tr>
                        <tr><td class="fw-bold">COLOUR VISION</td><td colspan="4"><input type="text" name="colour_vision" class="form-control"></td></tr>
                        <tr><td class="fw-bold">EYE DISORDER</td><td colspan="4"><input type="text" name="eye_disorder" class="form-control"></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- FOR FEMALE WORKERS -->
            <h4 class="section-title"><i class="fas fa-female"></i> FOR FEMALE WORKERS</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">LAST MENSTRUAL PERIOD</label><input type="text" name="lmp" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">MENSTRUAL CYCLE</label><select name="menstrual_cycle" class="form-select"><option value="">Select</option><option>Regular</option><option>Irregular</option></select></div>
                <div class="col-md-4"><label class="form-label">PREGNANCY DURATION</label><input type="text" name="pregnancy_duration" class="form-control"></div>
            </div>

            <!-- INVESTIGATIONS - FIXED BLOOD GROUP DROPDOWN -->
            <h4 class="section-title"><i class="fas fa-flask"></i> INVESTIGATIONS</h4>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">CBC</label><input type="text" name="cbc" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">RANDOM BLOOD SUGAR</label><input type="text" name="random_blood_sugar" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">URINE R&amp;M</label><input type="text" name="urine_rm" class="form-control"></div>
                <div class="col-md-3">
                    <label class="form-label">BLOOD GROUP</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A Positive (A+)</option>
                        <option value="A-">A Negative (A-)</option>
                        <option value="B+">B Positive (B+)</option>
                        <option value="B-">B Negative (B-)</option>
                        <option value="O+">O Positive (O+)</option>
                        <option value="O-">O Negative (O-)</option>
                        <option value="AB+">AB Positive (AB+)</option>
                        <option value="AB-">AB Negative (AB-)</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">LFT/KFT</label><input type="text" name="lft_kft" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">ECG</label><select name="ecg" class="form-select"><option value="">Select</option><option>Normal</option><option>Abnormal</option></select></div>
                <div class="col-md-3"><label class="form-label">CHEST X-RAY</label><select name="chest_xray" class="form-select"><option value="">Select</option><option>Normal</option><option>Abnormal</option></select></div>
                <div class="col-md-3"><label class="form-label">HEIGHT PASS TEST</label><select name="height_pass_test" class="form-select"><option value="">Select</option><option>Pass</option><option>Fail</option></select></div>
                <div class="col-12"><label class="form-label">OTHER TESTS</label><textarea name="other_tests" class="form-control" rows="2"></textarea></div>
            </div>

            <!-- OPINION & REMARKS -->
            <h4 class="section-title"><i class="fas fa-clipboard-list"></i> OPINION & REMARKS</h4>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">OPINION</label>
                    <div class="d-flex flex-wrap gap-4 mb-3">
                        <div class="form-check"><input class="form-check-input" type="radio" name="opinion" value="MEDICALLY FIT"> <label class="form-check-label">MEDICALLY FIT</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="opinion" value="UNFIT"> <label class="form-check-label">UNFIT</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="opinion" value="TEMPORARILY UNFIT"> <label class="form-check-label">TEMPORARILY UNFIT</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="opinion" value="CONDITIONALLY FIT"> <label class="form-check-label">CONDITIONALLY FIT</label></div>
                    </div>
                </div>
                <div class="col-12"><label class="form-label">REMARKS</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
                <div class="col-md-6"><label class="form-label">WORKER SIGNATURE</label><input type="text" name="worker_signature" class="form-control signature-elegant"></div>
                <div class="col-md-6"><label class="form-label">DOCTOR SIGNATURE</label><input type="text" name="doctor_signature" class="form-control signature-elegant"></div>
            </div>

            <div class="text-center mt-5 d-flex flex-wrap justify-content-center gap-3">
                <button type="submit" class="btn btn-submit px-5 py-3"><i class="fas fa-save me-2"></i> SAVE & PROCEED TO FORM 26</button>
                <button type="reset" class="btn btn-reset px-5 py-3"><i class="fas fa-eraser me-2"></i> RESET FORM</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Set today's date
    document.getElementById('examDate').value = new Date().toISOString().split('T')[0];
    
    // BMI Calculator
    const heightInput = document.getElementById('heightInput');
    const weightInput = document.getElementById('weightInput');
    const bmiField = document.getElementById('bmiField');
    
    function calculateBMI() {
        const height = parseFloat(heightInput?.value);
        const weight = parseFloat(weightInput?.value);
        if (height && weight && height > 50 && height < 300 && weight > 10 && weight < 400) {
            const bmi = weight / ((height / 100) ** 2);
            bmiField.value = bmi.toFixed(2);
        } else {
            bmiField.value = '';
        }
    }
    
    heightInput?.addEventListener('input', calculateBMI);
    weightInput?.addEventListener('input', calculateBMI);
    
    // Photo Preview
    const photoInput = document.getElementById('photoUpload');
    const photoPreview = document.getElementById('photoPreview');
    photoInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const url = URL.createObjectURL(file);
            photoPreview.src = url;
            photoPreview.style.display = 'block';
        }
    });
    
    // Generate Serial Number
    const serialInput = document.querySelector('input[name="serial_no"]');
    if(serialInput) {
        const date = new Date();
        serialInput.value = 'CLIMS/' + date.getFullYear() + '/' + String(date.getMonth() + 1).padStart(2,'0') + '/' + String(date.getDate()).padStart(2,'0') + '/' + Math.floor(Math.random() * 10000);
    }
    
    // Form Submit
    const form = document.getElementById('medicalExaminationForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const alertContainer = document.getElementById('alertContainer');
    
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-fixed`;
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        alertContainer.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fullName = document.querySelector('input[name="full_name"]').value.trim();
        if(!fullName) {
            showAlert('Please enter the NAME of the contract worker.', 'danger');
            document.querySelector('input[name="full_name"]').focus();
            return;
        }
        
        loadingOverlay.style.display = 'flex';
        
        const formData = new FormData(form);
        
        fetch('save_examination.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            console.log('Response:', text);
            try {
                return JSON.parse(text);
            } catch(e) {
                throw new Error('Server returned invalid response: ' + text.substring(0, 200));
            }
        })
        .then(data => {
            loadingOverlay.style.display = 'none';
            if(data.success) {
                showAlert('✅ ' + data.message, 'success');
                setTimeout(() => {
                    window.location.href = '26/index.php?id=' + data.id;
                }, 1500);
            } else {
                showAlert('❌ Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            loadingOverlay.style.display = 'none';
            console.error('Error:', error);
            showAlert('Error: ' + error.message, 'danger');
        });
    });
    
    // Reset confirmation
    document.querySelector('button[type="reset"]')?.addEventListener('click', function(e) {
        if(!confirm('⚠️ Are you sure you want to reset the entire form?')) {
            e.preventDefault();
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>