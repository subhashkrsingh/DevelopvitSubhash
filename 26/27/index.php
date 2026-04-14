<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FORM-27 — Health Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 100%);
            padding: 30px;
            font-family: 'Segoe UI', sans-serif;
        }
        .form-sheet {
            background: white;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 36px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h2 {
            font-weight: 800;
            color: #0f172a;
        }
        .header h4 {
            color: #4f46e5;
        }
        .dotted-input {
            border: none;
            border-bottom: 2px dotted #4f46e5;
            background: transparent;
            outline: none;
            padding: 6px 8px;
            width: 100%;
        }
        .dotted-input:focus {
            border-bottom-color: #9333ea;
            background: #f8fafc;
        }
        textarea.dotted-input {
            min-height: 60px;
            resize: vertical;
        }
        .item-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
            display: block;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .section-title {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #4f46e5;
            color: #0f172a;
        }
        .btn-submit {
            background: linear-gradient(105deg, #4f46e5, #9333ea);
            border: none;
            border-radius: 40px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
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
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
        <p class="mt-3 fw-bold">Saving Health Register...</p>
        <p class="text-muted small">Please wait</p>
    </div>
</div>

<div id="alertContainer"></div>

<div class="form-sheet">
    <div class="header">
        <h2><i class="fas fa-book me-2"></i> FORM 27</h2>
        <h4>HEALTH REGISTER</h4>
        <p class="text-muted">[Prescribed under the Schedule specified under Rule 109]</p>
        <h5>NTPC DADRI</h5>
    </div>

    <form id="healthForm" method="POST" action="save_form.php">
        <div class="row g-4">
            <div class="col-md-6">
                <label class="item-label">1. Serial Number</label>
                <input class="dotted-input" type="text" name="serial" required>
            </div>

            <div class="col-md-6">
                <label class="item-label">2. Department / Work</label>
                <input class="dotted-input" type="text" name="department" required>
            </div>

            <div class="col-md-6">
                <label class="item-label">3. Name of worker</label>
                <input class="dotted-input" type="text" name="name" required>
            </div>

            <div class="col-md-6">
                <label class="item-label">4. Sex</label>
                <select class="dotted-input" name="sex" style="border-bottom: 2px dotted #4f46e5;" required>
                    <option value="">-- Select --</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="item-label">5. Age (Last birthday)</label>
                <input class="dotted-input" type="number" min="0" name="age" required>
            </div>

            <div class="col-md-6">
                <label class="item-label">6. Date of employment of present work</label>
                <input class="dotted-input" type="date" name="start_date" required>
            </div>

            <div class="col-12">
                <label class="item-label">7. Date of leaving or transfer of other work</label>
                <small class="text-muted d-block mb-1">With reasons for discharge or transfer</small>
                <input class="dotted-input" type="text" name="leave_transfer">
            </div>

            <div class="col-12">
                <label class="item-label">8. Nature of job or occupation</label>
                <input class="dotted-input" type="text" name="occupation">
            </div>

            <div class="col-12">
                <label class="item-label">9. Raw material products or by-products likely to be exposed to</label>
                <textarea class="dotted-input" name="raw_materials" rows="2"></textarea>
            </div>

            <div class="col-12">
                <div class="section-title">Date of medical examination and the result thereof</div>
            </div>

            <div class="col-md-4">
                <label class="item-label">10. Date</label>
                <input class="dotted-input" type="date" name="exam_date">
            </div>

            <div class="col-md-4">
                <label class="item-label">11. Result (fit / unfit)</label>
                <select class="dotted-input" name="result">
                    <option value="">-- Select --</option>
                    <option>Fit</option>
                    <option>Unfit</option>
                    <option>Temporarily Unfit</option>
                </select>
            </div>

            <div class="col-12">
                <label class="item-label">12. Signs and symptoms observed during examination</label>
                <textarea class="dotted-input" name="signs_symptoms" rows="2"></textarea>
            </div>

            <div class="col-12">
                <label class="item-label">13. Nature of tests and result hereof</label>
                <textarea class="dotted-input" name="tests_result" rows="2"></textarea>
            </div>

            <div class="col-12">
                <label class="item-label">14. If declared unfit for work state period of suspension with reasons in details</label>
                <textarea class="dotted-input" name="suspension_details" rows="2"></textarea>
            </div>

            <div class="col-md-6">
                <label class="item-label">15. Whether certificate of unfitness issued to worker</label>
                <input class="dotted-input" type="text" name="certificate_issued">
            </div>

            <div class="col-md-6">
                <label class="item-label">16. Re-certified fit to resume duty on</label>
                <input class="dotted-input" type="date" name="recertified_date">
            </div>

            <div class="col-12">
                <label class="item-label">Signature of Certifying Surgeon</label>
                <input class="dotted-input" type="text" name="surgeon_signature" placeholder="Dr. Name & Stamp">
            </div>
        </div>

        <div class="text-center mt-5 d-flex flex-wrap justify-content-center gap-3">
            <button type="submit" class="btn-submit"><i class="fas fa-save me-2"></i> SAVE FORM 27</button>
            <button type="button" class="btn-submit" style="background: #6c757d;" onclick="window.print()"><i class="fas fa-print me-2"></i> PRINT</button>
            <button type="reset" class="btn-submit" style="background: #dc2626;"><i class="fas fa-eraser me-2"></i> RESET</button>
            <a href="view_form27.php" class="btn-submit" style="background: #0891b2; text-decoration: none;"><i class="fas fa-eye me-2"></i> VIEW RECORDS</a>
            <a href="index.php" class="btn-submit" style="background: #10b981; text-decoration: none;"><i class="fas fa-home me-2"></i> BACK TO HOME</a>
        </div>
    </form>
</div>

<script>
    // Auto-generate serial number
    const serialInput = document.querySelector('input[name="serial"]');
    if(serialInput && !serialInput.value) {
        const date = new Date();
        serialInput.value = 'FORM27/' + date.getFullYear() + '/' + String(date.getMonth() + 1).padStart(2,'0') + '/' + String(date.getDate()).padStart(2,'0') + '/' + Math.floor(Math.random() * 10000);
    }

    // Set default dates
    const examDateInput = document.querySelector('input[name="exam_date"]');
    if(examDateInput && !examDateInput.value) {
        examDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    const startDateInput = document.querySelector('input[name="start_date"]');
    if(startDateInput && !startDateInput.value) {
        startDateInput.value = new Date().toISOString().split('T')[0];
    }

    // Check for success message from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('success') === '1') {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i> FORM 27 saved successfully!';
        document.body.appendChild(successDiv);
        setTimeout(() => successDiv.remove(), 3000);
        // Remove the parameter from URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    const form = document.getElementById('healthForm');
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
        
        const workerName = document.querySelector('input[name="name"]').value.trim();
        if(!workerName) {
            showAlert('Please enter the name of the worker.', 'danger');
            document.querySelector('input[name="name"]').focus();
            return;
        }
        
        loadingOverlay.style.display = 'flex';
        
        const formData = new FormData(form);
        
        fetch('save_form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingOverlay.style.display = 'none';
            if(data.success) {
                // Show success message and redirect with success parameter
                window.location.href = 'index.php?success=1';
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
</script>
</body>
</html>