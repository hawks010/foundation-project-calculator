jQuery(document).ready(function($) {

    // --------------------------------------------------
    // 1. SETUP & STATE
    // --------------------------------------------------

    const config = (typeof foundationConfig !== 'undefined') ? foundationConfig : {};
    let steps = [];

    // Safety check: Ensure we have data to work with
    if (config.formData) {
        // Handle legacy single-step data vs new multi-step array
        if (Array.isArray(config.formData) && config.formData.length > 0 && config.formData[0].hasOwnProperty('fields')) {
            steps = config.formData;
        } else if (Array.isArray(config.formData)) {
            // Legacy: Convert flat fields array into a single step
            steps = [{ id: 'step_legacy', title: 'Project Scope', fields: config.formData, is_conditional: false }];
        }
    }

    // --- CRITICAL FIX: Sanitize Data Types ---
    steps.forEach((step, index) => {
        // 1. Ensure ID exists
        if (!step.id) {
            step.id = 'step_' + index + '_' + Date.now();
        }

        // 2. Strict Boolean Conversion for is_conditional
        const rawCond = String(step.is_conditional).toLowerCase();
        step.is_conditional = (rawCond === 'true' || rawCond === '1');

        // 3. Ensure fields is an array
        if (!Array.isArray(step.fields)) {
            step.fields = [];
        }
    });

    // Current index into the *full* steps array (not filtered)
    let currentStep = -1; // Start at -1 for intro screen

    // Stores numeric totals + extra keys like fieldId + '_options' and fieldId + '_val'
    let userSelections = {};
    let uploadedFiles = {};

    // Set of step IDs that should be shown for conditional screens
    let selectedRouteIds = new Set();

    // Last trigger element (for focus return)
    let lastActiveElement = null;

    const getCanvas = () => $('#foundation-app-canvas');
    const getContainer = () => $('.wizard-container');

    // --------------------------------------------------
    // 2. MOVE OVERLAY (DOM MANIPULATION)
    // --------------------------------------------------
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

    // Helper: close overlay (used by button + Esc + success)
    function closeOverlay() {
        $overlay.removeClass('is-active').css('opacity', '0');
        setTimeout(() => {
            $overlay.css('display', 'none');
            $('body').css('overflow', '');
            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus();
            }
        }, 300);
    }

    // --------------------------------------------------
    // 3. BUILD LAYOUT
    // --------------------------------------------------
    function buildWizardLayout() {
        const html = `
            <div class="wizard-container">
                <div class="wizard-cover" id="fnd-wizard-cover">
                    <div class="cover-content" id="fnd-cover-content">
                        <!-- Content Injected via JS -->
                    </div>
                </div>

                <div class="wizard-main">
                    <div class="wizard-header">
                        <div class="wizard-title" id="foundation-wizard-title" style="display: flex; align-items: center; gap: 10px; font-size: 1rem; font-weight: 700;">
                            <img src="https://inkfire.co.uk/wp-content/uploads/2025/08/cropped-IMG_1089.png"
                                 alt="Inkfire Icon" style="width: auto; height: 60px; object-fit: contain;">
                            Inkfire Project Calculator
                        </div>

                        <div class="wizard-controls">
                            <button class="theme-toggle" id="fnd-theme-toggle" type="button" aria-pressed="false">Light mode</button>
                            <button class="close-link" id="foundation-close-btn" type="button">Close</button>
                        </div>
                    </div>

                    <div class="progress-container" id="fnd-progress-bar" style="display:none;"></div>

                    <div class="step-banner" id="fnd-step-banner" style="display:none;" aria-live="polite"></div>
                    <p id="foundation-step-error" role="alert" style="display:none; color:#ff4757; text-align:center; margin:10px 0 0; font-weight:700;"></p>

                    <div class="wizard-card">
                        <div id="foundation-app-canvas"></div>
                    </div>

                    <div class="wizard-footer" style="display:none;">
                        <button id="btn-prev" type="button">← Back</button>
                        <button id="btn-next" type="button">Next Step →</button>
                    </div>
                </div>
            </div>
        `;
        $overlay.html(html);
        updateCoverContent('intro'); // Set default content
    }

    // Helper to switch Cover Content & Position
    function updateCoverContent(type) {
        const $cover = $('#fnd-wizard-cover');
        const $content = $('#fnd-cover-content');

        if (type === 'intro') {
            $cover.css('background-image', "url('https://inkfire.co.uk/wp-content/uploads/2025/12/250515_SCOPE-AWARDS_02_0489.jpg')");
            $cover.css('background-position', 'center');
            $content.html(`
                <h1 class="text-gradient">Why choose Inkfire?</h1>
                <p>We’re a disabled-led team of 15+ with lived experience at the heart of everything we do. Our specialists bring decades of combined expertise across IT, web, creative and accessibility — delivering solutions that genuinely work for real people.</p>
            `);
        } else if (type === 'testimonial') {
            $cover.css('background-image', "url('https://inkfire.co.uk/wp-content/uploads/2025/12/Screenshot-2025-12-01-at-22.00.39.png')");
            $cover.css('background-position', 'top center');
            $content.html(`
                <h1 class="text-gradient">Why Our Clients Love Inkfire</h1>
                <p style="font-style:italic; font-size:17px; margin-bottom:20px;">“I'm SO grateful for all that you do. I absolutely love my websites, and working with Imali and the team has been such a gift. A special shout-out to Sonny — he’s always so helpful, efficient, and nothing is ever too much trouble. YAY for Sonny!”</p>
                <p style="font-weight:bold; margin-top:0;">Cat Lawless — catlawless.com</p>
            `);
        }
    }

    // --------------------------------------------------
    // CONDITIONAL STEP HELPERS
    // --------------------------------------------------

    function shouldShowStep(step) {
        if (!step) return false;
        if (step.is_conditional !== true) return true;
        return selectedRouteIds.has(step.id);
    }

    function getVisibleStepIndexes() {
        const indexes = [];
        steps.forEach((step, idx) => {
            if (shouldShowStep(step)) {
                indexes.push(idx);
            }
        });
        return indexes;
    }

    function getNextStepIndex(fromIndex) {
        for (let i = fromIndex + 1; i < steps.length; i++) {
            if (shouldShowStep(steps[i])) {
                return i;
            }
        }
        return steps.length; // go to contact form
    }

    function getPrevStepIndex(fromIndex) {
        for (let i = fromIndex - 1; i >= 0; i--) {
            if (shouldShowStep(steps[i])) {
                return i;
            }
        }
        return -1; // intro
    }

    function isFieldRequired(field) {
        const rawRequired = String(field && field.required ? field.required : '').toLowerCase();
        const label = String(field && field.label ? field.label : '').toLowerCase();
        if (label.includes('(optional)')) {
            return false;
        }
        return rawRequired === 'true' || rawRequired === '1' || rawRequired === 'yes';
    }

    function clearStepError() {
        $('#foundation-step-error').hide().text('');
    }

    function setStepError(message) {
        $('#foundation-step-error').text(message).show();
    }

    function getFieldValue(fieldId) {
        if (!fieldId || typeof userSelections[fieldId] === 'undefined' || userSelections[fieldId] === null) {
            return '';
        }
        return String(userSelections[fieldId]).trim();
    }

    function validateCurrentStep() {
        if (currentStep < 0 || currentStep >= steps.length) {
            return true;
        }

        const step = steps[currentStep];
        if (!step || !Array.isArray(step.fields)) {
            return true;
        }

        clearStepError();

        for (const field of step.fields) {
            if (!field || !field.id) {
                continue;
            }

            const required = isFieldRequired(field);
            if (!required) {
                continue;
            }

            const fieldType = field.type;
            let valid = true;
            let focusSelector = null;

            if (fieldType === 'service_card' || fieldType === 'toggle') {
                const selected = userSelections[field.id + '_options'] || [];
                valid = Array.isArray(selected) && selected.length > 0;
                focusSelector = `.options-grid[data-field-id="${field.id}"] .option-card`;
            } else if (fieldType === 'range_slider') {
                valid = !!userSelections[field.id + '_val'];
                focusSelector = `.foundation-range[data-field-id="${field.id}"]`;
            } else if (fieldType === 'text_input' || fieldType === 'rich_text') {
                valid = getFieldValue(field.id) !== '';
                focusSelector = `.fnd-input[data-field-id="${field.id}"]`;
            } else if (fieldType === 'file_upload') {
                const files = uploadedFiles[field.id] || [];
                valid = Array.isArray(files) && files.length > 0;
                focusSelector = `.fnd-input[data-field-id="${field.id}"]`;
            }

            if (!valid) {
                setStepError(`Please complete "${field.label || 'this step'}" before continuing.`);
                const $target = getCanvas().find(focusSelector).filter(':visible').first();
                if ($target.length) {
                    $target.trigger('focus');
                }
                return false;
            }
        }

        return true;
    }

    function validateCoreSelections() {
        const requirements = [
            { key: 'field_budget_options', message: 'Please choose a budget range before requesting a quote.', stepField: 'field_budget' },
            { key: 'field_timeline_options', message: 'Please choose a starting timeline before requesting a quote.', stepField: 'field_timeline' },
            { key: 'field_services_main_options', message: 'Please choose at least one service before requesting a quote.', stepField: 'field_services_main' }
        ];

        for (const requirement of requirements) {
            const selected = userSelections[requirement.key] || [];
            if (!Array.isArray(selected) || selected.length === 0) {
                const targetIndex = steps.findIndex((step) => Array.isArray(step.fields) && step.fields.some((field) => field.id === requirement.stepField));
                if (targetIndex >= 0) {
                    renderStep(targetIndex);
                    window.setTimeout(function () {
                        setStepError(requirement.message);
                    }, 50);
                } else {
                    setStepError(requirement.message);
                }
                return false;
            }
        }

        return true;
    }

    // --------------------------------------------------
    // 4. OPEN APP TRIGGER
    // --------------------------------------------------
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        const triggerClass = e.target.closest('.foundation-trigger');
        const triggerId = e.target.closest('#foundation-launch-btn');

        let isLinkMatch = false;
        if (link) {
            const href = link.getAttribute('href') || '';
            if (href.includes('foundation-form') || href.includes('get-quote')) {
                isLinkMatch = true;
            }
        }

        if (isLinkMatch || triggerClass || triggerId) {
            e.preventDefault();
            e.stopPropagation();

            lastActiveElement = document.activeElement;

            buildWizardLayout();

            $overlay.css({
                'display': 'flex',
                'visibility': 'visible',
                'opacity': '0',
                'z-index': '2147483647',
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'width': '100%',
                'height': '100%'
            });

            setTimeout(() => {
                $overlay.addClass('is-active').css('opacity', '1');
                // Move focus to close button initially (visible on all screens)
                $('#foundation-close-btn').trigger('focus');
            }, 50);

            $('body').css('overflow', 'hidden');

            renderStartScreen();
        }
    }, true);

    // --------------------------------------------------
    // 5. EVENT DELEGATION
    // --------------------------------------------------
    $(document).on('click', '#foundation-close-btn', function(e) {
        e.preventDefault();
        closeOverlay();
    });

    // Esc key to close when overlay is open
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $overlay.hasClass('is-active')) {
            e.preventDefault();
            closeOverlay();
        }
    });

    $(document).on('click', '#fnd-theme-toggle', function() {
        const current = $overlay.attr('data-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        $overlay.attr('data-theme', newTheme);
        $(this)
            .text(newTheme === 'dark' ? 'Light mode' : 'Dark mode')
            .attr('aria-pressed', newTheme === 'dark' ? 'false' : 'true');
    });

    // NEXT / PREV using conditional routing
    $(document).on('click', '#btn-next', () => {
        if (!validateCurrentStep()) {
            return;
        }
        const nextIndex = getNextStepIndex(currentStep);
        if (nextIndex >= steps.length && !validateCoreSelections()) {
            return;
        }
        renderStep(nextIndex);
    });

    $(document).on('click', '#btn-prev', () => {
        const prevIndex = getPrevStepIndex(currentStep);
        if (prevIndex === -1) {
            renderStartScreen();
        } else {
            renderStep(prevIndex);
        }
    });

    // Option card click: supports multi-select for "services" cards
    $(document).on('click', '.option-card', function() {
        const $card = $(this);
        const $grid = $card.closest('.options-grid');

        const fieldId = $grid.data('fieldId') || $card.data('fieldId');
        const multi = ($grid.data('multi') === true || $grid.data('multi') === 'true');
        const optionIndex = parseInt($card.data('optionIndex'), 10) || 0;
        const price = parseFloat($card.data('price')) || 0;
        const routeStepId = $card.data('routeStepId') || '';

        const selectionKey = fieldId + '_options';
        let selected = userSelections[selectionKey] || [];

        if (multi) {
            if ($card.hasClass('selected')) {
                $card.removeClass('selected').attr('aria-pressed', 'false');
                selected = selected.filter(i => i !== optionIndex);
                if (routeStepId) selectedRouteIds.delete(routeStepId);
            } else {
                $card.addClass('selected').attr('aria-pressed', 'true');
                if (!selected.includes(optionIndex)) {
                    selected.push(optionIndex);
                }
                if (routeStepId) selectedRouteIds.add(routeStepId);
            }
        } else {
            // Single-select: clear others in this grid
            $grid.find('.option-card').each(function() {
                const $c = $(this);
                $c.removeClass('selected').attr('aria-pressed', 'false');
                const rid = $c.data('routeStepId');
                if (rid) selectedRouteIds.delete(rid);
            });

            selected = [optionIndex];
            $card.addClass('selected').attr('aria-pressed', 'true');
            if (routeStepId) selectedRouteIds.add(routeStepId);
        }

        userSelections[selectionKey] = selected;

        // Recalculate numeric price total for this field
        let total = 0;
        selected.forEach(idx => {
            const $optCard = $grid.find(`.option-card[data-option-index="${idx}"]`);
            const optPrice = parseFloat($optCard.data('price')) || 0;
            total += optPrice;
        });
        userSelections[fieldId] = total;
    });

    // Keyboard activation for option cards
    $(document).on('keydown', '.option-card', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
        }
    });

    // Range Slider Logic
    $(document).on('input', '.foundation-range', function() {
        const val = $(this).val();
        $(this).parent().find('.range-value').text(val);
        const fieldId = $(this).data('field-id');
        userSelections[fieldId + '_val'] = val;
        clearStepError();
    });

    $(document).on('input change', '.fnd-input[data-field-id]', function() {
        const $field = $(this);
        const fieldId = $field.data('field-id');
        const fieldType = $field.data('field-type');

        if (!fieldId) {
            return;
        }

        if (fieldType === 'file_upload') {
            const files = Array.from(this.files || []);
            uploadedFiles[fieldId] = files;
            userSelections[fieldId] = files.map((file) => file.name).join(', ');

            const $summary = getCanvas().find(`[data-file-summary-for="${fieldId}"]`).first();
            if ($summary.length) {
                $summary.text(files.length ? files.map((file) => file.name).join(', ') : 'No files selected yet.');
            }
        } else {
            userSelections[fieldId] = $field.val();
        }

        clearStepError();
    });

    // --------------------------------------------------
    // 6. RENDERERS
    // --------------------------------------------------

    function escapeHtml(str) {
        if (!str && str !== 0) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function focusFirstInteractive() {
        const $canvas = getCanvas();
        const $first = $canvas.find('input, textarea, select, .option-card, button').filter(':visible').first();

        if ($first.length) {
            if ($first.hasClass('option-card')) {
                if (!$first.attr('tabindex')) {
                    $first.attr('tabindex', '0');
                }
                $first.focus();
            } else {
                $first.focus();
            }
        }
    }

    // 6.1 Intro Screen
    function renderStartScreen() {
        currentStep = -1;
        getContainer().removeClass('full-width-mode');

        updateCoverContent('intro');

        $('#fnd-progress-bar, #fnd-step-banner, .wizard-footer').hide();

        const $canvas = getCanvas();
        $canvas.html(`
            <div class="start-screen-content">
                <div class="sections-list">
                    <span>Brief</span> • <span>Plan</span> • <span>Quote</span>
                </div>
                <h2>Your next project starts here.</h2>
                <p style="color: var(--fnd-text-muted);">Build your project step by step. Our interactive calculator helps you choose what you need and see transparent pricing as you go.</p>
                <button id="foundation-start-btn" type="button" style="background:var(--fnd-primary); color:var(--fnd-primary-fg); border:none; padding:16px 40px; border-radius:50px; font-weight:800; font-size:18px; cursor:pointer; width: fit-content;">Let's Begin</button>
            </div>
        `).hide().fadeIn(300, () => {
            $('#foundation-start-btn').trigger('focus');
        });

        $('#foundation-start-btn').on('click', function() {
            const firstIndex = getNextStepIndex(-1);
            renderStep(firstIndex);
        });
    }

    // 6.2 Dynamic Step
    function renderStep(index) {
        currentStep = index;
        clearStepError();

        if (index >= steps.length) {
            renderContactForm();
            return;
        }

        getContainer().addClass('full-width-mode');

        $('#fnd-progress-bar, #fnd-step-banner, .wizard-footer').show();

        if (steps.length === 0) {
            getCanvas().html('<div style="text-align:center; padding:40px;"><h2>No steps configured</h2><p>Please configure the wizard in WP Admin.</p></div>');
            return;
        }

        const step = steps[index];
        renderProgressBar(index);
        renderBanner(step, index);

        let html = '';
        if (step.fields && Array.isArray(step.fields) && step.fields.length > 0) {
            html += `<div class="step-container">`;
            step.fields.forEach(field => {
                html += renderField(field);
            });
            html += `</div>`;
        } else {
            html += `<p style="text-align:center; color:var(--fnd-text-muted);">(This step has no fields)</p>`;
        }

        getCanvas().html(html).hide().fadeIn(300, () => {
            focusFirstInteractive();
        });
        updateButtons();
    }

    // 6.3 Progress Bar
    function renderProgressBar(currentIndex) {
        const $bar = $('#fnd-progress-bar');
        const visibleIndexes = getVisibleStepIndexes();
        const currentPos = visibleIndexes.indexOf(currentIndex);
        const totalSegments = visibleIndexes.length + 1; // +1 for contact step

        let html = '';
        for (let i = 0; i < totalSegments; i++) {
            const activeClass = (currentPos >= 0 && i <= currentPos) ? 'active' : '';
            html += `<div class="progress-segment ${activeClass}"></div>`;
        }
        $bar.html(html);
    }

    // 6.4 Title Banner
    function renderBanner(step, index) {
        const title = step.title || `Step ${index + 1}`;
        const desc = step.subtitle || 'Fill in the details below.';
        $('#fnd-step-banner')
            .html(`<h2>${escapeHtml(title)}</h2><p style="margin: 0 auto;">${escapeHtml(desc)}</p>`)
            .show();
    }

    // 6.5 Field Renderer (CRASH PROOF)
    function renderField(field) {
        if (!field || !field.type) return '';

        let html = `<div class="field-wrapper">`;
        if (field.label) html += `<h4>${escapeHtml(field.label)}</h4>`;

        if (field.type === 'service_card') {
            const selectionKey = field.id + '_options';
            const selected = userSelections[selectionKey] || [];
            const isMulti = (!field.variant || field.variant === 'services');

            html += `<div class="options-grid" data-field-id="${field.id}" data-multi="${isMulti ? 'true' : 'false'}">`;
            if (field.options && Array.isArray(field.options)) {
                field.options.forEach((opt, idx) => {
                    const isSelected = selected.includes(idx);
                    const price = opt.price || 0;
                    html += `
                        <div class="option-card ${isSelected ? 'selected' : ''}"
                             data-field-id="${field.id}"
                             data-option-index="${idx}"
                             data-price="${price}"
                             data-route-step-id="${opt.route_step_id || ''}"
                             role="button"
                             tabindex="0"
                             aria-pressed="${isSelected ? 'true' : 'false'}">
                            <h4>${escapeHtml(opt.label || 'Option')}</h4>
                            ${price > 0 ? `<div class="price">+£${price}</div>` : ''}
                        </div>`;
                });
            }
            html += `</div>`;
        }
        else if (field.type === 'text_input') {
            const currentValue = escapeHtml(getFieldValue(field.id));
            html += `<input type="text" data-field-id="${field.id}" data-field-type="text_input" value="${currentValue}" placeholder="${escapeHtml(field.placeholder || 'Type here...')}" class="fnd-input" aria-label="${escapeHtml(field.label || field.placeholder || 'Text input')}" ${isFieldRequired(field) ? 'aria-required="true"' : ''}>`;
        }
        else if (field.type === 'rich_text') {
            const currentValue = escapeHtml(getFieldValue(field.id));
            html += `<textarea class="fnd-input" data-field-id="${field.id}" data-field-type="rich_text" rows="6" placeholder="${escapeHtml(field.placeholder || 'Type details here...')}" aria-label="${escapeHtml(field.label || 'Details')}" ${isFieldRequired(field) ? 'aria-required="true"' : ''}>${currentValue}</textarea>`;
            if (field.helper) html += `<div style="font-size:13px; color:var(--fnd-text-muted); margin-top:6px;">${escapeHtml(field.helper)}</div>`;
        }
        else if (field.type === 'file_upload') {
            const selectedFiles = escapeHtml(getFieldValue(field.id) || 'No files selected yet.');
            html += `<input type="file" data-field-id="${field.id}" data-field-type="file_upload" class="fnd-input" multiple style="padding:10px;" aria-label="${escapeHtml(field.label || 'File upload')}" ${isFieldRequired(field) ? 'aria-required="true"' : ''}>`;
            html += `<div data-file-summary-for="${field.id}" style="font-size:13px; color:var(--fnd-text-muted); margin-top:6px;">${selectedFiles}</div>`;
            if (field.helper) html += `<div style="font-size:13px; color:var(--fnd-text-muted); margin-top:6px;">${escapeHtml(field.helper)}</div>`;
        }
        else if (field.type === 'toggle') {
            const selectionKey = field.id + '_options';
            const selected = userSelections[selectionKey] || [];
            const yesSelected = selected.includes(0);
            const noSelected = selected.includes(1);

            const yesLabel = field.yes_label || 'Yes';
            const noLabel = field.no_label || 'No';
            const yesPrice = field.price || 0;

            html += `
                <div class="options-grid" data-field-id="${field.id}" data-multi="false" style="grid-template-columns:1fr 1fr; max-width:300px;">
                    <div class="option-card ${yesSelected ? 'selected' : ''}"
                         data-field-id="${field.id}"
                         data-option-index="0"
                         data-price="${yesPrice}"
                         role="button"
                         tabindex="0"
                         aria-pressed="${yesSelected ? 'true' : 'false'}">
                        <h4>${escapeHtml(yesLabel)}</h4>
                    </div>
                    <div class="option-card ${noSelected ? 'selected' : ''}"
                         data-field-id="${field.id}"
                         data-option-index="1"
                         data-price="0"
                         role="button"
                         tabindex="0"
                         aria-pressed="${noSelected ? 'true' : 'false'}">
                        <h4>${escapeHtml(noLabel)}</h4>
                    </div>
                </div>`;
        }
        else if (field.type === 'range_slider') {
            const currentVal = userSelections[field.id + '_val'] || field.min || 1;
            html += `
                <div class="range-wrapper">
                    <div class="range-value">${currentVal}</div>
                    <input type="range" class="foundation-range"
                           data-field-id="${field.id}"
                           min="${field.min || 1}" max="${field.max || 50}" value="${currentVal}"
                           style="width:100%">
                </div>`;
        }
        else if (field.type === 'section_title') {
            html += `<div class="section-title-block">`;
            html += `<h3 style="margin-bottom:5px;">${escapeHtml(field.label || '')}</h3>`;
            if (field.helper) html += `<p style="margin-top:0; color:var(--fnd-text-muted);">${escapeHtml(field.helper)}</p>`;
            html += `</div>`;
        }
        else if (field.type === 'description') {
            html += `<p style="color:var(--fnd-text); line-height:1.6;">${escapeHtml(field.text || '')}</p>`;
        }
        else if (field.type === 'divider') {
            html += `<hr style="border:0; border-top:1px solid var(--fnd-border); margin:20px 0;">`;
        }

        html += `</div>`;
        return html;
    }

    function updateButtons() {
        $('#btn-prev').prop('disabled', false);
        $('#btn-next').text('Next Step →');
        $('.wizard-footer').show();
    }

    // --------------------------------------------------
    // 7. RENDER CONTACT FORM
    // --------------------------------------------------
    function renderContactForm() {
        currentStep = steps.length;
        getContainer().removeClass('full-width-mode');

        updateCoverContent('testimonial');

        $('#fnd-progress-bar').hide();

        $('#fnd-step-banner').css('text-align', 'center').html(`
            <h2>We’ll Take It From Here</h2>
            <p style="margin: 0 auto;">Share your details and we’ll send your personalised project estimate.</p>
        `).show();

        const $canvas = getCanvas();

        $canvas.html(`
            <div class="foundation-lead-form" style="max-width:600px; margin: auto; padding-top:20px;">
                <div style="display:flex; flex-direction:column; gap:20px;">

                    <div style="display:grid; grid-template-columns: 1fr; gap:20px;">
                        <div>
                            <label for="lead-name" style="display:block; margin-bottom:8px; font-weight:600; color:var(--fnd-text);">Full Name <span style="color:#ff4757">*</span></label>
                            <input type="text" id="lead-name" placeholder="John Doe" required aria-required="true">
                        </div>
                        <div>
                            <label for="company-name" style="display:block; margin-bottom:8px; font-weight:600; color:var(--fnd-text);">Company Name <span style="color:#ff4757">*</span></label>
                            <input type="text" id="company-name" placeholder="Company Name" required aria-required="true">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div>
                            <label for="lead-phone" style="display:block; margin-bottom:8px; font-weight:600; color:var(--fnd-text);">Phone Number <span style="color:#ff4757">*</span></label>
                            <input type="text" id="lead-phone" placeholder="+44 7700 900000" required aria-required="true">
                        </div>
                        <div>
                            <label for="lead-email" style="display:block; margin-bottom:8px; font-weight:600; color:var(--fnd-text);">Email Address <span style="color:#ff4757">*</span></label>
                            <input type="email" id="lead-email" placeholder="john@company.com" required aria-required="true">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr; gap:20px;">
                        <div>
                            <label for="lead-website" style="display:block; margin-bottom:8px; font-weight:600; color:var(--fnd-text);">Website (Optional)</label>
                            <input type="text" id="lead-website" placeholder="https://">
                        </div>
                    </div>
                    
                    <div style="display:none;">
                        <label for="foundation-honey">Do not fill this out if you are human</label>
                        <input type="text" id="foundation-honey" name="foundation_honey" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="display:flex; gap:15px; align-items:center; margin-top:20px;">
                        <button id="foundation-back-to-steps" type="button" style="background:transparent; border:1px solid var(--fnd-border); color:var(--fnd-text-muted); padding:15px 30px; border-radius:8px; font-weight:700; cursor:pointer;">Back</button>
                        <button id="foundation-submit-lead" type="button" style="flex:1; background:var(--fnd-primary); color:var(--fnd-primary-fg); border:none; padding:15px; border-radius:8px; font-weight:800; font-size:18px; cursor:pointer;">Get Quote</button>
                    </div>
                    
                    <p id="submission-error" role="alert" style="color:#ff4757; display:none; text-align:center; margin-top:10px; font-weight:600;"></p>
                </div>
            </div>
        `).hide().fadeIn(300, () => {
            $('#lead-name').trigger('focus');
        });

        $('.wizard-footer').hide();

        $('#foundation-back-to-steps').on('click', function() {
            const prevVisible = getPrevStepIndex(steps.length);
            if (prevVisible === -1) {
                renderStartScreen();
            } else {
                renderStep(prevVisible);
            }
        });
    }

    // Submit Logic
    $(document).on('click', '#foundation-submit-lead', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const name = $('#lead-name').val().trim();
        const email = $('#lead-email').val().trim();
        const phone = $('#lead-phone').val().trim();
        const company = $('#company-name').val().trim();
        const website = $('#lead-website').val().trim();
        const honeypot = $('#foundation-honey').val();

        $('#submission-error').hide().text('');

        if (honeypot) {
            console.log('Bot detected.');
            return;
        }

        if (!name || !email || !phone || !company) {
            $('#submission-error').text('Please fill in all required fields (Name, Company, Email, Phone).').show();
            $('#lead-name').focus();
            return;
        }

        if (!validateEmail(email)) {
            $('#submission-error').text('Please enter a valid email address.').show();
            $('#lead-email').focus();
            return;
        }

        if (!validateCoreSelections()) {
            return;
        }

        $btn.text('Sending Request...');
        $btn.prop('disabled', true);
        const payload = new FormData();
        payload.append('action', 'foundation_submit_quote');
        payload.append('nonce', config.nonce || '');
        payload.append('contact[name]', name);
        payload.append('contact[email]', email);
        payload.append('contact[phone]', phone);
        payload.append('contact[company]', company);
        payload.append('contact[website]', website);
        payload.append('foundation_honey', honeypot);

        Object.entries(userSelections).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => {
                    payload.append(`selections[${key}][]`, String(item));
                });
                return;
            }
            payload.append(`selections[${key}]`, String(value));
        });

        Object.entries(uploadedFiles).forEach(([fieldId, files]) => {
            if (!Array.isArray(files)) {
                return;
            }
            files.forEach((file) => {
                payload.append(`uploads[${fieldId}][]`, file, file.name);
            });
        });

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: payload
        })
            .then(async (response) => {
                let data = null;
                try {
                    data = await response.json();
                } catch (err) {
                    throw new Error('The server returned an unexpected response.');
                }

                if (!response.ok || !data || !data.success) {
                    const message = data && data.data && data.data.message
                        ? data.data.message
                        : 'We could not send your request right now. Please try again in a moment.';
                    throw new Error(message);
                }

                renderSuccessScreen(data.data && typeof data.data.total !== 'undefined' ? data.data.total : null);
            })
            .catch((error) => {
                $('#submission-error').text(error.message).show();
                $btn.text('Get Quote');
                $btn.prop('disabled', false);
            });
    });

    function renderSuccessScreen(serverTotal = null) {
        let totalPrice = serverTotal;
        if (totalPrice === null) {
            totalPrice = 0;
            for (const [key, value] of Object.entries(userSelections)) {
                if (!key.endsWith('_val') && typeof value === 'number') totalPrice += value;
            }
        }

        $('#fnd-step-banner').hide();
        $('.wizard-footer').hide();

        getCanvas().html(`
            <div style="text-align:center; padding:60px 0;">
                <h2 style="font-size:32px; margin-bottom:10px; color:var(--fnd-text);" tabindex="-1">Request Received</h2>
                <div style="font-size: 16px; text-transform: uppercase; letter-spacing: 1px; color: var(--fnd-text-muted); margin-bottom: 10px; margin-top: 40px;">Estimated Investment</div>
                <h1 style="font-size: 80px; color: var(--fnd-accent); margin: 0; font-weight:800;">£${totalPrice.toLocaleString()}</h1>
                <p style="color:var(--fnd-text-muted); margin-top: 20px; font-size:18px;">Thank you, ${$('#lead-name').val()}.<br>A detailed copy of your proposal has been sent to your email.</p>
                <button id="foundation-close-btn" type="button" style="margin-top:50px; background:transparent; border:2px solid var(--fnd-border); color:var(--fnd-text); padding:12px 35px; border-radius:50px; cursor:pointer; font-weight:600; font-size:16px;">Close Window</button>
            </div>
        `).hide().fadeIn(500, () => {
            // Move focus to the success heading
            getCanvas().find('h2').first().focus();
        });
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
