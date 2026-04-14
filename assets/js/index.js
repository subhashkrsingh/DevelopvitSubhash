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
    const saveAllChangesBtn = document.getElementById('saveAllChangesBtn');
    const submitForm26Btn = document.getElementById('submitForm26Btn');

    const statusChip = document.getElementById('recordStatus');
    const recordInfo = document.getElementById('recordInfo');
    const examIdInput = document.getElementById('examination_id');
    const serialInput = document.getElementById('serial_no');
    const climsIdInput = document.getElementById('clims_id');

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

    const TEST_MODE_MESSAGE = '⚠️ TEST MODE: Using dummy CLIMS ID. This is for local testing only.';

    let mode = 'search';
    let recordStatus = 'draft';
    let loadedSnapshot = null;

    function flattenFields() {
        const all = [];
        Object.values(fieldMap).forEach((arr) => {
            arr.forEach((f) => {
                if (!all.includes(f)) {
                    all.push(f);
                }
            });
        });
        return all;
    }

    const allFields = flattenFields();

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
        return form.querySelector('[name="' + name + '"]');
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

        searchStatus.className = 'mt-3 alert alert-' + type;
        searchStatus.textContent = message;
    }

    function setStatusChip(status) {
        recordStatus = status || 'draft';
        statusChip.classList.remove('status-draft', 'status-partial', 'status-completed', 'status-submitted');

        if (recordStatus === 'submitted') {
            statusChip.classList.add('status-submitted');
            statusChip.innerHTML = '<i class="fa-solid fa-circle-check"></i> Submitted';
            return;
        }

        if (recordStatus === 'completed') {
            statusChip.classList.add('status-completed');
            statusChip.innerHTML = '<i class="fa-solid fa-check"></i> Completed';
            return;
        }

        if (recordStatus === 'partial') {
            statusChip.classList.add('status-partial');
            statusChip.innerHTML = '<i class="fa-solid fa-list-check"></i> Partial';
            return;
        }

        statusChip.classList.add('status-draft');
        statusChip.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Draft';
    }

    function getSection(step) {
        return document.getElementById('section' + step);
    }

    function showSection(step, show) {
        const section = getSection(step);
        if (!section) {
            return;
        }
        section.classList.toggle('d-none', !show);
    }

    function hideAllSections() {
        for (let i = 1; i <= 8; i += 1) {
            showSection(i, false);
        }
    }

    function showAllSections() {
        for (let i = 1; i <= 8; i += 1) {
            showSection(i, true);
        }
    }

    function setSectionEditable(step, editable) {
        const section = getSection(step);
        if (!section) {
            return;
        }

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

    function toggleEdit(containerId) {
        const section = document.getElementById(containerId);
        if (!section) {
            return;
        }

        const step = Number(section.dataset.step || containerId.replace(/\D/g, '')) || 0;
        const editBtn = section.querySelector('.edit-btn');
        const saveBtn = section.querySelector('.save-btn');
        const cancelBtn = section.querySelector('.cancel-btn');

        if (!editBtn || !saveBtn || !cancelBtn) {
            return;
        }

        // Store original values for cancel functionality
        storeOriginalValues(step);

        // Enable editing
        setSectionEditable(step, true);
        section.classList.add('editing');

        // Toggle button visibility
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
        cancelBtn.classList.remove('d-none');
    }

    async function saveSection(containerId) {
        const section = document.getElementById(containerId);
        if (!section) {
            return;
        }

        const step = Number(section.dataset.step || containerId.replace(/\D/g, '')) || 0;

        if (!validateContainer(step)) {
            return;
        }

        const editBtn = section.querySelector('.edit-btn');
        const saveBtn = section.querySelector('.save-btn');
        const cancelBtn = section.querySelector('.cancel-btn');

        if (!editBtn || !saveBtn || !cancelBtn) {
            return;
        }

        // Save the data
        const payload = buildContainerPayload(step);
        setLoading(true, 'Saving section...');

        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: payload
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Unable to save section.');
            }

            // Update examination_id if it's a new record
            if (data.id && !examIdInput.value) {
                examIdInput.value = String(data.id);
            }

            // Disable editing
            setSectionEditable(step, false);
            section.classList.remove('editing');

            // Clear stored original values
            clearOriginalValues(step);

            // Toggle button visibility
            editBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
            cancelBtn.classList.add('d-none');

            showToast('Section saved successfully.', 'success');
        } catch (error) {
            showToast(error.message || 'Unable to save section.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    function cancelSection(containerId) {
        const section = document.getElementById(containerId);
        if (!section) {
            return;
        }

        const step = Number(section.dataset.step || containerId.replace(/\D/g, '')) || 0;
        const editBtn = section.querySelector('.edit-btn');
        const saveBtn = section.querySelector('.save-btn');
        const cancelBtn = section.querySelector('.cancel-btn');

        if (!editBtn || !saveBtn || !cancelBtn) {
            return;
        }

        // Restore original values
        restoreOriginalValues(step);

        // Disable editing
        setSectionEditable(step, false);
        section.classList.remove('editing');

        // Clear stored original values
        clearOriginalValues(step);

        // Toggle button visibility
        editBtn.classList.remove('d-none');
        saveBtn.classList.add('d-none');
        cancelBtn.classList.add('d-none');
    }

    function storeOriginalValues(step) {
        const fields = fieldMap[step] || [];
        const originals = {};

        fields.forEach(field => {
            const elements = form.querySelectorAll(`[name="${field}"]`);
            if (elements.length > 0) {
                if (elements[0].type === 'radio') {
                    const checked = Array.from(elements).find(el => el.checked);
                    originals[field] = checked ? checked.value : '';
                } else if (elements[0].type === 'checkbox') {
                    originals[field] = Array.from(elements).filter(el => el.checked).map(el => el.value);
                } else {
                    originals[field] = elements[0].value;
                }
            }
        });

        // Store in a global object
        if (!window.originalValues) {
            window.originalValues = {};
        }
        window.originalValues[step] = originals;
    }

    function restoreOriginalValues(step) {
        const originals = window.originalValues && window.originalValues[step];
        if (!originals) return;

        Object.entries(originals).forEach(([field, value]) => {
            const elements = form.querySelectorAll(`[name="${field}"]`);
            if (elements.length > 0) {
                if (elements[0].type === 'radio') {
                    elements.forEach(el => {
                        el.checked = el.value === value;
                    });
                } else if (elements[0].type === 'checkbox') {
                    elements.forEach(el => {
                        el.checked = value.includes(el.value);
                    });
                } else {
                    elements[0].value = value;
                }
            }
        });
    }

    function clearOriginalValues(step) {
        if (window.originalValues) {
            delete window.originalValues[step];
        }
    }

    function setAllSectionsEditable(editable) {
        for (let i = 1; i <= 8; i += 1) {
            setSectionEditable(i, editable);
        }
    }

    function parseValueForField(value) {
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

            if (nodes[0].type === 'file') {
                return;
            }

            if (nodes[0].type === 'radio') {
                nodes.forEach((radio) => {
                    radio.checked = String(radio.value) === String(value);
                });
                return;
            }

            nodes[0].value = parseValueForField(value);
        });

        if (data.worker_photo) {
            workerPhotoPreview.src = data.worker_photo;
            workerPhotoPreview.style.display = 'inline-block';
        } else {
            workerPhotoPreview.src = '';
            workerPhotoPreview.style.display = 'none';
        }

        calculateBMI();
    }

    function collectSnapshot() {
        const snapshot = {};
        allFields.forEach((field) => {
            if (field === 'worker_photo') {
                return;
            }
            snapshot[field] = getInputValue(field);
        });
        snapshot.id = examIdInput.value;
        snapshot.worker_photo = workerPhotoPreview.src || '';
        return snapshot;
    }

    function applySnapshot(snapshot) {
        if (!snapshot) {
            return;
        }

        Object.entries(snapshot).forEach(([name, value]) => {
            if (name === 'worker_photo' || name === 'id') {
                return;
            }
            setInputValue(name, value);
        });

        examIdInput.value = snapshot.id || '';

        if (snapshot.worker_photo) {
            workerPhotoPreview.src = snapshot.worker_photo;
            workerPhotoPreview.style.display = 'inline-block';
        } else {
            workerPhotoPreview.src = '';
            workerPhotoPreview.style.display = 'none';
        }

        calculateBMI();
    }

    function setBaseActionsVisibility(config) {
        saveAllChangesBtn.classList.toggle('d-none', !config.saveAll);
        submitForm26Btn.classList.toggle('d-none', !config.submit);
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

    function validateContainer(step) {
        if (!climsIdInput.value.trim()) {
            showToast('CLIMS ID is required.', 'warning');
            return false;
        }

        if (step === 1 && !getInputValue('full_name').trim()) {
            showToast('Name is required in demographics.', 'warning');
            getField('full_name').focus();
            return false;
        }

        if (step === 6 && !isStepDataPresent(6)) {
            setInputValue('menstrual_cycle', 'Not Applicable');
            return true;
        }

        if (!isStepDataPresent(step)) {
            showToast('Please fill at least one field in this container before saving.', 'warning');
            return false;
        }

        return true;
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

    function buildContainerPayload(step) {
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

    function buildSaveAllPayload() {
        const fd = new FormData();
        fd.append('csrf_token', app.csrfToken || '');
        fd.append('action', 'save_all');
        fd.append('clims_id', climsIdInput.value.trim());
        fd.append('examination_id', examIdInput.value || '');

        allFields.forEach((field) => {
            if (field === 'clims_id') {
                return;
            }
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

    function prepareCreateMode(climsId, options = {}) {
        mode = 'edit';
        recordStatus = 'draft';

        form.reset();
        examIdInput.value = options.id ? String(options.id) : '';
        serialInput.value = options.serial_no || generateSerialNumber();
        setInputValue('exam_date', today);
        setInputValue('demo_exam_date', today);
        setInputValue('clims_id', climsId);

        if (options.prefill && typeof options.prefill === 'object') {
            fillForm(options.prefill);
            examIdInput.value = options.id ? String(options.id) : examIdInput.value;
            serialInput.value = options.serial_no || getInputValue('serial_no') || serialInput.value;
            setInputValue('clims_id', climsId);
        }

        workerPhotoPreview.src = '';
        workerPhotoPreview.style.display = 'none';

        showAllSections();
        setAllSectionsEditable(true);

        setStatusChip('draft');
        recordInfo.textContent = 'New draft record ID: ' + (examIdInput.value || '-') + ' | CLIMS ID: ' + climsId;

        setBaseActionsVisibility({
            saveAll: true,
            submit: false
        });

        if (options.is_test_mode) {
            setFeedback(TEST_MODE_MESSAGE, 'warning');
            showToast(TEST_MODE_MESSAGE, 'warning');
        } else {
            setFeedback('No record found. New draft record created for CLIMS ID: ' + climsId, 'info');
            showToast('No record found. New draft created. Fill the form and click Save All Changes.', 'info');
        }
    }


    function applyFoundReadOnly(data, status) {
        mode = 'edit';
        showAllSections();
        setAllSectionsEditable(true);

        const isSubmitted = status === 'submitted';

        setBaseActionsVisibility({
            saveAll: true,
            submit: status === 'completed'
        });

        if (isSubmitted) {
            submitForm26Btn.classList.remove('d-none');
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-folder-open me-1"></i>Open FORM 26';
        } else {
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26';
        }

        recordInfo.textContent = 'Record ID: ' + (data.id || '') + ' | CLIMS ID: ' + (data.clims_id || '');
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

            const warningMessage = data.is_test_mode
                ? '⚠️ TEST MODE: Using dummy CLIMS ID. This is for local testing only.'
                : '';

            if (!data.found) {
                prepareCreateMode(climsId, {
                    id: data.id || '',
                    serial_no: (data.data && data.data.serial_no) ? data.data.serial_no : '',
                    is_test_mode: Boolean(data.is_test_mode),
                    prefill: data.data || null
                });
                return;
            }

            form.reset();
            fillForm(data.data || {});
            examIdInput.value = String(data.id || '');
            setStatusChip(data.record_status || 'draft');
            applyFoundReadOnly(data.data || {}, data.record_status || 'draft');
            loadedSnapshot = collectSnapshot();

            if (data.is_test_mode) {
                setFeedback(warningMessage || TEST_MODE_MESSAGE, 'warning');
                showToast('Loaded existing dummy test record.', 'warning');
            } else {
                setFeedback('Record found for CLIMS ID: ' + climsId, 'success');
                showToast('Record loaded successfully.', 'success');
            }
        } catch (error) {
            showToast(error.message || 'Search failed.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    function refreshData() {
        const climsId = searchClimsInput.value.trim();
        if (!climsId) {
            showToast('Enter CLIMS ID to refresh.', 'warning');
            return;
        }
        setFeedback('Refreshing data from database...', 'info');
        searchRecord();
    }

    async function saveAllChanges() {
        if (!climsIdInput.value.trim()) {
            showToast('CLIMS ID missing.', 'warning');
            return;
        }

        if (!getInputValue('full_name').trim()) {
            showToast('Name cannot be empty.', 'warning');
            return;
        }

        const payload = buildSaveAllPayload();
        setLoading(true, 'Saving all changes...');

        try {
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: payload
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Unable to save changes.');
            }

            examIdInput.value = String(data.id || examIdInput.value || '');
            if (data.serial_no) {
                serialInput.value = data.serial_no;
            }
            if (data.worker_photo) {
                workerPhotoPreview.src = data.worker_photo;
                workerPhotoPreview.style.display = 'inline-block';
            }

            setStatusChip(data.record_status || 'partial');
            loadedSnapshot = collectSnapshot();

            mode = 'edit';
            setAllSectionsEditable(true);

            setBaseActionsVisibility({
                saveAll: true,
                submit: (data.record_status || '') === 'completed' || (data.record_status || '') === 'submitted'
            });

            if ((data.record_status || '') === 'submitted') {
                submitForm26Btn.innerHTML = '<i class="fa-solid fa-folder-open me-1"></i>Open FORM 26';
            } else {
                submitForm26Btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26';
            }

            setFeedback('Changes saved successfully for CLIMS ID: ' + climsIdInput.value.trim(), 'success');
            showToast(data.message || 'Changes saved.', 'success');
        } catch (error) {
            showToast(error.message || 'Unable to save changes.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function submitToForm26() {
        const examId = examIdInput.value.trim();

        if (recordStatus === 'submitted') {
            if (!examId) {
                showToast('Examination ID missing for Form 26 redirect.', 'danger');
                return;
            }
            window.location.href = app.form26Url + '?examination_id=' + encodeURIComponent(examId);
            return;
        }

        if (recordStatus !== 'completed') {
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
            mode = 'view';
            setAllSectionsEditable(false);
            setBaseActionsVisibility({
                saveAll: false,
                submit: true
            });
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-folder-open me-1"></i>Open FORM 26';

            showToast(data.message || 'Submitted successfully. Redirecting...', 'success');

            const redirectUrl = data.redirect_url || (app.form26Url + '?examination_id=' + encodeURIComponent(data.id || examId));
            // Store examination ID in session for Form 26
            sessionStorage.setItem('current_examination_id', data.id || examId);
            window.setTimeout(() => {
                window.location.href = redirectUrl;
            }, 900);
        } catch (error) {
            showToast(error.message || 'Submission failed.', 'danger');
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

    function initializeApp() {
        mode = 'search';
        recordStatus = 'draft';
        loadedSnapshot = null;

        form.reset();
        examIdInput.value = '';
        serialInput.value = '';
        climsIdInput.value = '';
        searchClimsInput.value = '';
        workerPhotoPreview.src = '';
        workerPhotoPreview.style.display = 'none';

        hideAllSections();
        submitForm26Btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26';

        setStatusChip('draft');
        recordInfo.textContent = 'Search CLIMS ID to begin';
        setFeedback('', 'info');

        setBaseActionsVisibility({
            saveAll: false,
            submit: false
        });
    }

    searchBtn.addEventListener('click', searchRecord);

    searchClimsInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchRecord();
        }
    });

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshData);
    }

    saveAllChangesBtn.addEventListener('click', saveAllChanges);

        submitForm26Btn.addEventListener('click', submitToForm26);

        // Event delegation for per-container edit buttons
        document.addEventListener('click', (event) => {
            const target = event.target.closest('.edit-btn, .save-btn, .cancel-btn');
            if (!target) return;

            event.preventDefault();
            const containerId = target.getAttribute('data-target');
            if (!containerId) return;

            if (target.classList.contains('edit-btn')) {
                toggleEdit(containerId);
            } else if (target.classList.contains('save-btn')) {
                saveSection(containerId);
            } else if (target.classList.contains('cancel-btn')) {
                cancelSection(containerId);
            }
        });

        window.toggleEdit = toggleEdit;
        window.searchPatient = searchRecord;
        window.refreshData = refreshData;

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

    initializeApp();
})();
