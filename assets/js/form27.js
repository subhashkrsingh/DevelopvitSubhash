(() => {
    'use strict';

    const app = window.FORM27_APP || {};
    const form = document.getElementById('form27Form');
    const saveBtn = document.getElementById('saveForm27Btn');
    const form27IdInput = document.getElementById('form27_id');
    const toastContainer = document.getElementById('toastContainer');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');

    if (!form || !saveBtn) {
        return;
    }

    function setLoading(show, text) {
        loadingText.textContent = text || 'Saving...';
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }

    function showToast(message, type = 'success') {
        const el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + type + ' border-0';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');
        el.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        toastContainer.appendChild(el);
        const t = new bootstrap.Toast(el, { delay: 3000 });
        t.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
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

    async function saveForm27() {
        setLoading(true, 'Saving Form 27...');
        try {
            const fd = new FormData(form);
            const response = await fetch(app.saveUrl, {
                method: 'POST',
                body: fd
            });
            const data = await parseJsonResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'Save failed.');
            }

            if (data.id) {
                form27IdInput.value = String(data.id);
            }
            showToast(data.message || 'Form 27 saved successfully.', 'success');
        } catch (error) {
            showToast(error.message || 'Unable to save Form 27.', 'danger');
        } finally {
            setLoading(false, 'Saving...');
        }
    }

    saveBtn.addEventListener('click', saveForm27);
})();
