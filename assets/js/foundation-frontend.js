jQuery(document).ready(function($) {
    const config = typeof foundationConfig !== 'undefined' ? foundationConfig : {};
    const branding = config.branding || {};
    const uploadRules = config.uploads || {};
    const currencySymbol = branding.currencySymbol || '£';

    let steps = Array.isArray(config.formData) ? config.formData : [];
    let currentStep = -1;
    let userSelections = {};
    let uploadedFiles = {};
    let selectedRouteIds = new Set();
    let lastActiveElement = null;
    let journeyStarted = false;
    let journeyCompleted = false;
    let resumeToken = '';

    const $overlay = $('#foundation-app-overlay');
    if ($overlay.length && $overlay.parent().prop('tagName') !== 'BODY') {
        $overlay.appendTo('body');
    }

    $overlay.attr({
        'data-theme': 'dark',
        'role': 'dialog',
        'aria-modal': 'true',
        'aria-labelledby': 'foundation-wizard-title'
    });

    steps = steps.map((step, index) => normalizeStep(step, index));
    trackViewOnce();
    const initialResumeToken = getResumeTokenFromUrl();
    if (initialResumeToken) {
        restoreDraftFromToken(initialResumeToken);
    }

    function normalizeStep(step, index) {
        const normalized = $.extend(true, {
            id: `step_${index + 1}`,
            title: `Screen ${index + 1}`,
            subtitle: 'Fill in the details below.',
            is_conditional: false,
            fields: []
        }, step || {});

        normalized.is_conditional = normalizeBool(normalized.is_conditional);
        normalized.fields = Array.isArray(normalized.fields) ? normalized.fields.map((field, fieldIndex) => normalizeField(field, fieldIndex)) : [];
        if (!normalized.id) normalized.id = `step_${index + 1}`;
        return normalized;
    }

    function normalizeField(field, index) {
        const normalized = $.extend(true, {
            id: `field_${index + 1}`,
            type: 'text_input',
            label: '',
            helper: '',
            placeholder: '',
            required: false
        }, field || {});
        normalized.required = normalizeBool(normalized.required);
        if (!normalized.id) normalized.id = `field_${index + 1}`;
        if (normalized.type === 'service_card') {
            normalized.options = Array.isArray(normalized.options) ? normalized.options : [];
            normalized.variant = normalized.variant || 'services';
        }
        return normalized;
    }

    function normalizeBool(value) {
        if (typeof value === 'boolean') return value;
        const raw = String(value || '').toLowerCase();
        return raw === '1' || raw === 'true' || raw === 'yes' || raw === 'on';
    }

    function escapeHtml(str) {
        if (str === null || typeof str === 'undefined') return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }



    function trackEvent(eventName, message = '') {
        if (!config.ajaxUrl || !config.nonce) return Promise.resolve();
        const payload = new URLSearchParams();
        payload.set('action', 'foundation_track_quote_event');
        payload.set('nonce', config.nonce || '');
        payload.set('event', eventName);
        if (message) payload.set('message', message);
        return fetch(config.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
        }).catch(() => null);
    }

    function trackViewOnce() {
        const key = 'foundation_form_view_tracked';
        try {
            if (window.sessionStorage && !window.sessionStorage.getItem(key)) {
                window.sessionStorage.setItem(key, '1');
                trackEvent('view');
            }
        } catch (error) {
            trackEvent('view');
        }
    }

    function getResumeConfig() {
        return config.resume || {};
    }

    function getResumeQueryParam() {
        return getResumeConfig().queryParam || 'foundation_resume';
    }

    function getResumeBaseUrl() {
        return getResumeConfig().baseUrl || window.location.href.split('?')[0];
    }

    function getResumeTokenFromUrl() {
        try {
            const params = new URLSearchParams(window.location.search);
            return params.get(getResumeQueryParam()) || '';
        } catch (error) {
            return '';
        }
    }

    function getContactDraftValues() {
        return {
            name: ($('#lead-name').val() || '').trim(),
            email: ($('#lead-email').val() || '').trim(),
            phone: ($('#lead-phone').val() || '').trim(),
            company: ($('#company-name').val() || '').trim(),
            website: ($('#lead-website').val() || '').trim()
        };
    }

    function applyDraftContact(contact) {
        if (!contact || typeof contact !== 'object') return;
        $('#lead-name').val(contact.name || '');
        $('#lead-email').val(contact.email || '');
        $('#lead-phone').val(contact.phone || '');
        $('#company-name').val(contact.company || '');
        $('#lead-website').val(contact.website || '');
    }

    function saveDraft(sendEmail = false) {
        if (!config.ajaxUrl || !config.nonce) return Promise.reject(new Error('Saving is not available right now.'));
        const contact = getContactDraftValues();
        const payload = new FormData();
        payload.append('action', 'foundation_save_quote_draft');
        payload.append('nonce', config.nonce || '');
        payload.append('token', resumeToken || '');
        payload.append('current_step', String(currentStep));
        payload.append('resume_base', getResumeBaseUrl());
        payload.append('send_email', sendEmail ? '1' : '0');
        Object.entries(contact).forEach(([key, value]) => payload.append(`contact[${key}]`, String(value || '')));
        Object.entries(userSelections).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => payload.append(`selections[${key}][]`, String(item)));
            } else {
                payload.append(`selections[${key}]`, String(value));
            }
        });
        return fetch(config.ajaxUrl, { method: 'POST', body: payload })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok || !data || !data.success) {
                    const message = data && data.data && data.data.message ? data.data.message : 'We could not save your progress right now.';
                    throw new Error(message);
                }
                resumeToken = data.data && data.data.token ? String(data.data.token) : resumeToken;
                return data.data || {};
            });
    }

    function restoreDraftFromToken(token) {
        if (!token || !config.ajaxUrl || !config.nonce) return;
        const payload = new URLSearchParams();
        payload.set('action', 'foundation_resume_quote_draft');
        payload.set('nonce', config.nonce || '');
        payload.set('token', token);
        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
        }).then(async (response) => {
            const data = await response.json();
            if (!response.ok || !data || !data.success || !data.data) return;
            resumeToken = token;
            const payload = data.data;
            userSelections = payload.selections && typeof payload.selections === 'object' ? payload.selections : {};
            const draftContact = payload.contact && typeof payload.contact === 'object' ? payload.contact : {};
            selectedRouteIds = new Set();
            steps.forEach((step) => {
                (step.fields || []).forEach((field) => {
                    if (field.type !== 'service_card') return;
                    const selectionKey = `${field.id}_options`;
                    const selected = Array.isArray(userSelections[selectionKey]) ? userSelections[selectionKey].map(String) : [];
                    (field.options || []).forEach((opt, idx) => {
                        if (selected.includes(String(idx)) && opt.route_step_id) selectedRouteIds.add(opt.route_step_id);
                    });
                });
            });
            buildWizardLayout();
            $overlay.css({ display: 'flex', visibility: 'visible', opacity: '1' }).addClass('is-active');
            $('body').css('overflow', 'hidden');
            journeyStarted = true;
            trackEvent('start');
            const draftStep = Number.isInteger(payload.current_step) ? payload.current_step : parseInt(payload.current_step, 10);
            if (!Number.isNaN(draftStep) && draftStep >= 0 && draftStep < steps.length) {
                renderStep(draftStep);
            } else if (!Number.isNaN(draftStep) && draftStep >= steps.length) {
                renderContactForm();
            } else {
                const firstIndex = getNextStepIndex(-1);
                renderStep(firstIndex);
            }
            window.setTimeout(() => applyDraftContact(draftContact), 60);
        }).catch(() => null);
    }

    function getCanvas() {
        return $('#foundation-app-canvas');
    }

    function getContainer() {
        return $('.wizard-container');
    }

    function getTriggerLabel() {
        return branding.launchButtonLabel || 'Get a Quote';
    }

    function getWizardTitle() {
        return branding.wizardTitle || 'Inkfire Project Calculator';
    }

    function openOverlay() {
        lastActiveElement = document.activeElement;
        currentStep = -1;
        userSelections = {};
        uploadedFiles = {};
        selectedRouteIds = new Set();
        journeyStarted = false;
        journeyCompleted = false;
        buildWizardLayout();
        $overlay.css({ display: 'flex', visibility: 'visible', opacity: '0' });
        $('body').css('overflow', 'hidden');
        window.setTimeout(() => {
            $overlay.addClass('is-active').css('opacity', '1');
            $('#foundation-close-btn').trigger('focus');
        }, 20);
        renderStartScreen();
    }

    function closeOverlay() {
        if (journeyStarted && !journeyCompleted) { trackEvent('incomplete'); }
        $overlay.removeClass('is-active').css('opacity', '0');
        window.setTimeout(() => {
            $overlay.css('display', 'none');
            $('body').css('overflow', '');
            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus();
            }
        }, 250);
    }

    function buildWizardLayout() {
        const logo = branding.logoUrl ? `<img src="${escapeHtml(branding.logoUrl)}" alt="Brand logo" style="width:auto;height:60px;object-fit:contain;">` : '';
        const html = `
            <div class="wizard-container">
                <div class="wizard-cover" id="fnd-wizard-cover">
                    <div class="cover-content" id="fnd-cover-content"></div>
                </div>
                <div class="wizard-main">
                    <div class="wizard-header">
                        <div class="wizard-title" id="foundation-wizard-title" style="display:flex;align-items:center;gap:10px;font-size:1rem;font-weight:700;">
                            ${logo}
                            <span>${escapeHtml(getWizardTitle())}</span>
                        </div>
                        <div class="wizard-controls">
                            <button class="theme-toggle" id="fnd-theme-toggle" type="button" aria-pressed="false">Light mode</button>
                            <button class="close-link" id="foundation-save-draft-btn" type="button">Save &amp; resume</button>
                            <button class="close-link" id="foundation-close-btn" type="button">Close</button>
                        </div>
                    </div>
                    <div class="progress-container" id="fnd-progress-bar" style="display:none;"></div>
                    <div class="step-banner" id="fnd-step-banner" style="display:none;" aria-live="polite"></div>
                    <p id="foundation-step-error" role="alert" style="display:none;color:#c62828;text-align:center;margin:10px 0 0;font-weight:700;"></p>
                    <div class="wizard-card">
                        <div id="foundation-app-canvas"></div>
                    </div>
                    <div class="wizard-footer" style="display:none;">
                        <button id="btn-prev" type="button">← Back</button>
                        <button id="btn-next" type="button">Next Step →</button>
                    </div>
                </div>
            </div>`;
        $overlay.html(html);
        updateCoverContent('intro');
    }

    function updateCoverContent(type) {
        const $cover = $('#fnd-wizard-cover');
        const $content = $('#fnd-cover-content');
        const introImage = branding.introImageUrl || '';
        const testimonialImage = branding.testimonialImageUrl || '';

        if (type === 'testimonial') {
            if (testimonialImage) {
                $cover.css('background-image', `url('${testimonialImage.replace(/'/g, "\\'")}')`);
                $cover.css('background-position', 'top center');
            }
            $content.html(`
                <h1 class="text-gradient">${escapeHtml(branding.testimonialHeading || 'Why Our Clients Love Inkfire')}</h1>
                <p style="font-style:italic;font-size:17px;margin-bottom:20px;">${escapeHtml(branding.testimonialQuote || '')}</p>
                <p style="font-weight:bold;margin-top:0;">${escapeHtml(branding.testimonialAttribution || '')}</p>
            `);
            return;
        }

        if (introImage) {
            $cover.css('background-image', `url('${introImage.replace(/'/g, "\\'")}')`);
            $cover.css('background-position', 'center');
        }
        $content.html(`
            <h1 class="text-gradient">${escapeHtml(branding.introHeading || 'Why choose us?')}</h1>
            <p>${escapeHtml(branding.introText || '')}</p>
        `);
    }

    function shouldShowStep(step) {
        if (!step) return false;
        if (step.is_conditional !== true) return true;
        return selectedRouteIds.has(step.id);
    }

    function getVisibleStepIndexes() {
        const indexes = [];
        steps.forEach((step, index) => {
            if (shouldShowStep(step)) indexes.push(index);
        });
        return indexes;
    }

    function getNextStepIndex(fromIndex) {
        for (let i = fromIndex + 1; i < steps.length; i++) {
            if (shouldShowStep(steps[i])) return i;
        }
        return steps.length;
    }

    function getPrevStepIndex(fromIndex) {
        for (let i = fromIndex - 1; i >= 0; i--) {
            if (shouldShowStep(steps[i])) return i;
        }
        return -1;
    }

    function isFieldRequired(field) {
        return normalizeBool(field && field.required);
    }

    function getFieldValue(fieldId) {
        if (!fieldId || typeof userSelections[fieldId] === 'undefined' || userSelections[fieldId] === null) return '';
        return String(userSelections[fieldId]).trim();
    }

    function clearStepError() {
        $('#foundation-step-error').hide().text('');
    }

    function setStepError(message) {
        $('#foundation-step-error').text(message).show();
    }

    function getCoreFieldIds() {
        const ids = { budget: '', timeline: '', services_main: '' };
        steps.forEach((step) => {
            step.fields.forEach((field) => {
                if (field.type !== 'service_card') return;
                const role = field.role || '';
                const variant = field.variant || '';
                if (!ids.budget && (role === 'budget' || variant === 'budget')) ids.budget = field.id;
                if (!ids.timeline && (role === 'timeline' || variant === 'timeline')) ids.timeline = field.id;
                if (!ids.services_main && (role === 'services_main' || variant === 'services')) ids.services_main = field.id;
            });
        });
        return ids;
    }

    function validateCoreSelections() {
        const ids = getCoreFieldIds();
        const checks = [
            { key: 'budget', label: 'budget' },
            { key: 'timeline', label: 'timeline' },
            { key: 'services_main', label: 'service selection' }
        ];
        const missing = checks.filter((check) => {
            if (!ids[check.key]) return false;
            const selected = userSelections[`${ids[check.key]}_options`] || [];
            return !Array.isArray(selected) || selected.length === 0;
        }).map((check) => check.label);

        if (missing.length) {
            setStepError(`Please complete your ${missing.join(', ')} before requesting a quote.`);
            return false;
        }
        return true;
    }

    function focusTrap(event) {
        if (!$overlay.hasClass('is-active') || event.key !== 'Tab') return;
        const focusable = $overlay.find('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])').filter(':visible');
        if (!focusable.length) return;
        const first = focusable.get(0);
        const last = focusable.get(focusable.length - 1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function validateCurrentStep() {
        if (currentStep < 0 || currentStep >= steps.length) return true;
        const step = steps[currentStep];
        if (!step || !Array.isArray(step.fields)) return true;

        clearStepError();

        for (const field of step.fields) {
            if (!field || !field.id || !isFieldRequired(field)) continue;
            const type = field.type;
            let valid = true;
            let focusTarget = null;

            if (type === 'service_card' || type === 'toggle') {
                const selected = userSelections[`${field.id}_options`] || [];
                valid = Array.isArray(selected) && selected.length > 0;
                focusTarget = $(`[data-field-id="${field.id}"]`).find('.option-card').first();
            } else if (type === 'range_slider') {
                valid = !!userSelections[`${field.id}_val`];
                focusTarget = $(`#${field.id}_range`);
            } else if (type === 'text_input' || type === 'rich_text') {
                valid = getFieldValue(field.id) !== '';
                focusTarget = $(`#${field.id}`);
                focusTarget.attr('aria-invalid', valid ? 'false' : 'true');
            } else if (type === 'file_upload') {
                const selectedFiles = uploadedFiles[field.id] || [];
                valid = Array.isArray(selectedFiles) && selectedFiles.length > 0;
                focusTarget = $(`#${field.id}`);
                focusTarget.attr('aria-invalid', valid ? 'false' : 'true');
            }

            if (!valid) {
                setStepError(`Please complete “${field.label || 'this field'}” before continuing.`);
                if (focusTarget && focusTarget.length) focusTarget.trigger('focus');
                return false;
            }
        }
        return true;
    }

    function renderStartScreen() {
        currentStep = -1;
        getContainer().removeClass('full-width-mode');
        updateCoverContent('intro');
        $('#fnd-progress-bar, #fnd-step-banner, .wizard-footer').hide();
        clearStepError();

        getCanvas().html(`
            <div class="start-screen-content">
                <div class="sections-list"><span>Brief</span> • <span>Plan</span> • <span>Quote</span></div>
                <h2>Your next project starts here.</h2>
                <p style="color:var(--fnd-text-muted);">Build your project step by step. Our interactive calculator helps you choose what you need and see transparent pricing as you go.</p>
                <button id="foundation-start-btn" type="button" class="foundation-primary-pill">Let's Begin</button>
            </div>
        `).hide().fadeIn(250, () => $('#foundation-start-btn').trigger('focus'));
    }

    function renderProgressBar(index) {
        const visible = getVisibleStepIndexes();
        const currentPos = visible.indexOf(index);
        const totalSegments = visible.length + 1;
        let html = '';
        for (let i = 0; i < totalSegments; i++) {
            html += `<div class="progress-segment ${currentPos >= 0 && i <= currentPos ? 'active' : ''}"></div>`;
        }
        $('#fnd-progress-bar').html(html);
    }

    function renderBanner(step, index) {
        $('#fnd-step-banner').html(`
            <h2>${escapeHtml(step.title || `Step ${index + 1}`)}</h2>
            <p style="margin:0 auto;">${escapeHtml(step.subtitle || 'Fill in the details below.')}</p>
        `).show();
    }

    function focusFirstInteractive() {
        const $first = getCanvas().find('button, input, textarea, select').filter(':visible').first();
        if ($first.length) $first.trigger('focus');
    }

    function renderStep(index) {
        currentStep = index;
        clearStepError();

        if (index >= steps.length) {
            renderContactForm();
            return;
        }

        if (!steps[index]) {
            getCanvas().html('<div style="text-align:center;padding:40px;"><h2>No steps configured</h2><p>Please configure the wizard in WordPress admin.</p></div>');
            return;
        }

        getContainer().addClass('full-width-mode');
        $('#fnd-progress-bar, #fnd-step-banner, .wizard-footer').show();
        renderProgressBar(index);
        renderBanner(steps[index], index);

        let html = '<div class="step-container">';
        if (steps[index].fields && steps[index].fields.length) {
            steps[index].fields.forEach((field) => {
                html += renderField(field);
            });
        } else {
            html += '<p style="text-align:center;color:var(--fnd-text-muted);">(This step has no fields)</p>';
        }
        html += '</div>';

        getCanvas().html(html).hide().fadeIn(250, focusFirstInteractive);
        $('.wizard-footer').show();
        $('#btn-next').text('Next Step →');
    }

    function getFieldHelperMarkup(field, helperId) {
        if (!field.helper) return '';
        return `<div id="${helperId}" class="fnd-helper-text">${escapeHtml(field.helper)}</div>`;
    }

    function renderField(field) {
        if (!field || !field.type) return '';
        const requiredMark = isFieldRequired(field) ? ' <span aria-hidden="true" style="color:#c62828;">*</span>' : '';
        const helperId = `${field.id}_helper`;
        let html = `<div class="field-wrapper" data-field-wrapper="${escapeHtml(field.id)}">`;
        if (field.label && !['section_title', 'description', 'divider'].includes(field.type)) {
            html += `<h4 id="${escapeHtml(field.id)}_label">${escapeHtml(field.label)}${requiredMark}</h4>`;
        }

        if (field.type === 'service_card') {
            const selectionKey = `${field.id}_options`;
            const selected = Array.isArray(userSelections[selectionKey]) ? userSelections[selectionKey] : [];
            const isMulti = !field.variant || field.variant === 'services';
            html += `<fieldset class="fnd-fieldset"><legend class="screen-reader-text">${escapeHtml(field.label || 'Options')}</legend>${getFieldHelperMarkup(field, helperId)}<div class="options-grid" data-field-id="${escapeHtml(field.id)}" data-multi="${isMulti ? 'true' : 'false'}">`;
            (field.options || []).forEach((opt, idx) => {
                const isSelected = selected.includes(idx) || selected.includes(String(idx));
                const price = parseFloat(opt.price || 0);
                html += `
                    <button type="button" class="option-card ${isSelected ? 'selected' : ''}" data-field-id="${escapeHtml(field.id)}" data-option-index="${idx}" data-price="${price}" data-route-step-id="${escapeHtml(opt.route_step_id || '')}" aria-pressed="${isSelected ? 'true' : 'false'}" ${field.helper ? `aria-describedby="${helperId}"` : ''}>
                        <h4>${escapeHtml(opt.label || 'Option')}</h4>
                        ${price > 0 ? `<div class="price">+${escapeHtml(currencySymbol)}${price}</div>` : ''}
                    </button>`;
            });
            html += '</div></fieldset>';
        } else if (field.type === 'toggle') {
            const selectionKey = `${field.id}_options`;
            const selected = Array.isArray(userSelections[selectionKey]) ? userSelections[selectionKey] : [];
            const yesSelected = selected.includes(0) || selected.includes('0');
            const noSelected = selected.includes(1) || selected.includes('1');
            html += `<fieldset class="fnd-fieldset"><legend class="screen-reader-text">${escapeHtml(field.label || 'Yes or no')}</legend><div class="options-grid options-grid-toggle" data-field-id="${escapeHtml(field.id)}" data-multi="false">`;
            html += `<button type="button" class="option-card ${yesSelected ? 'selected' : ''}" data-field-id="${escapeHtml(field.id)}" data-option-index="0" data-price="${parseFloat(field.price || 0)}" aria-pressed="${yesSelected ? 'true' : 'false'}"><h4>${escapeHtml(field.yes_label || 'Yes')}</h4></button>`;
            html += `<button type="button" class="option-card ${noSelected ? 'selected' : ''}" data-field-id="${escapeHtml(field.id)}" data-option-index="1" data-price="0" aria-pressed="${noSelected ? 'true' : 'false'}"><h4>${escapeHtml(field.no_label || 'No')}</h4></button>`;
            html += '</div></fieldset>';
        } else if (field.type === 'text_input') {
            html += `${getFieldHelperMarkup(field, helperId)}<input id="${escapeHtml(field.id)}" type="text" class="fnd-input" data-field-id="${escapeHtml(field.id)}" data-field-type="text_input" value="${escapeHtml(getFieldValue(field.id))}" placeholder="${escapeHtml(field.placeholder || 'Type here...')}" ${field.helper ? `aria-describedby="${helperId}"` : ''} ${isFieldRequired(field) ? 'aria-required="true" required' : ''}>`;
        } else if (field.type === 'rich_text') {
            html += `${getFieldHelperMarkup(field, helperId)}<textarea id="${escapeHtml(field.id)}" class="fnd-input" data-field-id="${escapeHtml(field.id)}" data-field-type="rich_text" rows="6" placeholder="${escapeHtml(field.placeholder || 'Type details here...')}" ${field.helper ? `aria-describedby="${helperId}"` : ''} ${isFieldRequired(field) ? 'aria-required="true" required' : ''}>${escapeHtml(getFieldValue(field.id))}</textarea>`;
        } else if (field.type === 'file_upload') {
            const selectedFiles = escapeHtml(getFieldValue(field.id) || 'No files selected yet.');
            const accept = getAcceptAttribute(field);
            const describedBy = [field.helper ? helperId : '', `${field.id}_summary`].filter(Boolean).join(' ');
            html += `${getFieldHelperMarkup(field, helperId)}<input id="${escapeHtml(field.id)}" type="file" class="fnd-input fnd-file-input" data-field-id="${escapeHtml(field.id)}" data-field-type="file_upload" ${accept ? `accept="${escapeHtml(accept)}"` : ''} multiple aria-describedby="${escapeHtml(describedBy)}" ${isFieldRequired(field) ? 'aria-required="true" required' : ''}>`;
            html += `<div id="${escapeHtml(field.id)}_summary" data-file-summary-for="${escapeHtml(field.id)}" class="fnd-helper-text" aria-live="polite">${selectedFiles}</div>`;
        } else if (field.type === 'range_slider') {
            const currentVal = userSelections[`${field.id}_val`] || field.min || 1;
            html += `<div class="range-wrapper"><div class="range-value" id="${escapeHtml(field.id)}_output">${escapeHtml(currentVal)}</div><input id="${escapeHtml(field.id)}_range" type="range" class="foundation-range" data-field-id="${escapeHtml(field.id)}" min="${escapeHtml(field.min || 1)}" max="${escapeHtml(field.max || 50)}" step="${escapeHtml(field.step || 1)}" value="${escapeHtml(currentVal)}" aria-labelledby="${escapeHtml(field.id)}_label" aria-describedby="${escapeHtml(field.id)}_output"></div>`;
        } else if (field.type === 'section_title') {
            html += `<div class="section-title-block"><h3 style="margin-bottom:5px;">${escapeHtml(field.label || '')}</h3>${field.helper ? `<p style="margin-top:0;color:var(--fnd-text-muted);">${escapeHtml(field.helper)}</p>` : ''}</div>`;
        } else if (field.type === 'description') {
            html += `<p style="color:var(--fnd-text);line-height:1.6;">${escapeHtml(field.text || '')}</p>`;
        } else if (field.type === 'divider') {
            html += `<hr style="border:0;border-top:1px solid var(--fnd-border);margin:20px 0;">`;
        }

        html += '</div>';
        return html;
    }

    function getAcceptAttribute(field) {
        if (field && field.accept) return field.accept;
        const allowed = Array.isArray(uploadRules.allowedTypes) ? uploadRules.allowedTypes : [];
        return allowed.map((type) => `.${type}`).join(',');
    }

    function renderContactForm() {
        currentStep = steps.length;
        getContainer().removeClass('full-width-mode');
        updateCoverContent('testimonial');
        $('#fnd-progress-bar').hide();
        $('#fnd-step-banner').html(`
            <h2>We’ll Take It From Here</h2>
            <p style="margin:0 auto;">Share your details and we’ll send your personalised project estimate.</p>
        `).show();

        getCanvas().html(`
            <div class="foundation-lead-form">
                <div class="foundation-contact-grid foundation-contact-grid-single">
                    <div>
                        <label for="lead-name">Full Name <span style="color:#c62828">*</span></label>
                        <input type="text" id="lead-name" placeholder="John Doe" aria-required="true" required>
                    </div>
                    <div>
                        <label for="company-name">Company Name <span style="color:#c62828">*</span></label>
                        <input type="text" id="company-name" placeholder="Company name" aria-required="true" required>
                    </div>
                </div>
                <div class="foundation-contact-grid foundation-contact-grid-double">
                    <div>
                        <label for="lead-phone">Phone Number <span style="color:#c62828">*</span></label>
                        <input type="text" id="lead-phone" placeholder="+44 7700 900000" aria-required="true" required>
                    </div>
                    <div>
                        <label for="lead-email">Email Address <span style="color:#c62828">*</span></label>
                        <input type="email" id="lead-email" placeholder="john@company.com" aria-required="true" required>
                    </div>
                </div>
                <div class="foundation-contact-grid foundation-contact-grid-single">
                    <div>
                        <label for="lead-website">Website (Optional)</label>
                        <input type="url" id="lead-website" placeholder="https://">
                    </div>
                </div>
                <div style="display:none;">
                    <label for="foundation-honey">Do not fill this out if you are human</label>
                    <input type="text" id="foundation-honey" name="foundation_honey" tabindex="-1" autocomplete="off">
                </div>
                <div class="foundation-form-actions">
                    <button id="foundation-back-to-steps" type="button" class="foundation-secondary-pill">Back</button>
                    <button id="foundation-save-and-email" type="button" class="foundation-secondary-pill">Save &amp; email link</button>
                    <button id="foundation-submit-lead" type="button" class="foundation-primary-pill foundation-submit-pill">${escapeHtml(getTriggerLabel())}</button>
                </div>
                <p id="submission-error" role="alert" style="color:#c62828;display:none;text-align:center;margin-top:12px;font-weight:600;"></p>
            </div>
        `).hide().fadeIn(250, () => $('#lead-name').trigger('focus'));

        $('.wizard-footer').hide();
    }

    function validateContactForm() {
        const fields = [
            { id: '#lead-name', label: 'full name' },
            { id: '#company-name', label: 'company name' },
            { id: '#lead-phone', label: 'phone number' },
            { id: '#lead-email', label: 'email address' }
        ];

        for (const field of fields) {
            const $input = $(field.id);
            const value = ($input.val() || '').trim();
            const valid = value !== '';
            $input.attr('aria-invalid', valid ? 'false' : 'true');
            if (!valid) {
                $('#submission-error').text(`Please fill in your ${field.label}.`).show();
                $input.trigger('focus');
                return false;
            }
        }

        const email = ($('#lead-email').val() || '').trim();
        if (!validateEmail(email)) {
            $('#submission-error').text('Please enter a valid email address.').show();
            $('#lead-email').attr('aria-invalid', 'true').trigger('focus');
            return false;
        }

        return true;
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function handleFileSelection(input) {
        const fieldId = $(input).data('field-id');
        const field = findFieldById(fieldId);
        const files = Array.from(input.files || []);
        const $summary = getCanvas().find(`[data-file-summary-for="${fieldId}"]`).first();
        const $error = $('#foundation-step-error');

        const maxFiles = Number(field && field.max_files ? field.max_files : (uploadRules.maxFilesPerField || 5));
        const maxFileMb = Number(field && field.max_file_size_mb ? field.max_file_size_mb : (uploadRules.maxFileSizeMb || 10));
        const allowedExtensions = Array.isArray(uploadRules.allowedTypes) ? uploadRules.allowedTypes.map((ext) => String(ext).toLowerCase()) : [];

        if (files.length > maxFiles) {
            input.value = '';
            uploadedFiles[fieldId] = [];
            userSelections[fieldId] = '';
            $summary.text(`Please upload up to ${maxFiles} files.`);
            $error.text(`Please upload up to ${maxFiles} files for “${field && field.label ? field.label : 'this upload field'}”.`).show();
            return;
        }

        for (const file of files) {
            const extension = (file.name.split('.').pop() || '').toLowerCase();
            if (allowedExtensions.length && !allowedExtensions.includes(extension)) {
                input.value = '';
                uploadedFiles[fieldId] = [];
                userSelections[fieldId] = '';
                $summary.text(`${file.name} is not an allowed file type.`);
                $error.text(`${file.name} is not an allowed file type.`).show();
                return;
            }
            if (file.size > maxFileMb * 1024 * 1024) {
                input.value = '';
                uploadedFiles[fieldId] = [];
                userSelections[fieldId] = '';
                $summary.text(`${file.name} is larger than ${maxFileMb}MB.`);
                $error.text(`${file.name} is larger than ${maxFileMb}MB.`).show();
                return;
            }
        }

        uploadedFiles[fieldId] = files;
        userSelections[fieldId] = files.map((file) => file.name).join(', ');
        $(input).attr('aria-invalid', files.length ? 'false' : 'true');
        $summary.text(files.length ? files.map((file) => file.name).join(', ') : 'No files selected yet.');
        clearStepError();
    }

    function findFieldById(fieldId) {
        for (const step of steps) {
            const match = (step.fields || []).find((field) => field.id === fieldId);
            if (match) return match;
        }
        return null;
    }

    function renderSuccessScreen(serverTotal = null, customerEmailStatus = 'disabled') {
        const totalPrice = serverTotal === null ? 0 : serverTotal;
        const safeName = escapeHtml(($('#lead-name').val() || '').trim());
        let safeMessage = '';
        if (customerEmailStatus === 'sent') {
            safeMessage = escapeHtml(branding.successMessage || 'A detailed copy of your proposal has been sent to your email.');
        } else if (customerEmailStatus === 'failed') {
            safeMessage = 'Your request has been sent to our team. A customer email could not be sent this time, but we still have your brief.';
        } else {
            safeMessage = 'Your request has been sent to our team. We will follow up with you shortly.';
        }

        $('#fnd-step-banner').hide();
        $('.wizard-footer').hide();
        getCanvas().html(`
            <div style="text-align:center;padding:60px 0;">
                <h2 id="foundation-success-heading" style="font-size:32px;margin-bottom:10px;color:var(--fnd-text);" tabindex="-1">Request Received</h2>
                <div style="font-size:16px;text-transform:uppercase;letter-spacing:1px;color:var(--fnd-text-muted);margin-bottom:10px;margin-top:40px;">Estimated Investment</div>
                <h1 style="font-size:80px;color:var(--fnd-accent);margin:0;font-weight:800;">${escapeHtml(currencySymbol)}${Number(totalPrice).toLocaleString()}</h1>
                <p style="color:var(--fnd-text-muted);margin-top:20px;font-size:18px;line-height:1.5;">Thank you, ${safeName}.<br>${safeMessage}</p>
                <button id="foundation-close-btn" type="button" class="foundation-secondary-pill" style="margin-top:40px;">Close Window</button>
            </div>
        `).hide().fadeIn(300, () => $('#foundation-success-heading').trigger('focus'));
    }

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        const triggerClass = e.target.closest('.foundation-trigger');
        const triggerId = e.target.closest('#foundation-launch-btn');
        let isLinkMatch = false;
        if (link) {
            const href = link.getAttribute('href') || '';
            if (href.includes('foundation-form') || href.includes('get-quote')) isLinkMatch = true;
        }
        if (isLinkMatch || triggerClass || triggerId) {
            e.preventDefault();
            e.stopPropagation();
            openOverlay();
        }
    }, true);



    $(document).on('click', '#foundation-save-draft-btn, #foundation-save-and-email', function(e) {
        e.preventDefault();
        const sendEmail = this.id === 'foundation-save-and-email';
        const $button = $(this);
        const original = $button.text();
        $button.prop('disabled', true).text(sendEmail ? 'Sending link…' : 'Saving…');
        saveDraft(sendEmail)
            .then((data) => {
                const resumeUrl = data && data.resume_url ? String(data.resume_url) : '';
                if (!sendEmail && resumeUrl && navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(resumeUrl).catch(() => null);
                }
                const message = sendEmail
                    ? 'We emailed your magic link. Uploaded files will need to be added again when you return.'
                    : (resumeUrl ? `Progress saved. Resume link: ${resumeUrl}` : 'Progress saved.');
                if ($('#submission-error').length) {
                    $('#submission-error').css('color', 'var(--fnd-accent)').text(message).show();
                } else {
                    setStepError(message);
                }
            })
            .catch((error) => {
                const message = error && error.message ? error.message : 'We could not save your progress right now.';
                if ($('#submission-error').length) {
                    $('#submission-error').css('color', '#c62828').text(message).show();
                } else {
                    setStepError(message);
                }
                trackEvent('failure', message);
            })
            .finally(() => {
                $button.prop('disabled', false).text(original);
            });
    });

    $(document).on('click', '#foundation-close-btn', function(e) {
        e.preventDefault();
        closeOverlay();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $overlay.hasClass('is-active')) {
            e.preventDefault();
            closeOverlay();
            return;
        }
        focusTrap(e);
    });

    $(document).on('click', '#fnd-theme-toggle', function() {
        const current = $overlay.attr('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        $overlay.attr('data-theme', next);
        $(this).text(next === 'dark' ? 'Light mode' : 'Dark mode').attr('aria-pressed', next === 'light' ? 'true' : 'false');
    });

    $(document).on('click', '#foundation-start-btn', function() {
        if (!journeyStarted) {
            journeyStarted = true;
            trackEvent('start');
        }
        const firstIndex = getNextStepIndex(-1);
        renderStep(firstIndex);
    });

    $(document).on('click', '#btn-next', function() {
        if (!validateCurrentStep()) return;
        const nextIndex = getNextStepIndex(currentStep);
        if (nextIndex >= steps.length && !validateCoreSelections()) return;
        renderStep(nextIndex);
    });

    $(document).on('click', '#btn-prev', function() {
        const prevIndex = getPrevStepIndex(currentStep);
        if (prevIndex === -1) {
            renderStartScreen();
        } else {
            renderStep(prevIndex);
        }
    });

    $(document).on('click', '.option-card', function() {
        const $card = $(this);
        const $grid = $card.closest('.options-grid');
        const fieldId = $grid.data('fieldId') || $card.data('fieldId');
        const multi = String($grid.data('multi')) === 'true';
        const optionIndex = String($card.data('optionIndex'));
        const routeStepId = $card.data('routeStepId') || '';
        const selectionKey = `${fieldId}_options`;
        let selected = Array.isArray(userSelections[selectionKey]) ? userSelections[selectionKey].map(String) : [];

        if (multi) {
            if ($card.hasClass('selected')) {
                selected = selected.filter((idx) => idx !== optionIndex);
                $card.removeClass('selected').attr('aria-pressed', 'false');
                if (routeStepId) selectedRouteIds.delete(routeStepId);
            } else {
                selected.push(optionIndex);
                $card.addClass('selected').attr('aria-pressed', 'true');
                if (routeStepId) selectedRouteIds.add(routeStepId);
            }
        } else {
            $grid.find('.option-card').removeClass('selected').attr('aria-pressed', 'false').each(function() {
                const rid = $(this).data('routeStepId');
                if (rid) selectedRouteIds.delete(rid);
            });
            selected = [optionIndex];
            $card.addClass('selected').attr('aria-pressed', 'true');
            if (routeStepId) selectedRouteIds.add(routeStepId);
        }

        userSelections[selectionKey] = selected;
        let total = 0;
        selected.forEach((idx) => {
            const $opt = $grid.find(`.option-card[data-option-index="${idx}"]`);
            total += parseFloat($opt.data('price') || 0);
        });
        userSelections[fieldId] = total;
        clearStepError();
    });

    $(document).on('input', '.foundation-range', function() {
        const value = $(this).val();
        const fieldId = $(this).data('field-id');
        userSelections[`${fieldId}_val`] = value;
        $(this).closest('.range-wrapper').find('.range-value').text(value);
        clearStepError();
    });

    $(document).on('input change', '.fnd-input[data-field-id]', function() {
        const fieldId = $(this).data('field-id');
        const fieldType = $(this).data('field-type');
        if (!fieldId) return;
        if (fieldType === 'file_upload') {
            handleFileSelection(this);
            return;
        }
        userSelections[fieldId] = $(this).val();
        $(this).attr('aria-invalid', getFieldValue(fieldId) === '' ? 'true' : 'false');
        clearStepError();
    });

    $(document).on('click', '#foundation-back-to-steps', function() {
        const prevVisible = getPrevStepIndex(steps.length);
        if (prevVisible === -1) renderStartScreen();
        else renderStep(prevVisible);
    });

    $(document).on('click', '#foundation-submit-lead', function(e) {
        e.preventDefault();
        $('#submission-error').hide().text('');
        if ($('#foundation-honey').val()) return;
        if (!validateContactForm()) return;
        if (!validateCoreSelections()) {
            $('#submission-error').text($('#foundation-step-error').text()).show();
            return;
        }

        const $btn = $(this);
        $btn.text('Sending Request...').prop('disabled', true);

        const payload = new FormData();
        payload.append('action', 'foundation_submit_quote');
        payload.append('nonce', config.nonce || '');
        payload.append('contact[name]', ($('#lead-name').val() || '').trim());
        payload.append('contact[email]', ($('#lead-email').val() || '').trim());
        payload.append('contact[phone]', ($('#lead-phone').val() || '').trim());
        payload.append('contact[company]', ($('#company-name').val() || '').trim());
        payload.append('contact[website]', ($('#lead-website').val() || '').trim());
        payload.append('foundation_honey', ($('#foundation-honey').val() || '').trim());

        Object.entries(userSelections).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => payload.append(`selections[${key}][]`, String(item)));
            } else {
                payload.append(`selections[${key}]`, String(value));
            }
        });

        Object.entries(uploadedFiles).forEach(([fieldId, files]) => {
            if (!Array.isArray(files)) return;
            files.forEach((file) => payload.append(`uploads[${fieldId}][]`, file, file.name));
        });

        fetch(config.ajaxUrl, { method: 'POST', body: payload })
            .then(async (response) => {
                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    throw new Error('The server returned an unexpected response.');
                }
                if (!response.ok || !data || !data.success) {
                    const message = data && data.data && data.data.message ? data.data.message : 'We could not send your request right now. Please try again in a moment.';
                    throw new Error(message);
                }
                journeyCompleted = true;
                renderSuccessScreen(
                    data.data && typeof data.data.total !== 'undefined' ? data.data.total : null,
                    data.data && data.data.customer_email_status ? String(data.data.customer_email_status) : 'disabled'
                );
            })
            .catch((error) => {
                $('#submission-error').text(error.message).show();
                $btn.text(getTriggerLabel()).prop('disabled', false);
                trackEvent('failure', error.message || 'Submission failed.');
            });
    });
});
