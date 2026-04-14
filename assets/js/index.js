(() => {
    'use strict';

    const app = window.CLIMS_APP || {};
    const form = document.getElementById('medicalForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const toastContainer = document.getElementById('toastContainer');

    const searchBtn = document.getElementById('searchBtn');
    const searchClimsInput = document.getElementById('search_clims_id');
    const searchFeedback = document.getElementById('searchFeedback');

    const createNewRecordBtn = document.getElementById('createNewRecordBtn');
    const editModeBtn = document.getElementById('editModeBtn');
    const saveAllChangesBtn = document.getElementById('saveAllChangesBtn');
    const submitForm26Btn = document.getElementById('submitForm26Btn');
    const resetWorkflowBtn = document.getElementById('resetWorkflowBtn');

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

    const sectionButtons = Array.from(document.querySelectorAll('.save-section-btn'));

    let mode = 'search';
    let currentStep = 1;
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
            searchFeedback.className = 'mt-3 d-none';
            searchFeedback.textContent = '';
            return;
        }

        searchFeedback.className = 'mt-3 alert alert-' + type;
        searchFeedback.textContent = message;
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

    function setAllSectionsEditable(editable) {
        for (let i = 1; i <= 8; i += 1) {
            setSectionEditable(i, editable);
        }
    }

    function showPerContainerSaveButtons(showStep) {
        sectionButtons.forEach((btn) => {
            const step = Number(btn.dataset.step || '0');
            btn.classList.toggle('d-none', step !== showStep);
            btn.disabled = step !== showStep;
        });
    }

    function hideAllContainerSaveButtons() {
        sectionButtons.forEach((btn) => {
            btn.classList.add('d-none');
            btn.disabled = true;
        });
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
        createNewRecordBtn.classList.toggle('d-none', !config.create);
        editModeBtn.classList.toggle('d-none', !config.edit);
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

    function prepareCreateMode(climsId) {
        mode = 'create';
        currentStep = 1;
        recordStatus = 'draft';

        form.reset();
        examIdInput.value = '';
        serialInput.value = generateSerialNumber();
        setInputValue('exam_date', today);
        setInputValue('demo_exam_date', today);
        setInputValue('clims_id', climsId);

        workerPhotoPreview.src = '';
        workerPhotoPreview.style.display = 'none';

        hideAllSections();
        showSection(1, true);
        setSectionEditable(1, true);
        showPerContainerSaveButtons(1);

        for (let i = 2; i <= 8; i += 1) {
            setSectionEditable(i, false);
            showSection(i, false);
        }

        setStatusChip('draft');
        recordInfo.textContent = 'Creating new record for CLIMS ID: ' + climsId;

        setBaseActionsVisibility({
            create: false,
            edit: false,
            saveAll: false,
            submit: false
        });

        setFeedback('No record found. Creating new record for CLIMS ID: ' + climsId, 'info');
        showToast('Fill demographics and click Save Demographics to continue.', 'info');
    }

    function applyCreateProgress(nextContainer, status) {
        setStatusChip(status || 'partial');

        if ((status || '') === 'completed') {
            currentStep = 8;
            showAllSections();
            setAllSectionsEditable(false);
            hideAllContainerSaveButtons();
            setBaseActionsVisibility({
                create: false,
                edit: false,
                saveAll: false,
                submit: true
            });
            setFeedback('All containers saved successfully. Click "Submit to FORM 26".', 'success');
            return;
        }

        currentStep = Number(nextContainer || 1);
        if (currentStep < 1 || currentStep > 8) {
            currentStep = 1;
        }

        for (let i = 1; i <= 8; i += 1) {
            if (i <= currentStep) {
                showSection(i, true);
            } else {
                showSection(i, false);
            }

            if (i < currentStep) {
                setSectionEditable(i, false);
                continue;
            }

            if (i === currentStep) {
                setSectionEditable(i, true);
                continue;
            }

            setSectionEditable(i, false);
        }

        showPerContainerSaveButtons(currentStep);
        setBaseActionsVisibility({
            create: false,
            edit: false,
            saveAll: false,
            submit: false
        });
    }

    function applyFoundReadOnly(data, status) {
        mode = 'view';
        showAllSections();
        setAllSectionsEditable(false);
        hideAllContainerSaveButtons();

        const isSubmitted = status === 'submitted';

        setBaseActionsVisibility({
            create: false,
            edit: !isSubmitted,
            saveAll: false,
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

    function enterEditMode() {
        if (mode !== 'view') {
            return;
        }

        loadedSnapshot = collectSnapshot();
        mode = 'edit';

        setAllSectionsEditable(true);
        hideAllContainerSaveButtons();

        editModeBtn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Cancel Edit Mode';

        setBaseActionsVisibility({
            create: false,
            edit: true,
            saveAll: true,
            submit: false
        });

        setFeedback('Edit mode enabled. Update fields and click "Save Changes".', 'warning');
    }

    function cancelEditMode() {
        if (mode !== 'edit') {
            return;
        }

        applySnapshot(loadedSnapshot);
        mode = 'view';
        setAllSectionsEditable(false);
        hideAllContainerSaveButtons();

        editModeBtn.innerHTML = '<i class="fa-solid fa-pen me-1"></i>Edit Mode';

        setBaseActionsVisibility({
            create: false,
            edit: true,
            saveAll: false,
            submit: recordStatus === 'completed' || recordStatus === 'submitted'
        });

        if (recordStatus === 'submitted') {
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-folder-open me-1"></i>Open FORM 26';
        } else {
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26';
        }

        setFeedback('Edit mode cancelled. Record is read-only.', 'info');
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
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Unable to process search.');
            }

            if (!data.found) {
                mode = 'search_not_found';
                hideAllSections();
                setStatusChip('draft');
                recordInfo.textContent = 'No existing record for CLIMS ID: ' + climsId;
                setFeedback('No record found. Create new record?', 'warning');
                setBaseActionsVisibility({
                    create: true,
                    edit: false,
                    saveAll: false,
                    submit: false
                });
                return;
            }

            form.reset();
            fillForm(data.data || {});
            examIdInput.value = String(data.id || '');
            setStatusChip(data.record_status || 'draft');
            applyFoundReadOnly(data.data || {}, data.record_status || 'draft');
            loadedSnapshot = collectSnapshot();

            setFeedback('Record found for CLIMS ID: ' + climsId, 'success');
            showToast('Record loaded successfully.', 'success');
        } catch (error) {
            showToast(error.message || 'Search failed.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function saveContainer(step) {
        if (mode !== 'create') {
            showToast('Container save is available only during new record creation.', 'warning');
            return;
        }

        if (step !== currentStep) {
            showToast('Please save the currently unlocked container only.', 'warning');
            return;
        }

        if (!validateContainer(step)) {
            return;
        }

        const payload = buildContainerPayload(step);
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

            examIdInput.value = String(data.id || examIdInput.value || '');
            if (data.serial_no) {
                serialInput.value = data.serial_no;
            }
            if (data.worker_photo) {
                workerPhotoPreview.src = data.worker_photo;
                workerPhotoPreview.style.display = 'inline-block';
            }

            applyCreateProgress(Number(data.current_container || (step + 1)), data.record_status || 'partial');
            searchClimsInput.value = climsIdInput.value.trim();
            showToast(data.message || ('Container ' + step + ' saved.'), 'success');

            if ((data.record_status || '') !== 'completed') {
                const next = getSection(currentStep);
                if (next) {
                    next.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        } catch (error) {
            showToast(error.message || 'Unable to save container.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function saveAllChanges() {
        if (mode !== 'edit') {
            showToast('Enable Edit Mode first.', 'warning');
            return;
        }

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
            const data = await response.json();

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

            mode = 'view';
            setAllSectionsEditable(false);
            hideAllContainerSaveButtons();

            editModeBtn.innerHTML = '<i class="fa-solid fa-pen me-1"></i>Edit Mode';
            setBaseActionsVisibility({
                create: false,
                edit: (data.record_status || '') !== 'submitted',
                saveAll: false,
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
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Submission failed.');
            }

            setStatusChip('submitted');
            mode = 'view';
            setAllSectionsEditable(false);
            hideAllContainerSaveButtons();
            setBaseActionsVisibility({
                create: false,
                edit: false,
                saveAll: false,
                submit: true
            });
            submitForm26Btn.innerHTML = '<i class="fa-solid fa-folder-open me-1"></i>Open FORM 26';

            showToast(data.message || 'Submitted successfully. Redirecting...', 'success');

            const redirectUrl = data.redirect_url || (app.form26Url + '?examination_id=' + encodeURIComponent(data.id || examId));
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

    function resetScreen() {
        mode = 'search';
        currentStep = 1;
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
        hideAllContainerSaveButtons();

        editModeBtn.innerHTML = '<i class="fa-solid fa-pen me-1"></i>Edit Mode';
        submitForm26Btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Submit to FORM 26';

        setStatusChip('draft');
        recordInfo.textContent = 'Search CLIMS ID to begin';
        setFeedback('', 'info');

        setBaseActionsVisibility({
            create: false,
            edit: false,
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

    createNewRecordBtn.addEventListener('click', () => {
        const climsId = searchClimsInput.value.trim();
        if (!climsId) {
            showToast('Enter CLIMS ID before creating a new record.', 'warning');
            return;
        }
        prepareCreateMode(climsId);
    });

    editModeBtn.addEventListener('click', () => {
        if (mode === 'view') {
            enterEditMode();
            return;
        }
        if (mode === 'edit') {
            cancelEditMode();
        }
    });

    saveAllChangesBtn.addEventListener('click', saveAllChanges);

    submitForm26Btn.addEventListener('click', submitToForm26);

    resetWorkflowBtn.addEventListener('click', () => {
        const confirmReset = window.confirm('Reset the screen to initial search-only mode?');
        if (!confirmReset) {
            return;
        }
        resetScreen();
    });

    sectionButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const step = Number(btn.dataset.step || '0');
            if (!step) {
                return;
            }
            saveContainer(step);
        });
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

    resetScreen();
})();
