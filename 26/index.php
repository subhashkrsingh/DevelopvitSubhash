<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>FORM-26 Certificate of Fitness</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: #f4f6f9;
      padding: 24px;
      font-family: "Segoe UI", Roboto, sans-serif;
    }
    .form-sheet {
      background: #fff;
      max-width: 850px;
      margin: auto;
      padding: 30px;
      border-radius: 6px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    }
    .header {
      text-align: center;
      margin-bottom: 20px;
    }
.header img {
        width: 100px;
        height: auto;
    }
    .dotted-input {
      border: none;
      border-bottom: 2px dotted #555;
      background: transparent;
      outline: none;
      padding: 4px 6px;
      width: 100%;
    }
    textarea.dotted-input {
      min-height: 50px;
      resize: vertical;
    }
    .section-title {
      font-weight: 600;
      margin: 12px 0;
    }
    .signature-box {
      border: 1px dashed #aaa;
      min-height: 100px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border-radius: 6px;
    }
    .signature-box img {
      max-height: 100px;
      max-width: 100%;
      object-fit: contain;
    }

    @media print {
      body { background: white; }
      .form-sheet { box-shadow: none; }
      .btn, #uploadSignature { display: none !important; }
    }
  </style>
</head>
<body>

<div class="form-sheet">
  <!-- Header -->
  <div class="d-flex justify-content-between">
    <div>
      <div style="width:110px;height:60px;border:1px solid #aaa;display:flex;align-items:center;justify-content:center;font-size:0.8rem;color:#666;">
        <img src="NTPC_Logo.svg.png" alt="NTPC Main Logo" width="160" height="60" >
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

  <!-- Form -->
  <form id="fitnessForm" class="mt-3" action="save_form.php" method="POST">
    <div class="col-12 col-md-6">
        <label class="item-label">1. Serial Number:</label><div class="fill-line"><input class="dotted-input" type="text" name="serial" required></div>
    </div><br>
  <p>
    <p class="lh-lg">
      I certify that I have personally examined Shri 
        <input type="text" name="patient_name" class="dotted-input">
    </p>
  <div class="row mb-3">
  <div class="col-md-6">
    S/O <input type="text" name="father_name" class="dotted-input w-75">
  </div>
  <div class="col-md-6">
    R/O <input type="text" name="address" class="dotted-input w-75">
  </div>
  </div>
    who is desirous of being employed in 
    <input type="text" name="employment_details" class="dotted-input"> 
    as <input type="text" name="designation" class="dotted-input">
    and that he is age,as nearly as can be from my examination  is  <input type="text" name="age" class="dotted-input"> Years, and he is, in my 
    <select name="fitness_status">
      <option value="">Select</option>
      <option>Fit</option>
      <option>Unfit</option>
      <option>Temporarily unfit</option>
    </select>. for employment in the above mentioned factory as mentioned below.
  </p>

  <div class="mb-3">
    <label class="fw-semibold">2.</label>
    He may be produced for further examination after a period of 
    <input type="text" name="further_exam_period" class="dotted-input">
  </div>

  <div class="mb-3">
    <label class="fw-semibold">3.</label>
    The serial number of the previous certificate is 
    <input type="text" name="previous_certificate_no" class="dotted-input">
  </div>

  <!-- Signature -->
  <div class="row mt-4">
    <div class="col-md-6"></div>
    <div class="col-md-6 text-end">
      <div class="fw-semibold">Signature of certifying surgeon</div>
      <div class="signature-box mt-2" id="signaturePreview">
        <span class="text-muted">No signature uploaded</span>
      </div>
      <input type="hidden" name="surgeon_signature" id="surgeon_signature">
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end mt-4">
    <button type="reset" class="btn btn-outline-secondary">Reset</button>
    <button type="button" id="previewBtn" class="btn btn-primary">Preview / Print</button>
    <button type="submit" class="btn btn-success">Save</button>
    <button type="button" id="nextBtn" class="btn btn-info">Next</button>
  </div>
</form>

  <div id="message" class="mt-3" style="display:none;"></div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("fitnessForm");
  const msg = document.getElementById("message");

  // Form submit
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    alert("Form saved successfully!");
  });

  // Reset button clears preview
  document.querySelector("button[type='reset']").addEventListener("click", () => {
    document.getElementById("signaturePreview").innerHTML = 
      '<span class="text-muted">No signature uploaded</span>';
  });

  // ✅ Next button (redirect to Form-27)
  document.getElementById("nextBtn").addEventListener("click", () => {
    window.location.href = "27/index.php";  
  });

  // ✅ Preview button
  document.getElementById("previewBtn").addEventListener("click", () => {
    window.print();
  });
});
</script>

</body>
</html>