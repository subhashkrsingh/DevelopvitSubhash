<?php
require_once __DIR__ . '/config.php';

$csrfToken = csrf_token();
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CLIMS - Annual Medical Examination</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-card">
        <div class="spinner-border text-primary" role="status"></div>
        <p id="loadingText">Please wait...</p>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="page-shell">
    <div class="page-head">
        <h1><i class="fa-solid fa-hospital-user me-2"></i>CORPORATE MEDICAL CELL - CLIMS</h1>
        <p>ANNUAL MEDICAL EXAMINATION OF CONTRACT WORKER</p>
    </div>

    <div class="search-panel">
        <div class="row g-2 align-items-end">
            <div class="col-lg-7">
                <label class="form-label mb-1" for="search_clims_id">ENTER CLIMS ID TO RETRIEVE EXISTING DATA</label>
                        <input autocomplete="off" type="text" class="form-control" id="search_clims_id" placeholder="e.g. CLIMS-NTPC-2026-001">
            </div>
            <div class="col-lg-2 d-grid">
                <button type="button" class="btn btn-gradient btn-pill" id="searchBtn">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Search
                </button>
            </div>
            <div class="col-lg-3 text-lg-end">
                <span class="status-chip status-draft" id="recordStatus">
                    <i class="fa-solid fa-pen-to-square"></i> Draft
                </span>
                <div class="small text-muted mt-1" id="recordInfo">Search CLIMS ID to begin</div>
            </div>
        </div>

        <div class="mt-3 d-none" id="searchFeedback"></div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-gradient btn-pill d-none" id="createNewRecordBtn">
                <i class="fa-solid fa-file-circle-plus me-1"></i>Create New Record
            </button>
            <button type="button" class="btn btn-gradient btn-pill d-none" id="editModeBtn">
                <i class="fa-solid fa-pen me-1"></i>Edit Mode
            </button>
            <button type="button" class="btn btn-gradient btn-pill d-none" id="saveAllChangesBtn">
                <i class="fa-solid fa-floppy-disk me-1"></i>Save Changes
            </button>
        </div>
    </div>

    <form id="medicalForm" enctype="multipart/form-data" autocomplete="off" novalidate>
        <input autocomplete="off" id="csrf_token" type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input autocomplete="off" type="hidden" id="examination_id" name="examination_id" value="">

        <section class="clims-section workflow-section d-none" data-step="1" id="section1">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-id-card"></i>Container 1 - Demographics</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="1">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Demographics
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-2">
                    <label class="form-label" for="serial_no">S. No.</label>
                        <input autocomplete="off" type="text" class="form-control" name="serial_no" id="serial_no" readonly>
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="worker_photo">Photograph</label>
                    <div class="photo-box">
                        <input autocomplete="off" class="form-control" type="file" accept="image/*" name="worker_photo" id="worker_photo">
                        <img src="" alt="Worker Preview" id="worker_photo_preview" style="display:none;">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="attested_by_eic">Attested by EIC</label>
                        <input autocomplete="off" type="text" class="form-control" name="attested_by_eic" id="attested_by_eic">
                </div>
                <div class="col-lg-4">
                    <label class="form-label" for="exam_date">Date of Examination</label>
                        <input autocomplete="off" type="date" class="form-control" name="exam_date" id="exam_date" value="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-lg-4">
                    <label class="form-label" for="full_name">Name *</label>
                        <input autocomplete="name" type="text" class="form-control" name="full_name" id="full_name" required>
                </div>
                <div class="col-lg-2">
                    <label class="form-label" for="age_sex">Age &amp; Sex</label>
                        <input autocomplete="off" type="text" class="form-control" name="age_sex" id="age_sex" placeholder="35/M">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="aadhar_no">Aadhar No.</label>
                        <input autocomplete="off" type="text" class="form-control" name="aadhar_no" id="aadhar_no">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="mobile_no">Mobile No.</label>
                        <input autocomplete="tel" type="text" class="form-control" name="mobile_no" id="mobile_no">
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="address">Address</label>
                        <textarea autocomplete="street-address" class="form-control" name="address" id="address" rows="2"></textarea>
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="demo_exam_date">Date of Examin.</label>
                        <input autocomplete="off" type="date" class="form-control" name="demo_exam_date" id="demo_exam_date" value="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="contractor_agency">Contractor/Agency</label>
                        <input autocomplete="off" type="text" class="form-control" name="contractor_agency" id="contractor_agency">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="clims_id">CLIMS ID / Gate Pass ID *</label>
                        <input autocomplete="off" type="text" class="form-control" name="clims_id" id="clims_id" readonly>
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="ntpc_eic">NTPC EIC</label>
                        <input autocomplete="off" type="text" class="form-control" name="ntpc_eic" id="ntpc_eic">
                </div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="2" id="section2">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-file-signature"></i>Container 2 - History / Self Declaration</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="2">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 2
                </button>
            </div>

            <div class="row g-3">
                <?php
                $historyFields = [
                    'diabetes' => 'Diabetes',
                    'hypertension' => 'Hypertension',
                    'vertigo' => 'Vertigo',
                    'epilepsy' => 'Epilepsy',
                    'height_phobia' => 'Height Phobia',
                    'skin_diseases' => 'Skin Diseases',
                    'asthma' => 'Asthma',
                    'alcohol_intake' => 'Alcohol Intake',
                    'mental_illness' => 'Mental Illness',
                    'tobacco_chewing' => 'Tobacco Chewing',
                    'cancer' => 'Cancer',
                    'piles' => 'Piles/Hernia',
                    'hearing_problem' => 'Hearing Problem',
                    'chronic_illness' => 'Chronic Illness',
                    'deformity' => 'Any Deformity',
                    'past_accident' => 'Past Accident/Surgery',
                    'smoking' => 'Smoking',
                ];
                foreach ($historyFields as $fieldName => $label):
                ?>
                    <div class="col-lg-3">
                        <label class="form-label" for="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <select autocomplete="off" id="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" class="form-select" name="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                <?php endforeach; ?>

                <div class="col-12">
                    <label class="form-label" for="medicine_history">Medicines Being Taken</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="medicine_history" id="medicine_history"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="contractor_signature">Signature of Contract Worker</label>
                        <input autocomplete="off" type="text" class="form-control" name="contractor_signature" id="contractor_signature">
                </div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="3" id="section3">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-heart-pulse"></i>Container 3 - General Examination</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="3">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 3
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-2"><label class="form-label" for="height">Height (cm)</label>
                        <input autocomplete="off" type="number" step="0.01" class="form-control" name="height" id="height"></div>
                <div class="col-lg-2"><label class="form-label" for="weight">Weight (kg)</label>
                        <input autocomplete="off" type="number" step="0.01" class="form-control" name="weight" id="weight"></div>
                <div class="col-lg-2"><label class="form-label" for="bp">BP (mmHg)</label>
                        <input autocomplete="off" type="text" class="form-control" name="bp" id="bp"></div>
                <div class="col-lg-2"><label class="form-label" for="bmi">BMI</label>
                        <input autocomplete="off" type="text" class="form-control" name="bmi" id="bmi" readonly></div>
                <div class="col-lg-2"><label class="form-label" for="chest_insp">Chest (Inspiration)</label>
                        <input autocomplete="off" type="text" class="form-control" name="chest_insp" id="chest_insp"></div>
                <div class="col-lg-2"><label class="form-label" for="chest_exp">Chest (Expiration)</label>
                        <input autocomplete="off" type="text" class="form-control" name="chest_exp" id="chest_exp"></div>
                <div class="col-lg-3"><label class="form-label" for="pulse_spo2_temp">Pulse/SPO2/Temp</label>
                        <input autocomplete="off" type="text" class="form-control" name="pulse_spo2_temp" id="pulse_spo2_temp"></div>
                <div class="col-lg-2"><label class="form-label" for="pallor">Pallor</label>
                        <select autocomplete="off" class="form-select" name="pallor" id="pallor"><option value="">Select</option><option value="Present">Present</option><option value="Absent">Absent</option></select></div>
                <div class="col-lg-2"><label class="form-label" for="icterus">Icterus</label>
                        <select autocomplete="off" class="form-select" name="icterus" id="icterus"><option value="">Select</option><option value="Present">Present</option><option value="Absent">Absent</option></select></div>
                <div class="col-lg-2"><label class="form-label" for="clubbing">Clubbing</label>
                        <select autocomplete="off" class="form-select" name="clubbing" id="clubbing"><option value="">Select</option><option value="Present">Present</option><option value="Absent">Absent</option></select></div>
                <div class="col-lg-3"><label class="form-label" for="built">Built</label>
                        <select autocomplete="off" class="form-select" name="built" id="built"><option value="">Select</option><option value="Thin">Thin</option><option value="Medium">Medium</option><option value="Obese">Obese</option></select></div>
                <div class="col-lg-3"><label class="form-label" for="tongue">Tongue</label>
                        <input autocomplete="off" type="text" class="form-control" name="tongue" id="tongue"></div>
                <div class="col-lg-3"><label class="form-label" for="teeth">Teeth</label>
                        <input autocomplete="off" type="text" class="form-control" name="teeth" id="teeth"></div>
                <div class="col-lg-6"><label class="form-label" for="other_finding">Any Other Finding</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="other_finding" id="other_finding"></textarea></div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="4" id="section4">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-stethoscope"></i>Container 4 - Systemic Examination</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="4">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 4
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-6"><label class="form-label" for="cardio_system">Cardiovascular System</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="cardio_system" id="cardio_system"></textarea></div>
                <div class="col-lg-6"><label class="form-label" for="respiratory_system">Respiratory System</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="respiratory_system" id="respiratory_system"></textarea></div>
                <div class="col-lg-6"><label class="form-label" for="cns">CNS</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="cns" id="cns"></textarea></div>
                <div class="col-lg-6"><label class="form-label" for="system_other">Any Other Findings</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="system_other" id="system_other"></textarea></div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="5" id="section5">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-eye"></i>Container 5 - Vision</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="5">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 5
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered vision-table align-middle">
                    <thead>
                        <tr>
                            <th rowspan="2">Parameter</th>
                            <th colspan="2">Right Eye</th>
                            <th colspan="2">Left Eye</th>
                        </tr>
                        <tr>
                            <th>With Glass</th>
                            <th>Without Glass</th>
                            <th>With Glass</th>
                            <th>Without Glass</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">Distant Vision</td>
                            <td><select autocomplete="off" class="form-select" name="distant_r_with" id="distant_r_with"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                            <td><select autocomplete="off" class="form-select" name="distant_r_without" id="distant_r_without"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                            <td><select autocomplete="off" class="form-select" name="distant_l_with" id="distant_l_with"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                            <td><select autocomplete="off" class="form-select" name="distant_l_without" id="distant_l_without"><option value="">Select</option><option>6/6</option><option>6/9</option><option>6/12</option><option>6/18</option><option>6/24</option><option>6/36</option><option>6/60</option></select></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Near Vision</td>
                            <td><select autocomplete="off" class="form-select" name="near_r_with" id="near_r_with"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                            <td><select autocomplete="off" class="form-select" name="near_r_without" id="near_r_without"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                            <td><select autocomplete="off" class="form-select" name="near_l_with" id="near_l_with"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                            <td><select autocomplete="off" class="form-select" name="near_l_without" id="near_l_without"><option value="">Select</option><option>N5</option><option>N6</option><option>N8</option><option>N10</option><option>N12</option><option>N18</option></select></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-lg-6"><label class="form-label" for="colour_vision">Colour Vision</label>
                        <input autocomplete="off" type="text" class="form-control" name="colour_vision" id="colour_vision"></div>
                <div class="col-lg-6"><label class="form-label" for="eye_disorder">Any Eye Related Disorder</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="eye_disorder" id="eye_disorder"></textarea></div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="6" id="section6">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-venus"></i>Container 6 - For Female Workers</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="6">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 6
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-4"><label class="form-label" for="lmp">Last Menstrual Period</label>
                        <input autocomplete="off" type="text" class="form-control" name="lmp" id="lmp"></div>
                <div class="col-lg-4"><label class="form-label" for="menstrual_cycle">Menstrual Cycle</label>
                        <select autocomplete="off" class="form-select" name="menstrual_cycle" id="menstrual_cycle"><option value="">Select</option><option value="Regular">Regular</option><option value="Irregular">Irregular</option><option value="Not Applicable">Not Applicable</option></select></div>
                <div class="col-lg-4"><label class="form-label" for="pregnancy_duration">Pregnancy Duration</label>
                        <input autocomplete="off" type="text" class="form-control" name="pregnancy_duration" id="pregnancy_duration"></div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="7" id="section7">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-vials"></i>Container 7 - Investigations</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="7">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 7
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-4"><label class="form-label" for="cbc">CBC</label>
                        <input autocomplete="off" type="text" class="form-control" name="cbc" id="cbc"></div>
                <div class="col-lg-4"><label class="form-label" for="random_blood_sugar">Random Blood Sugar</label>
                        <input autocomplete="off" type="text" class="form-control" name="random_blood_sugar" id="random_blood_sugar"></div>
                <div class="col-lg-4"><label class="form-label" for="urine_rm">Urine R&amp;M</label>
                        <input autocomplete="off" type="text" class="form-control" name="urine_rm" id="urine_rm"></div>
                <div class="col-lg-3"><label class="form-label" for="blood_group">Blood Group</label>
                        <select autocomplete="off" class="form-select" name="blood_group" id="blood_group"><option value="">Select</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option></select></div>
                <div class="col-lg-3"><label class="form-label" for="lft_kft">LFT/KFT</label>
                        <input autocomplete="off" type="text" class="form-control" name="lft_kft" id="lft_kft"></div>
                <div class="col-lg-2"><label class="form-label" for="ecg">ECG</label>
                        <select autocomplete="off" class="form-select" name="ecg" id="ecg"><option value="">Select</option><option value="Normal">Normal</option><option value="Abnormal">Abnormal</option></select></div>
                <div class="col-lg-2"><label class="form-label" for="chest_xray">Chest X-Ray</label>
                        <select autocomplete="off" class="form-select" name="chest_xray" id="chest_xray"><option value="">Select</option><option value="Normal">Normal</option><option value="Abnormal">Abnormal</option></select></div>
                <div class="col-lg-2"><label class="form-label" for="height_pass_test">Height Pass Test</label>
                        <select autocomplete="off" class="form-select" name="height_pass_test" id="height_pass_test"><option value="">Select</option><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                <div class="col-12"><label class="form-label" for="other_tests">Other Tests</label>
                        <textarea autocomplete="off" class="form-control" rows="2" name="other_tests" id="other_tests"></textarea></div>
            </div>
        </section>

        <section class="clims-section workflow-section d-none" data-step="8" id="section8">
            <div class="section-head">
                <h2 class="section-title"><i class="fa-solid fa-notes-medical"></i>Container 8 - Opinion &amp; Remarks</h2>
                <button type="button" class="btn btn-gradient btn-pill save-section-btn" data-step="8">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Save Container 8
                </button>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <p class="form-label mb-2">Opinion</p>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check"><input autocomplete="off" class="form-check-input" type="radio" name="opinion" id="opinion_fit" value="MEDICALLY FIT"><label class="form-check-label" for="opinion_fit">Medically Fit</label></div>
                        <div class="form-check"><input autocomplete="off" class="form-check-input" type="radio" name="opinion" id="opinion_unfit" value="UNFIT"><label class="form-check-label" for="opinion_unfit">Unfit</label></div>
                        <div class="form-check"><input autocomplete="off" class="form-check-input" type="radio" name="opinion" id="opinion_temp" value="TEMPORARILY UNFIT"><label class="form-check-label" for="opinion_temp">Temporarily Unfit</label></div>
                        <div class="form-check"><input autocomplete="off" class="form-check-input" type="radio" name="opinion" id="opinion_cond" value="CONDITIONALLY FIT"><label class="form-check-label" for="opinion_cond">Conditionally Fit</label></div>
                    </div>
                </div>
                <div class="col-12"><label class="form-label" for="remarks">Remarks</label>
                        <textarea autocomplete="off" class="form-control" rows="3" name="remarks" id="remarks"></textarea></div>
                <div class="col-lg-6"><label class="form-label" for="worker_signature">Worker Signature</label>
                        <input autocomplete="off" type="text" class="form-control" name="worker_signature" id="worker_signature"></div>
                <div class="col-lg-6"><label class="form-label" for="doctor_signature">Doctor Signature</label>
                        <input autocomplete="off" type="text" class="form-control" name="doctor_signature" id="doctor_signature"></div>
            </div>
        </section>

        <div class="footer-actions d-flex flex-wrap justify-content-center gap-2">
            <button type="button" class="btn btn-gradient btn-pill d-none" id="submitForm26Btn">
                <i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26
            </button>
            <button type="button" class="btn btn-outline-soft" id="resetWorkflowBtn">
                <i class="fa-solid fa-rotate-right me-1"></i>Reset Screen
            </button>
        </div>
    </form>
</div>

<script>
window.CLIMS_APP = {
    csrfToken: <?php echo json_encode($csrfToken); ?>,
    saveUrl: 'save_examination.php',
    lookupUrl: 'ajax/get_patient_data.php',
    form26Url: 'form26/index.php'
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/index.js"></script>
</body>
</html>


