(() => {
    'use strict';

    const app = window.CLIMS_APP || {};
    const form = document.getElementById('medicalForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const toastContainer = document.getElementById('toastContainer');

    const searchBtn = document.getElementById('searchBtn');
    const searchClimsInput = document.getElementById('search_clims_id');
    const searchStatus = document.getElementById('searchStatus');
    const refreshBtn = document.getElementById('refreshBtn');
    const saveAllBtn = document.getElementById('saveAllBtn');
    const submitForm26Btn = document.getElementById('submitForm26Btn');
    
    const statusChip = document.getElementById('recordStatus');
    const recordInfo = document.getElementById('recordInfo');
    const examIdInput = document.getElementById('examination_id');
    const serialInput = document.getElementById('serial_no');
    const climsIdInput = document.getElementById('clims_id');

    const workerPhotoInput = document.getElementById('worker_photo');
    const workerPhotoPreview = document.getElementById('worker_photo_preview');

    const today = new Date().toISOString().slice(0, 10);

    let currentRecordStatus = 'draft';
    let currentClimsId = null;

    function showToast(message, type = 'success') {
        const safeType = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'success';
        const wrapper = document.createElement('div');
        wrapper.className = `toast align-items-center text-bg-${safeType} border-0`;
        wrapper.setAttribute('role', 'alert');
        wrapper.setAttribute('aria-live', 'assertive');
        wrapper.setAttribute('aria-atomic', 'true');

        wrapper.innerHTML = [
            '<div class="d-flex">',
            `<div class="toast-body">${message}</div>`,
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>',
            '</div>'
        ].join('');

        toastContainer.appendChild(wrapper);
        const toast = new bootstrap.Toast(wrapper, { delay: 3500 });
        toast.show();
        wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
    }

    function setLoading(isVisible, text = 'Please wait...') {
        loadingText.textContent = text;
        loadingOverlay.style.display = isVisible ? 'flex' : 'none';
    }

    async function parseJsonResponse(response) {
        const rawText = await response.text();
        const cleanText = rawText.replace(/^\uFEFF/, '').trim();

        if (!cleanText) {
            throw new Error('Empty response from server.');
        }

        try {
            return JSON.parse(cleanText);
        } catch (error) {
            throw new Error('Invalid JSON response: ' + cleanText.substring(0, 180));
        }
    }

    function getField(name) {
        return form.querySelector(`[name="${name}"]`);
    }

    function getInputValue(name) {
        const nodes = form.querySelectorAll(`[name="${name}"]`);
        if (!nodes.length) return '';

        if (nodes[0].type === 'radio') {
            const checked = form.querySelector(`[name="${name}"]:checked`);
            return checked ? checked.value : '';
        }

        return nodes[0].value || '';
    }

    function setInputValue(name, value) {
        const nodes = form.querySelectorAll(`[name="${name}"]`);
        if (!nodes.length) return;

        if (nodes[0].type === 'radio') {
            nodes.forEach((node) => {
                node.checked = String(node.value) === String(value || '');
            });
            return;
        }

        nodes[0].value = value === null || value === undefined ? '' : String(value);
    }

    function setFeedback(message, type) {
        if (!message) {
            searchStatus.className = 'mt-3 d-none';
            searchStatus.textContent = '';
            return;
        }

        searchStatus.className = `mt-3 alert alert-${type}`;
        searchStatus.textContent = message;
    }

    function setStatusChip(status) {
        currentRecordStatus = status || 'draft';
        statusChip.classList.remove('status-draft', 'status-partial', 'status-completed', 'status-submitted');

        if (currentRecordStatus === 'submitted') {
            statusChip.classList.add('status-submitted');
            statusChip.innerHTML = '<i class="fa-solid fa-circle-check"></i> Submitted';
            submitForm26Btn.classList.remove('d-none');
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-folder-open me-1"></i>Open FORM 26';
            saveAllBtn.classList.add('d-none');
        } else if (currentRecordStatus === 'completed') {
            statusChip.classList.add('status-completed');
            statusChip.innerHTML = '<i class="fa-solid fa-check"></i> Completed';
            submitForm26Btn.classList.remove('d-none');
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26';
            saveAllBtn.classList.remove('d-none');
        } else if (currentRecordStatus === 'partial') {
            statusChip.classList.add('status-partial');
            statusChip.innerHTML = '<i class="fa-solid fa-list-check"></i> Partial';
            submitForm26Btn.classList.add('d-none');
            saveAllBtn.classList.remove('d-none');
        } else {
            statusChip.classList.add('status-draft');
            statusChip.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Draft';
            submitForm26Btn.classList.add('d-none');
            saveAllBtn.classList.remove('d-none');
        }
    }

    function getSection(step) {
        return document.getElementById(`section${step}`);
    }

    function showSection(step, show) {
        const section = getSection(step);
        if (section) {
            section.classList.toggle('d-none', !show);
        }
    }

    function hideAllSections() {
        for (let i = 1; i <= 8; i++) {
            showSection(i, false);
        }
    }

    function showAllSections() {
        for (let i = 1; i <= 8; i++) {
            showSection(i, true);
        }
    }

    function setSectionEditable(step, editable) {
        const section = getSection(step);
        if (!section) return;

        section.classList.toggle('section-locked', !editable);

        const controls = section.querySelectorAll('input, select, textarea');
        controls.forEach((control) => {
            const name = control.getAttribute('name') || '';
            if (name === 'serial_no' || name === 'clims_id' || name === 'bmi') {
                control.disabled = true;
                return;
            }
            control.disabled = !editable;
        });
    }

    function setAllSectionsEditable(editable) {
        for (let i = 1; i <= 8; i++) {
            setSectionEditable(i, editable);
        }
    }

    function storeOriginalValues(step) {
        const fields = getContainerFields(step);
        const originals = {};

        fields.forEach(field => {
            originals[field] = getInputValue(field);
        });

        if (!window.originalValues) {
            window.originalValues = {};
        }
        window.originalValues[step] = originals;
    }

    function restoreOriginalValues(step) {
        const originals = window.originalValues?.[step];
        if (!originals) return;

        Object.entries(originals).forEach(([field, value]) => {
            setInputValue(field, value);
        });
    }

    function clearOriginalValues(step) {
        if (window.originalValues) {
            delete window.originalValues[step];
        }
    }

    function getContainerFields(step) {
        const fieldMap = {
            1: ['serial_no', 'attested_by_eic', 'exam_date', 'full_name', 'age_sex', 'aadhar_no', 'address', 'mobile_no', 'demo_exam_date', 'contractor_agency', 'clims_id', 'ntpc_eic'],
            2: ['diabetes', 'hypertension', 'vertigo', 'epilepsy', 'height_phobia', 'skin_diseases', 'asthma', 'alcohol_intake', 'mental_illness', 'tobacco_chewing', 'cancer', 'piles', 'hearing_problem', 'chronic_illness', 'deformity', 'past_accident', 'smoking', 'medicine_history', 'contractor_signature'],
            3: ['height', 'weight', 'bp', 'chest_insp', 'chest_exp', 'pulse_spo2_temp', 'pallor', 'icterus', 'clubbing', 'built', 'tongue', 'teeth', 'other_finding'],
            4: ['cardio_system', 'respiratory_system', 'cns', 'system_other'],
            5: ['distant_r_with', 'distant_r_without', 'distant_l_with', 'distant_l_without', 'near_r_with', 'near_r_without', 'near_l_with', 'near_l_without', 'colour_vision', 'eye_disorder'],
            6: ['lmp', 'menstrual_cycle', 'pregnancy_duration'],
            7: ['cbc', 'random_blood_sugar', 'urine_rm', 'blood_group', 'lft_kft', 'ecg', 'chest_xray', 'height_pass_test', 'other_tests'],
            8: ['opinion', 'remarks', 'worker_signature', 'doctor_signature']
        };
        return fieldMap[step] || [];
    }

    function toggleEdit(containerId) {
        const section = document.getElementById(containerId);
        if (!section) return;

        const step = parseInt(section.dataset.step);
        const editBtn = section.querySelector('.edit-btn');
        const saveBtn = section.querySelector('.save-btn');

        if (!editBtn || !saveBtn) return;

        storeOriginalValues(step);
        setSectionEditable(step, true);

        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
    }

    async function saveSection(containerId) {
        const section = document.getElementById(containerId);
        if (!section) return;

        const step = parseInt(section.dataset.step);
        const editBtn = section.querySelector('.edit-btn');
        const saveBtn = section.querySelector('.save-btn');

        if (!editBtn || !saveBtn) return;

        if (!climsIdInput.value.trim()) {
            showToast('CLIMS ID is required.', 'warning');
            return;
        }

        if (step === 1 && !getInputValue('full_name').trim()) {
            showToast('Name is required in demographics.', 'warning');
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('action', 'save_container');
        fd.append('container', step);
        fd.append('clims_id', climsIdInput.value.trim());

        const fields = getContainerFields(step);
        fields.forEach(field => {
            fd.append(field, getInputValue(field));
        });

        if (step === 1 && workerPhotoInput.files && workerPhotoInput.files[0]) {
            fd.append('worker_photo', workerPhotoInput.files[0]);
        }

        setLoading(true, 'Saving section...');

        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: fd
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Unable to save section.');
            }

            if (data.id && !examIdInput.value) {
                examIdInput.value = String(data.id);
            }

            if (data.serial_no) {
                serialInput.value = data.serial_no;
            }

            setSectionEditable(step, false);
            setStatusChip(data.record_status || 'draft');

            editBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
            clearOriginalValues(step);

            showToast('Section saved successfully.', 'success');
        } catch (error) {
            restoreOriginalValues(step);
            showToast(error.message || 'Unable to save section.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    function fillForm(data) {
        Object.entries(data || {}).forEach(([name, value]) => {
            if (name === 'worker_photo') {
                if (value) {
                    workerPhotoPreview.src = value;
                    workerPhotoPreview.style.display = 'inline-block';
                }
                return;
            }
            setInputValue(name, value);
        });
        calculateBMI();
    }

    function clearForm() {
        const allInputs = form.querySelectorAll('input, select, textarea');
        allInputs.forEach(input => {
            if (input.type === 'radio') {
                input.checked = false;
            } else if (input.type === 'file') {
                input.value = '';
            } else if (input.id !== 'csrf_token') {
                input.value = '';
            }
        });
        workerPhotoPreview.src = '';
        workerPhotoPreview.style.display = 'none';
        examIdInput.value = '';
    }

    function calculateBMI() {
        const height = parseFloat(getInputValue('height'));
        const weight = parseFloat(getInputValue('weight'));

        if (height && weight && height > 0 && weight > 0) {
            const bmi = weight / ((height / 100) * (height / 100));
            setInputValue('bmi', bmi.toFixed(2));
        } else {
            setInputValue('bmi', '');
        }
    }

    async function searchRecord() {
        const climsId = searchClimsInput.value.trim();
        if (!climsId) {
            showToast('Enter CLIMS ID to search.', 'warning');
            return;
        }

        setLoading(true, 'Searching CLIMS ID...');

        try {
            const fd = new FormData();
            fd.append('csrf_token', app.csrfToken || '');
            fd.append('clims_id', climsId);

            const response = await fetch(app.lookupUrl, {
                method: 'POST',
                body: fd
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Unable to process search.');
            }

            currentClimsId = climsId;

            if (data.is_test_mode) {
                setFeedback('⚠️ TEST MODE: Using dummy CLIMS ID for local testing.', 'warning');
                showToast('Test mode active. Data will be saved locally.', 'warning');
            } else {
                setFeedback('', '');
            }

            clearForm();
            fillForm(data.data || {});
            examIdInput.value = String(data.id || '');
            setInputValue('clims_id', climsId);
            setStatusChip(data.record_status || 'draft');

            showAllSections();
            setAllSectionsEditable(true);

            recordInfo.textContent = `Record ID: ${data.id || '-'} | CLIMS ID: ${climsId}`;

            if (!data.found && data.created_new) {
                showToast('New record created. Fill the form and save.', 'info');
            } else {
                showToast('Record loaded successfully.', 'success');
            }
        } catch (error) {
            showToast(error.message || 'Search failed.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    function refreshData() {
        if (!currentClimsId) {
            showToast('No CLIMS ID loaded. Please search first.', 'warning');
            return;
        }
        setFeedback('Refreshing data from database...', 'info');
        searchRecord();
    }

    async function saveAllChanges() {
        if (!climsIdInput.value.trim()) {
            showToast('CLIMS ID missing. Please search first.', 'warning');
            return;
        }

        if (!getInputValue('full_name').trim()) {
            showToast('Name cannot be empty.', 'warning');
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('action', 'save_all');
        fd.append('clims_id', climsIdInput.value.trim());

        const allFields = [
            'serial_no', 'attested_by_eic', 'exam_date', 'full_name', 'age_sex', 'aadhar_no',
            'address', 'mobile_no', 'demo_exam_date', 'contractor_agency', 'ntpc_eic',
            'diabetes', 'hypertension', 'vertigo', 'epilepsy', 'height_phobia', 'skin_diseases',
            'asthma', 'alcohol_intake', 'mental_illness', 'tobacco_chewing', 'cancer', 'piles',
            'hearing_problem', 'chronic_illness', 'deformity', 'past_accident', 'smoking',
            'medicine_history', 'contractor_signature', 'height', 'weight', 'bp', 'chest_insp',
            'chest_exp', 'pulse_spo2_temp', 'pallor', 'icterus', 'clubbing', 'built', 'tongue',
            'teeth', 'other_finding', 'cardio_system', 'respiratory_system', 'cns', 'system_other',
            'distant_r_with', 'distant_r_without', 'distant_l_with', 'distant_l_without',
            'near_r_with', 'near_r_without', 'near_l_with', 'near_l_without', 'colour_vision',
            'eye_disorder', 'lmp', 'menstrual_cycle', 'pregnancy_duration', 'cbc',
            'random_blood_sugar', 'urine_rm', 'blood_group', 'lft_kft', 'ecg', 'chest_xray',
            'height_pass_test', 'other_tests', 'opinion', 'remarks', 'worker_signature', 'doctor_signature'
        ];

        allFields.forEach(field => {
            fd.append(field, getInputValue(field));
        });

        if (workerPhotoInput.files && workerPhotoInput.files[0]) {
            fd.append('worker_photo', workerPhotoInput.files[0]);
        }

        setLoading(true, 'Saving all changes...');

        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: fd
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Unable to save changes.');
            }

            examIdInput.value = String(data.id || examIdInput.value);
            if (data.serial_no) serialInput.value = data.serial_no;
            if (data.worker_photo) {
                workerPhotoPreview.src = data.worker_photo;
                workerPhotoPreview.style.display = 'inline-block';
            }

            setStatusChip(data.record_status || 'draft');
            showToast('All changes saved successfully.', 'success');
        } catch (error) {
            showToast(error.message || 'Unable to save changes.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function submitToForm26() {
        const examId = examIdInput.value.trim();

        if (currentRecordStatus === 'submitted') {
            if (!examId) {
                showToast('Examination ID missing.', 'danger');
                return;
            }
            window.location.href = `${app.form26Url}?examination_id=${encodeURIComponent(examId)}`;
            return;
        }

        if (currentRecordStatus !== 'completed') {
            showToast('Please complete all 8 containers before submission.', 'warning');
            return;
        }

        const climsId = climsIdInput.value.trim();
        if (!climsId) {
            showToast('CLIMS ID missing.', 'warning');
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('action', 'final_submit');
        fd.append('clims_id', climsId);

        setLoading(true, 'Submitting to FORM 26...');

        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: fd
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Submission failed.');
            }

            setStatusChip('submitted');
            showToast('Submitted successfully. Redirecting...', 'success');

            const redirectUrl = data.redirect_url || `${app.form26Url}?examination_id=${encodeURIComponent(data.id || examId)}`;
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 900);
        } catch (error) {
            showToast(error.message || 'Submission failed.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    function initializeEventListeners() {
        // Search button
        searchBtn.addEventListener('click', searchRecord);

        // Enter key on search input
        searchClimsInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchRecord();
            }
        });

        // Refresh button
        if (refreshBtn) {
            refreshBtn.addEventListener('click', refreshData);
        }

        // Save All button
        if (saveAllBtn) {
            saveAllBtn.addEventListener('click', saveAllChanges);
        }

        // Submit to FORM 26 button
        if (submitForm26Btn) {
            submitForm26Btn.addEventListener('click', submitToForm26);
        }

        // Edit and Save buttons for each section
        for (let i = 1; i <= 8; i++) {
            const section = document.getElementById(`section${i}`);
            if (section) {
                const editBtn = section.querySelector('.edit-btn');
                const saveBtn = section.querySelector('.save-btn');

                if (editBtn) {
                    editBtn.addEventListener('click', () => toggleEdit(`section${i}`));
                }
                if (saveBtn) {
                    saveBtn.addEventListener('click', () => saveSection(`section${i}`));
                }
            }
        }

        // BMI calculation
        const heightField = getField('height');
        const weightField = getField('weight');
        if (heightField) heightField.addEventListener('input', calculateBMI);
        if (weightField) weightField.addEventListener('input', calculateBMI);
    }

    function initializeApp() {
        hideAllSections();
        setStatusChip('draft');
        recordInfo.textContent = 'Search CLIMS ID to begin';
        setFeedback('', '');
        currentClimsId = null;
        initializeEventListeners();
    }

    initializeApp();
})();