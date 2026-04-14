<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FORM-27 — Health Register</title>

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
     body {
      background: #f2f4f6;
      padding: 24px;
      font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .form-sheet {
      background: white;
      max-width: 900px;
      margin: 0 auto;
      padding: 28px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.06);
      border-radius: 6px;
    }

    .header-left { width: 120px; }
    .header-center { flex: 1; text-align: center; }

    .dotted-input {
      border: none;
      border-bottom: 2px dotted #999;
      outline: none;
      padding: 6px 8px;
      min-width: 180px;
      background: transparent;
    }
    textarea.dotted-input {
      resize: vertical;
      min-height: 56px;
    }

    .item-label { font-weight: 600; }
    .small-muted { color: #666; font-size: 0.9rem; }
    .section-title {
      font-weight: 700;
      font-size: 1.05rem;
      margin-top: 16px;
      margin-bottom: 6px;
      color: #333;
    }
    .fill-line .dotted-input { width: 100%; }

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
  <div class="d-flex align-items-center mb-3">
    <div class="header-left">
      <div style="height:72px;width:110px;border:1px solid #ddd;display:flex;align-items:center;justify-content:center;font-size:0.85rem;color:#888">
       <img src="NTPC_Logo.svg.png" alt="NTPC Main Logo" width="160" height="60" >
      </div>
    </div>
    <div class="header-center">
      <div style="font-size:0.95rem;font-weight:700">FORM-27</div>
      <div style="font-size:0.85rem;color:#444"><h5>[Prescribed under the Schedule specified under Rule 109]</h5></div>
      <h2 style="margin:6px 0 0;font-size:1.6rem">Health Register</h2>
    </div>
    <div style="width:110px;text-align:right;font-weight:600;color:#555">
      दादरी<br/>DADRI
    </div>
  </div>

  <!-- Form -->
  <form id="healthForm" action="save_form.php" method="post">

    <div class="row g-3">
      <!-- 1 Serial Number -->
      <div class="col-12 col-md-6">
        <label class="item-label">1. Serial Number</label>
        <div class="fill-line"><input class="dotted-input" type="text" name="serial" required></div>
      </div>

      <!-- 2 Department/Work -->
      <div class="col-12 col-md-6">
        <label class="item-label">2. Department / Work</label>
        <div class="fill-line"><input class="dotted-input" type="text" name="department" required></div>
      </div>

      <!-- 3 Name of worker -->
      <div class="col-12 col-md-6">
        <label class="item-label">3. Name of worker</label>
        <div class="fill-line"><input class="dotted-input" type="text" name="name" required></div>
      </div>

      <!-- 4 Sex -->
      <div class="col-12 col-md-6">
        <label class="item-label">4. Sex</label>
        <div>
          <select class="form-select dotted-input" name="sex" style="border:none; padding-left:0;" required>
            <option value="">-- Select --</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>
      </div>

      <!-- 5 Age -->
      <div class="col-12 col-md-6">
        <label class="item-label">5. Age (Last birthday)</label>
        <div class="fill-line"><input class="dotted-input" type="number" min="0" name="age" required></div>
      </div>

      <!-- 6 Date of employment -->
      <div class="col-12 col-md-6">
        <label class="item-label">6. Date of employment of present work</label>
        <div class="fill-line"><input class="dotted-input" type="date" name="start_date" required></div>
      </div>

      <!-- 7 Date of leaving -->
      <div class="col-12">
        <label class="item-label">7. Date of leaving or transfer of other work</label>
        <div class="small-muted mb-1">With reasons for discharge or transfer</div>
        <div class="fill-line"><input class="dotted-input" type="text" name="leave_transfer"></div>
      </div>

      <!-- 8 Nature of job -->
      <div class="col-12">
        <label class="item-label">8. Nature of job or occupation</label>
        <div class="fill-line"><input class="dotted-input" type="text" name="occupation"></div>
      </div>

      <!-- 9 Raw materials -->
      <div class="col-12">
        <label class="item-label">9. Raw material products or by-products likely to be exposed to</label>
        <div class="fill-line"><textarea class="dotted-input" name="raw_materials"></textarea></div>
      </div>

      <div class="col-12">
        <div class="section-title">Date of medical examination and the result thereof</div>
      </div>

      <!-- 10 Date -->
      <div class="col-12 col-md-4">
        <label class="item-label">10. Date</label>
        <div class="fill-line"><input class="dotted-input" type="date" name="exam_date"></div>
      </div>

      <!-- 11 Result -->
      <div class="col-12 col-md-4">
        <label class="item-label">11. Result (fit / unfit)</label>
        <div>
          <select class="form-select dotted-input" name="result">
            <option value="">-- Select --</option>
            <option>Fit</option>
            <option>Unfit</option>
            <option>Temporarily Unfit</option>
          </select>
        </div>
      </div>

      <!-- 12 Signs & symptoms -->
      <div class="col-12">
        <label class="item-label">12. Signs and symptoms observed during examination</label>
        <div class="fill-line"><textarea class="dotted-input" name="signs_symptoms"></textarea></div>
      </div>

      <!-- 13 Nature of tests -->
      <div class="col-12">
        <label class="item-label">13. Nature of tests and result hereof</label>
        <div class="fill-line"><textarea class="dotted-input" name="tests_result"></textarea></div>
      </div>

      <!-- 14 Suspension -->
      <div class="col-12">
        <label class="item-label">14. If declared unfit for work state period of suspension with reasons in details</label>
        <div class="fill-line"><textarea class="dotted-input" name="suspension_details"></textarea></div>
      </div>

      <!-- 15 Certificate issued -->
      <div class="col-12 col-md-6">
        <label class="item-label">15. Whether certificate of unfitness issued to worker</label>
        <div class="fill-line"><input class="dotted-input" type="text" name="certificate_issued"></div>
      </div>

      <!-- 16 Re-certified date -->
      <div class="col-12 col-md-6">
        <label class="item-label">16. Re-certified fit to resume duty on</label>
        <div class="fill-line"><input class="dotted-input" type="date" name="recertified_date"></div>
      </div>

      <!-- 17 Digital Signature -->
        <div class="row mt-4">
          <div class="col-md-6"></div>
          <div class="col-md-6 text-end">
            <div class="fw-semibold">Signature of certifying surgeon</div>
            <div class="signature-box mt-2" id="signaturePreview">
              <span class="text-muted">No signature</span>
            </div>
          </div>
        </div>

      <!-- Buttons -->
      <div class="col-12 d-flex gap-2 justify-content-end mt-3">
          <button type="reset" class="btn btn-outline-secondary" id="resetBtn">Reset</button>
          <button type="button" class="btn btn-primary" id="previewBtn">Preview / Print</button>
          <button type="submit" class="btn btn-success" id="saveBtn">Save / Submit</button>
        </div>
    </div>
  </form>

  <!-- result message -->
  <div id="message" class="mt-3" style="display:none;"></div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function)(){
  const form = document.getElementById('healthForm');
  const msg = document.getElementById('message');
 // Form submit
    document.getElementById("healthForm").addEventListener("submit", (e) => {
      e.preventDefault();
      alert("Form submitted successfully!");
    });

    // Reset button clears preview
    document.getElementById("resetBtn").addEventListener("click", () => {
      document.getElementById("signaturePreview").innerHTML = 
        '<span class="text-muted">No signature</span>';
    });

    // Preview button
    document.getElementById("previewBtn").addEventListener("click", () => {
      window.print();
    });
}
</script>
</body>
</html>
