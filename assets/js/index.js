(() => {
    'use strict';

    const app = window.CLIMS_APP || {};
    const form = document.getElementById('medicalForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const toastContainer = document.getElementById('toastContainer');
    const searchBtn = document.getElementById('searchBtn');
    const searchClimsInput = document.getElementById('search_clims_id');
    const climsIdInput = document.getElementById('clims_id');
    const serialInput = document.getElementById('serial_no');
    const examIdInput = document.getElementById('examination_id');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const newRecordBtn = document.getElementById('newRecordBtn');
    const statusChip = document.getElementById('recordStatus');
    const recordInfo = document.getElementById('recordInfo');
    const workerPhotoInput = document.getElementById('worker_photo');
    const workerPhotoPreview = document.getElementById('worker_photo_preview');

    const today = new Date().toISOString().slice(0, 10);

    const fieldMap = {
        1: [
            'serial_no', 'attested_by_eic', 'exam_date', 'full_name', 'age_sex', 'aadhar_no',
            'address', 'mobile_no', 'demo_exam_date', 'contractor_agency', 'clims_id', 'ntpc_eic', 'worker_photo'
        ],
        2: [
            'diabetes', 'hypertension', 'vertigo', 'epilepsy', 'height_phobia', 'skin_diseases',
            'asthma', 'alcohol_intake', 'mental_illness', 'tobacco_chewing', 'cancer', 'piles',
            'hearing_problem', 'chronic_illness', 'deformity', 'past_accident', 'smoking', 'medicine_history',
            'contractor_signature'
        ],
        3: [
            'height', 'weight', 'bp', 'bmi', 'chest_insp', 'chest_exp', 'pulse_spo2_temp',
            'pallor', 'icterus', 'clubbing', 'built', 'tongue', 'teeth', 'other_finding'
        ],
        4: ['cardio_system', 'respiratory_system', 'cns', 'system_other'],
        5: [
            'distant_r_with', 'distant_r_without', 'distant_l_with', 'distant_l_without',
            'near_r_with', 'near_r_without', 'near_l_with', 'near_l_without',
            'colour_vision', 'eye_disorder'
        ],
        6: ['lmp', 'menstrual_cycle', 'pregnancy_duration'],
        7: [
            'cbc', 'random_blood_sugar', 'urine_rm', 'blood_group', 'lft_kft',
            'ecg', 'chest_xray', 'height_pass_test', 'other_tests'
        ],
        8: ['opinion', 'remarks', 'worker_signature', 'doctor_signature']
    };

    let dataStatus = 'draft';
    let currentStep = 1;

    function showToast(message, type = 'success') {
        const safeType = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'success';
        const wrapper = document.createElement('div');
        wrapper.className = 'toast align-items-center text-bg-' + safeType + ' border-0';
        wrapper.setAttribute('role', 'alert');
        wrapper.setAttribute('aria-live', 'assertive');
        wrapper.setAttribute('aria-atomic', 'true');

        wrapper.innerHTML = [
            '<div class="d-flex">',
            '<div class="toast-body">' + message + '</div>',
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>',
            '</div>'
        ].join('');

        toastContainer.appendChild(wrapper);
        const toast = new bootstrap.Toast(wrapper, { delay: 3500 });
        toast.show();
        wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
    }

    function setLoading(isVisible, text = 'Saving...') {
        loadingText.textContent = text;
        loadingOverlay.style.display = isVisible ? 'flex' : 'none';
    }

    function getField(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function setStatusChip(status) {
        dataStatus = status || 'draft';
        statusChip.classList.remove('status-draft', 'status-completed', 'status-verified');

        if (dataStatus === 'verified') {
            statusChip.classList.add('status-verified');
            statusChip.innerHTML = '<i class="fa-solid fa-circle-check"></i> Verified';
        } else if (dataStatus === 'completed') {
            statusChip.classList.add('status-completed');
            statusChip.innerHTML = '<i class="fa-solid fa-check"></i> Completed';
        } else {
            statusChip.classList.add('status-draft');
            statusChip.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Draft';
        }
    }

    function setSectionEditable(step, editable) {
        const section = document.getElementById('section' + step);
        if (!section) {
            return;
        }

        section.classList.toggle('section-locked', !editable);
        const controls = section.querySelectorAll('input, select, textarea, button');
        controls.forEach((control) => {
            if (control.type === 'hidden') {
                return;
            }
            control.disabled = !editable;
        });
    }

    function applyWorkflow() {
        if (dataStatus === 'verified') {
            for (let i = 1; i <= 8; i += 1) {
                setSectionEditable(i, false);
            }
            finalSubmitBtn.style.display = 'none';
            return;
        }

        if (dataStatus === 'completed') {
            for (let i = 1; i <= 8; i += 1) {
                setSectionEditable(i, false);
            }
            finalSubmitBtn.style.display = 'inline-flex';
            return;
        }

        for (let i = 1; i <= 8; i += 1) {
            setSectionEditable(i, i === currentStep);
        }
        finalSubmitBtn.style.display = 'none';
    }

    function parseValueForField(name, value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value);
    }

    function fillForm(data) {
        Object.entries(data || {}).forEach(([name, value]) => {
            const nodes = form.querySelectorAll('[name="' + name + '"]');
            if (!nodes.length) {
                return;
            }

            if (nodes[0].type === 'radio') {
                nodes.forEach((radio) => {
                    radio.checked = String(radio.value) === String(value);
                });
                return;
            }

            if (nodes[0].type === 'file') {
                return;
            }

            nodes[0].value = parseValueForField(name, value);
        });

        if (data.id) {
            examIdInput.value = String(data.id);
            recordInfo.textContent = 'Record ID: ' + data.id + ' | CLIMS ID: ' + (data.clims_id || '');
        }

        if (data.clims_id) {
            climsIdInput.value = data.clims_id;
            searchClimsInput.value = data.clims_id;
        }

        if (data.worker_photo) {
            workerPhotoPreview.src = data.worker_photo;
            workerPhotoPreview.style.display = 'inline-block';
        } else {
            workerPhotoPreview.src = '';
            workerPhotoPreview.style.display = 'none';
        }

        calculateBMI();
    }

    function getInputValue(name) {
        const nodes = form.querySelectorAll('[name="' + name + '"]');
        if (!nodes.length) {
            return '';
        }

        if (nodes[0].type === 'radio') {
            const checked = form.querySelector('[name="' + name + '"]:checked');
            return checked ? checked.value : '';
        }

        return nodes[0].value || '';
    }

    function setInputValue(name, value) {
        const nodes = form.querySelectorAll('[name="' + name + '"]');
        if (!nodes.length) {
            return;
        }

        if (nodes[0].type === 'radio') {
            nodes.forEach((node) => {
                node.checked = node.value === value;
            });
            return;
        }

        nodes[0].value = value;
    }

    function isStepDataPresent(step) {
        const fields = fieldMap[step] || [];
        for (let i = 0; i < fields.length; i += 1) {
            const field = fields[i];
            if (field === 'worker_photo') {
                if (workerPhotoInput.files && workerPhotoInput.files.length > 0) {
                    return true;
                }
                if (workerPhotoPreview.src) {
                    return true;
                }
                continue;
            }
            if (getInputValue(field).trim() !== '') {
                return true;
            }
        }
        return false;
    }

    function validateStep(step) {
        const climsId = climsIdInput.value.trim();

        if (step === 1) {
            if (!climsId) {
                showToast('CLIMS ID is required for Container 1.', 'warning');
                climsIdInput.focus();
                return false;
            }
            if (!getInputValue('full_name').trim()) {
                showToast('Worker name is required.', 'warning');
                getField('full_name').focus();
                return false;
            }
            return true;
        }

        if (!climsId) {
            showToast('Please save Container 1 first so CLIMS ID is locked.', 'warning');
            return false;
        }

        if (step === 6 && !isStepDataPresent(6)) {
            setInputValue('menstrual_cycle', 'Not Applicable');
            return true;
        }

        if (!isStepDataPresent(step)) {
            showToast('Please fill at least one field before saving this container.', 'warning');
            return false;
        }

        return true;
    }

    function collectStepData(step) {
        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('action', 'save_container');
        fd.append('container', String(step));
        fd.append('clims_id', climsIdInput.value.trim());
        fd.append('examination_id', examIdInput.value || '');

        const fields = fieldMap[step] || [];
        fields.forEach((field) => {
            if (field === 'worker_photo') {
                if (workerPhotoInput.files && workerPhotoInput.files[0]) {
                    fd.append('worker_photo', workerPhotoInput.files[0]);
                }
                return;
            }

            fd.append(field, getInputValue(field));
        });

        return fd;
    }

    async function saveContainer(step) {
        if (!validateStep(step)) {
            return;
        }

        const payload = collectStepData(step);
        setLoading(true, 'Saving Container ' + step + '...');

        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: payload
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Save failed.');
            }

            if (data.id) {
                examIdInput.value = String(data.id);
                recordInfo.textContent = 'Record ID: ' + data.id + ' | CLIMS ID: ' + climsIdInput.value.trim();
            }
            if (data.serial_no) {
                serialInput.value = data.serial_no;
            }
            if (data.worker_photo) {
                workerPhotoPreview.src = data.worker_photo;
                workerPhotoPreview.style.display = 'inline-block';
            }

            setStatusChip(data.data_status || 'draft');
            if (data.data_status === 'draft') {
                currentStep = Number(data.next_step || (step + 1));
                if (currentStep > 8) {
                    currentStep = 8;
                }
            }

            applyWorkflow();
            searchClimsInput.value = climsIdInput.value.trim();
            showToast(data.message || 'Container saved successfully.');

            if (step < 8 && data.data_status === 'draft') {
                const nextSection = document.getElementById('section' + currentStep);
                if (nextSection) {
                    nextSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        } catch (error) {
            showToast(error.message || 'Unable to save.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function lookupByClimsId() {
        const climsId = searchClimsInput.value.trim();
        if (!climsId) {
            showToast('Enter a CLIMS ID to search.', 'warning');
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('clims_id', climsId);

        setLoading(true, 'Fetching record...');

        try {
            const response = await fetch(app.lookupUrl, {
                method: 'POST',
                body: fd
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Record not found.');
            }

            form.reset();
            fillForm(data.data || {});
            setStatusChip(data.data_status || 'draft');

            if (data.data_status === 'draft') {
                const last = Number(data.last_completed_container || 0);
                currentStep = Math.min(last + 1, 8);
            } else {
                currentStep = 8;
            }

            applyWorkflow();
            showToast('Record loaded for CLIMS ID: ' + climsId, 'info');
        } catch (error) {
            showToast(error.message || 'Unable to load record.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function finalSubmit() {
        const climsId = climsIdInput.value.trim();
        if (!climsId) {
            showToast('CLIMS ID missing. Please load or save record first.', 'warning');
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('action', 'final_submit');
        fd.append('clims_id', climsId);

        setLoading(true, 'Final submission in progress...');
        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: fd
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Final submit failed.');
            }

            setStatusChip('verified');
            applyWorkflow();
            showToast(data.message || 'Final submit complete. Redirecting to Form 26...', 'success');

            const redirectUrl = data.redirect_url || (app.form26Url + '?examination_id=' + encodeURIComponent(data.id || examIdInput.value));
            window.setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
        } catch (error) {
            showToast(error.message || 'Unable to submit final record.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    function generateSerialNumber() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const rnd = Math.floor(Math.random() * 9000) + 1000;
        return 'CLIMS/' + y + '/' + m + d + '/' + rnd;
    }

    function calculateBMI() {
        const height = parseFloat(getInputValue('height'));
        const weight = parseFloat(getInputValue('weight'));
        if (!height || !weight || height <= 0 || weight <= 0) {
            setInputValue('bmi', '');
            return;
        }
        const bmi = weight / ((height / 100) * (height / 100));
        setInputValue('bmi', bmi.toFixed(2));
    }

    function resetForNewRecord() {
        form.reset();
        examIdInput.value = '';
        serialInput.value = generateSerialNumber();
        setInputValue('exam_date', today);
        setInputValue('demo_exam_date', today);
        searchClimsInput.value = '';
        climsIdInput.value = '';
        workerPhotoPreview.src = '';
        workerPhotoPreview.style.display = 'none';
        recordInfo.textContent = 'New record';
        setStatusChip('draft');
        currentStep = 1;
        applyWorkflow();
    }

    document.querySelectorAll('.save-section-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const step = Number(button.dataset.step || '0');
            if (!step) {
                return;
            }
            if (dataStatus !== 'draft' || step !== currentStep) {
                showToast('Only the current unlocked container can be edited.', 'warning');
                return;
            }
            saveContainer(step);
        });
    });

    searchBtn.addEventListener('click', lookupByClimsId);
    searchClimsInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupByClimsId();
        }
    });

    climsIdInput.addEventListener('blur', () => {
        searchClimsInput.value = climsIdInput.value.trim();
    });

    workerPhotoInput.addEventListener('change', () => {
        const file = workerPhotoInput.files && workerPhotoInput.files[0];
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = (event) => {
            workerPhotoPreview.src = String(event.target && event.target.result ? event.target.result : '');
            workerPhotoPreview.style.display = 'inline-block';
        };
        reader.readAsDataURL(file);
    });

    const heightField = getField('height');
    const weightField = getField('weight');
    if (heightField) {
        heightField.addEventListener('input', calculateBMI);
    }
    if (weightField) {
        weightField.addEventListener('input', calculateBMI);
    }

    finalSubmitBtn.addEventListener('click', finalSubmit);
    newRecordBtn.addEventListener('click', () => {
        const proceed = window.confirm('Start a new CLIMS record? Unsaved changes in current editable section will be lost.');
        if (!proceed) {
            return;
        }
        resetForNewRecord();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    resetForNewRecord();
})();
