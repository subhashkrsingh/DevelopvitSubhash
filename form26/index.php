<?php

require_once __DIR__ . '/../config.php';

// Define the extract_age function BEFORE using it
function extract_age($ageSex) {
    if (empty($ageSex)) return null;
    if (preg_match('/(\d{1,3})/', $ageSex, $matches)) {
        return (int)$matches[1];
    }
    return null;
}

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
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>FORM-26 Certificate of Fitness</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../assets/css/app.css">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
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
        <p class="mt-3 fw-bold">Saving Form 26...</p>
        <p class="text-muted small">Please wait</p>
    </div>
</div>

<div id="alertContainer"></div>

<div class="form-sheet">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div style="width:110px;height:60px;border:1px solid #aaa;display:flex;align-items:center;justify-content:center;font-size:0.8rem;color:#666;border-radius:12px;">
        <img src="../NTPC_Logo.svg.png" alt="NTPC Main Logo" width="160" height="60" style="max-width:100%;height:auto;">
      </div>
    </div>
    <div class="header flex-grow-1">
      <h5 class="mb-1">FORM 26</h5>
      <div style="font-size:0.85rem"><h5>[Prescribed under the Schedule specified under Rule 109]</h5></div>
      <h4 class="mt-2">Certificate of Fitness</h4>
    </div>
    <div class="text-end fw-bold" style="width:110px">
      दादरी<br>DADRI
    </div>
  </div>

  <!-- Error Message -->
  <?php if ($error): ?>
  <div class="alert alert-danger mt-3">
    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    <br><a href="../index.php" class="btn btn-sm btn-primary mt-2">Go to Medical Examination Form</a>
  </div>
  <?php else: ?>

  <!-- Form -->
  <form id="fitnessForm" class="mt-3" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="examination_id" value="<?php echo $examId; ?>">
    
    <div class="col-12 col-md-6">
        <label class="item-label">1. Serial Number:</label>
        <div class="fill-line">
            <input class="dotted-input" type="text" name="serial" required value="<?php echo htmlspecialchars($defaults['serial_number'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    </div><br>
    
    <p class="lh-lg">
      I certify that I have personally examined Shri 
      <input type="text" name="patient_name" class="dotted-input" value="<?php echo htmlspecialchars($defaults['patient_name'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>
    
    <div class="row mb-3">
      <div class="col-md-6">
        S/O <input type="text" name="father_name" class="dotted-input w-75" value="<?php echo htmlspecialchars($defaults['father_name'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="col-md-6">
        R/O <input type="text" name="address" class="dotted-input w-75" value="<?php echo htmlspecialchars($defaults['address'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>
    
    <p>
      who is desirous of being employed in 
      <input type="text" name="employment_details" class="dotted-input" value="<?php echo htmlspecialchars($defaults['employment_details'], ENT_QUOTES, 'UTF-8'); ?>"> 
      as 
      <input type="text" name="designation" class="dotted-input" value="<?php echo htmlspecialchars($defaults['designation'], ENT_QUOTES, 'UTF-8'); ?>">
      and that his age, as nearly as can be from my examination, is  
      <input type="text" name="age" class="dotted-input" value="<?php echo htmlspecialchars($defaults['age'], ENT_QUOTES, 'UTF-8'); ?>"> 
      Years, and he is, in my 
      <select name="fitness_status" class="dotted-input" style="width: auto; min-width: 150px;">
        <option value="">Select</option>
        <option value="Fit" <?php echo ($defaults['fitness_status'] == 'Fit') ? 'selected' : ''; ?>>Fit</option>
        <option value="Unfit" <?php echo ($defaults['fitness_status'] == 'Unfit') ? 'selected' : ''; ?>>Unfit</option>
        <option value="Temporarily unfit" <?php echo ($defaults['fitness_status'] == 'Temporarily unfit') ? 'selected' : ''; ?>>Temporarily unfit</option>
      </select> 
      for employment in the above mentioned factory as mentioned below.
    </p>

    <div class="mb-3">
      <label class="fw-semibold">2.</label>
      He may be produced for further examination after a period of 
      <input type="text" name="further_exam_period" class="dotted-input" value="<?php echo htmlspecialchars($defaults['further_exam_period'], ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="mb-3">
      <label class="fw-semibold">3.</label>
      The serial number of the previous certificate is 
      <input type="text" name="previous_certificate_no" class="dotted-input" value="<?php echo htmlspecialchars($defaults['previous_certificate_no'], ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <!-- Signature -->
    <div class="row mt-4">
      <div class="col-md-6"></div>
      <div class="col-md-6 text-end">
        <div class="fw-semibold">Signature of certifying surgeon</div>
        <div class="signature-box mt-2" id="signaturePreview">
          <span class="text-muted"><?php echo htmlspecialchars($defaults['surgeon_signature'] ?: 'No signature uploaded', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <input type="text" name="surgeon_signature" id="surgeon_signature" class="form-control mt-2" placeholder="Enter signature" value="<?php echo htmlspecialchars($defaults['surgeon_signature'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mt-4">
      <button type="reset" class="btn btn-outline-secondary">Reset</button>
      <button type="button" id="previewBtn" class="btn btn-primary">Preview / Print</button>
      <button type="button" id="saveBtn" class="btn btn-success">Save</button>
      <button type="button" id="nextBtn" class="btn btn-info">Next</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("fitnessForm");
  const loadingOverlay = document.getElementById('loadingOverlay');
  const alertContainer = document.getElementById('alertContainer');
  const saveBtn = document.getElementById('saveBtn');
  const nextBtn = document.getElementById('nextBtn');

  function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-fixed`;
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    alertContainer.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
  }

  // Save Form Data and Stay on Same Page
  function saveFormData(redirectToNext = false) {
    const fitnessStatus = document.querySelector('select[name="fitness_status"]').value;
    if(!fitnessStatus) {
        showAlert('Please select the fitness status.', 'danger');
        return false;
    }
    
    loadingOverlay.style.display = 'flex';
    
    const formData = new FormData(form);
    
    fetch('save_form26.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('HTTP error ' + response.status);
      }
      return response.json();
    })
    .then(data => {
      loadingOverlay.style.display = 'none';
      if(data.success) {
        showAlert('✅ ' + data.message, 'success');
        
        // Update signature preview
        const surgeonSignature = document.getElementById('surgeon_signature').value;
        if(surgeonSignature) {
          document.getElementById('signaturePreview').innerHTML = '<span>' + surgeonSignature + '</span>';
        }
        
        // If redirect to next page is requested
        if(redirectToNext) {
          setTimeout(() => {
            window.location.href = '../form27/index.php?examination_id=<?php echo $examId; ?>';
          }, 1000);
        }
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

  // Save button - saves data and stays on page
  if(saveBtn) {
    saveBtn.addEventListener('click', (e) => {
      e.preventDefault();
      saveFormData(false);
    });
  }

  // Next button - saves data and redirects to Form 27
  if(nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      saveFormData(true);
    });
  }

  // Form submit handler (for Enter key)
  if(form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      saveFormData(false);
    });
  }

  // Reset button clears preview
  document.querySelector("button[type='reset']")?.addEventListener("click", () => {
    document.getElementById("signaturePreview").innerHTML = 
      '<span class="text-muted">No signature uploaded</span>';
  });

  // Preview button
  document.getElementById("previewBtn")?.addEventListener("click", () => {
    window.print();
  });
});
</script>

</body>
</html>