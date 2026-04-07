/**
 * FOUNDATION BUILDER v8.3 (Full Verbose Version)
 * Elementor-style admin for the quote calculator.
 *
 * Field types (kept for compatibility):
 * - service_card  (with variant: services | budget | timeline)
 * - range_slider
 * - toggle
 * - text_input
 * - section_title
 * - description
 * - divider
 * - rich_text     (NEW)
 * - file_upload   (NEW)
 *
 * Pricing keys (unchanged so front-end logic still works):
 * - field.price
 * - field.price_per_unit
 * - field.options[].price
 *
 * NEW:
 * - field.options[].route_step_id  (links a service to a details screen)
 * - Import/Export JSON functionality
 * - step.is_conditional (Hides step unless triggered)
 * - Collapsible inline "Choices & Routing" panel for service cards
 * - Reorderable screen tabs via drag handle
 * - Unique Step IDs to prevent routing bugs
 */

jQuery(document).ready(function ($) {

    // --------------------------------------------------
    // 0. SMALL UTIL
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

    // --------------------------------------------------
    // 1. GLOBAL STATE
    // --------------------------------------------------

    let appState = {
        activeStepIndex: 0,
        steps: [
            {
                id: 'step_1',
                title: 'Project Scope',
                subtitle: 'Fill in the details below.',
                is_conditional: false,
                fields: []
            }
        ]
    };

    let selectedFieldId = null; // field currently selected in the canvas

    // Tracks open/closed state of the inline "Choices & Routing" panel per field
    // true  = expanded
    // false = collapsed
    const choicesPanelState = {};

    // --------------------------------------------------
    // 2. LAYOUT
    // --------------------------------------------------

    const appLayout = `
        <div class="foundation-header">
            <h2>Project Calculator Builder</h2><br>
            <div class="foundation-header-actions">
                <button id="export-form-btn" class="button button-secondary" aria-label="Export form as JSON">Export JSON</button>
                <button id="import-form-btn" class="button button-secondary" aria-label="Import form from JSON">Import JSON</button>
                <input type="file" id="import-file-input" accept="application/json,.json" style="display:none;">
                
                <span id="save-status" class="save-status" style="display:none; margin-left: 10px;" aria-live="polite">Saved</span>
                <button id="save-form-btn" class="button button-primary button-large">Save Form</button>
            </div>
        </div>

        <div class="foundation-builder-wrapper" role="region" aria-label="Quote form builder">

            <!-- LEFT: TOOLBOX -->
            <div class="builder-col toolbox" aria-label="Field toolbox">
                <h3>Question types</h3>

                <div class="toolbox-section">
                    <h4>Customer questions</h4>
                    <div class="toolbox-items">

                        <div class="draggable-item"
                             data-type="service_card"
                             data-variant="services"
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Selection Cards</strong>
                                <small>Grid of clickable boxes with prices (e.g. Packages)</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="service_card"
                             data-variant="budget"
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Budget range</strong>
                                <small>Single choice budget bands (e.g. £6k–£10k).</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="service_card"
                             data-variant="timeline"
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Timeline</strong>
                                <small>When the project should be completed.</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="toggle"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-yes" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Yes/No Toggle</strong>
                                <small>Simple switch for add-ons (e.g. Rush Delivery)</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="range_slider"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-leftright" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Quantity Slider</strong>
                                <small>Drag to select a number (e.g. Number of Pages)</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="text_input"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-editor-paragraph" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Text Field</strong>
                                <small>Short answer input for user details</small>
                            </div>
                        </div>

                        <!-- NEW: Rich Text Item -->
                        <div class="draggable-item"
                             data-type="rich_text"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-editor-alignleft" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Project Brief</strong>
                                <small>Large rich text area for details.</small>
                            </div>
                        </div>

                        <!-- NEW: File Upload Item -->
                        <div class="draggable-item"
                             data-type="file_upload"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>File Upload</strong>
                                <small>Allow users to upload documents/images.</small>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="toolbox-section">
                    <h4>Content & layout</h4>
                    <div class="toolbox-items">

                        <div class="draggable-item"
                             data-type="section_title"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-heading" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Section title</strong>
                                <small>Big heading for a new section.</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="description"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-media-text" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Description text</strong>
                                <small>Helper text or notes.</small>
                            </div>
                        </div>

                        <div class="draggable-item"
                             data-type="divider"
                             data-variant=""
                             role="button"
                             tabindex="0">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-minus" aria-hidden="true"></span>
                            </div>
                            <div class="tool-info">
                                <strong>Divider / spacer</strong>
                                <small>Visual break between questions.</small>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- MIDDLE: CANVAS -->
            <div class="builder-col canvas" aria-label="Form preview">
                <div class="step-manager-bar">
                    <div id="step-tabs-container" class="step-tabs" role="tablist" aria-label="Steps"></div>
                    <button id="add-step-btn" class="button button-secondary add-step-btn" type="button" aria-label="Add new screen">
                        <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                        Add step
                    </button>
                </div>

                <div id="form-canvas-area"></div>
            </div>

            <!-- RIGHT: SETTINGS -->
            <div class="builder-col settings" aria-label="Settings panel">
                <h3>Selected question</h3>
                <div id="settings-content" class="settings-content">
                    <p style="color:#888; text-align:center; margin-top:40px;">
                        Select a question to edit it below, or click a step to change its title and subtitle.
                    </p>
                </div>
            </div>

        </div>
    `;

    $('#foundation-admin-app').html(appLayout);


    // --------------------------------------------------
    // 3. LOAD EXISTING DATA (from PHP / REST)
    // --------------------------------------------------

    function loadInitialData() {
        if (typeof foundationData === 'undefined') {
            renderApp();
            return;
        }

        $.ajax({
            url: foundationData.apiUrl + 'get',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', foundationData.nonce);
            },
            success: function (response) {
                if (response && Array.isArray(response) && response.length > 0) {
                    // New format: steps array
                    if (response[0].hasOwnProperty('fields')) {
                        appState.steps = response;
                    } else {
                        // Legacy: fields only, single step
                        appState.steps[0].fields = response;
                    }
                }
                
                // DATA SANITIZATION
                appState.steps.forEach(step => {
                    // FIX: Ensure boolean types for conditional logic
                    const rawCond = String(step.is_conditional).toLowerCase();
                    if (rawCond === 'false' || rawCond === '0' || rawCond === 'null' || rawCond === 'undefined' || !step.is_conditional) {
                        step.is_conditional = false;
                    } else {
                        step.is_conditional = true;
                    }

                    (step.fields || []).forEach(f => {
                        if (f.type === 'service_card' && !f.variant) {
                            f.variant = 'services';
                        }
                        // Ensure options exist as array
                        if (f.type === 'service_card' && !Array.isArray(f.options)) {
                            f.options = [];
                        }
                    });
                });
                renderApp();
            },
            error: function () {
                renderApp();
            }
        });
    }


    // --------------------------------------------------
    // 4. RENDER HELPERS
    // --------------------------------------------------

    function getActiveStep() {
        return appState.steps[appState.activeStepIndex] || appState.steps[0];
    }

    function renderApp() {
        renderStepTabs();
        renderCanvas();
        renderSettings();
    }

    // --- Step tabs ---

    function renderStepTabs() {
        const $tabs = $('#step-tabs-container');
        $tabs.empty();

        appState.steps.forEach((step, index) => {
            const isActive = index === appState.activeStepIndex;
            const canDelete = appState.steps.length > 1;

            const deleteBtnHtml = canDelete
                ? `<span class="dashicons dashicons-no-alt step-delete-btn" 
                          title="Remove screen" 
                          role="button" 
                          tabindex="0"
                          aria-label="Remove screen ${index + 1}"></span>`
                : '';

            const conditionalIcon = step.is_conditional
                ? `<span class="dashicons dashicons-hidden" title="Conditional (Hidden by default)" style="font-size:12px; height:12px; width:12px; margin-left:4px; color:#f0b849;"></span>`
                : '';

            const $tab = $(`
                <div class="step-tab ${isActive ? 'is-active' : ''}"
                        data-index="${index}"
                        role="tab"
                        aria-selected="${isActive ? 'true' : 'false'}"
                        tabindex="${isActive ? '0' : '-1'}">
                    <span class="step-tab-drag" title="Drag to reorder">
                        <span class="dashicons dashicons-move" aria-hidden="true"></span>
                    </span>
                    <span class="step-tab-title">${escapeHtml(step.title || 'Screen ' + (index + 1))}</span>
                    ${conditionalIcon}
                    ${deleteBtnHtml}
                </div>
            `);
            $tabs.append($tab);
        });

        initStepTabSorting();
    }

    // Make the step tabs sortable (Grid/Multi-line supported)
    function initStepTabSorting() {
        const $tabs = $('#step-tabs-container');
        if (!$.fn.sortable) return;

        // Destroy existing sortable (if any) to avoid duplicate bindings
        try {
            $tabs.sortable('destroy');
        } catch (e) {}

        $tabs.sortable({
            // axis: 'x', // REMOVED: This was causing the jitter on multi-line grids
            items: '.step-tab',
            handle: '.step-tab-drag',
            tolerance: 'pointer',
            cursor: 'grabbing',
            distance: 5, // Prevents accidental dragging on normal clicks
            opacity: 0.8,
            forcePlaceholderSize: true, // Crucial for flex/inline-block
            start: function(e, ui) {
                // Force placeholder dimensions to match the item being dragged
                // This prevents the container from collapsing/expanding rapidly
                ui.placeholder.width(ui.item.outerWidth());
                ui.placeholder.height(ui.item.outerHeight());
                ui.placeholder.css({
                    'visibility': 'visible',
                    'background': 'rgba(0,0,0,0.05)',
                    'border-radius': '4px'
                });
            },
            stop: function () {
                const oldSteps = appState.steps.slice();
                const newSteps = [];
                let newActiveIndex = 0;

                $tabs.find('.step-tab').each(function (i) {
                    const oldIndex = parseInt($(this).attr('data-index'), 10);
                    newSteps.push(oldSteps[oldIndex]);
                    if (oldIndex === appState.activeStepIndex) {
                        newActiveIndex = i;
                    }
                });

                appState.steps = newSteps;
                appState.activeStepIndex = newActiveIndex;

                // Re-render everything to refresh data-index attributes and UI
                renderApp();
            }
        });
    }

    // --- Canvas ---

    function renderCanvas() {
        const step = getActiveStep();
        const $canvas = $('#form-canvas-area');
        $canvas.empty();

        // Show conditional status in canvas
        if (step.is_conditional) {
            $canvas.append(`
                <div style="background:#fff8e5; border:1px solid #f0c33c; padding:10px; margin-bottom:15px; border-radius:4px; font-size:12px;">
                    <span class="dashicons dashicons-info" style="vertical-align:middle; color:#dba617;"></span> 
                    <strong>Conditional Screen:</strong> This screen will be skipped unless a Service Card option specifically links to it.
                </div>
            `);
        }

        if (!step.fields || !step.fields.length) {
            $canvas.append('<p class="canvas-empty">Add your first field by clicking a widget on the left.</p>');
            return;
        }

        step.fields.forEach(field => {
            const isSelected = field.id === selectedFieldId;

            let innerHtml = `
                <div class="field-header-bar">
                    <span class="field-type-label">${escapeHtml(getFieldTypeLabel(field))}</span>
                    <div class="field-actions" aria-label="Field actions">
                        <button type="button" class="field-action-btn move-up" aria-label="Move field up">
                            <span class="dashicons dashicons-arrow-up-alt" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="field-action-btn move-down" aria-label="Move field down">
                            <span class="dashicons dashicons-arrow-down-alt" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="field-action-btn delete-field" aria-label="Remove field">
                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="field-body-preview">
                    ${getFieldPreviewHTML(field)}
                    <div class="preview-hint">Click to edit details and pricing on the right.</div>
                </div>
            `;

            // Inline Choices & Routing editor when a service_card is selected
            if (isSelected && field.type === 'service_card') {
                if (typeof choicesPanelState[field.id] === 'undefined') {
                    choicesPanelState[field.id] = true; // default open
                }

                const isOpen = !!choicesPanelState[field.id];
                const iconClass = isOpen ? 'dashicons-arrow-down' : 'dashicons-arrow-right';
                const optionCount = (field.options && field.options.length) ? field.options.length : 0;
                const badgeLabel = optionCount === 1 ? '1 option' : optionCount + ' options';

                innerHtml += `
                    <div class="inline-routing-editor">
                        <div class="choices-panel-header" data-field-id="${field.id}">
                            <span class="dashicons ${iconClass}"></span>
                            <span class="choices-title">Choices & Routing</span>
                            <span class="choices-badge">${badgeLabel}</span>
                        </div>

                        <div class="choices-panel-body" style="${isOpen ? '' : 'display:none;'}">
                            <p class="settings-help" style="margin:5px 0 15px 0;">
                                Define your options below. Use the dropdown to route a choice to a specific screen.
                            </p>
                            <div id="options-repeater-${field.id}" class="options-repeater-inline"></div>
                            <button type="button" class="button add-option-btn" data-field-id="${field.id}" style="margin-top:10px;">Add choice</button>
                        </div>
                    </div>
                `;
            }

            const $field = $(`
                <div class="canvas-field ${isSelected ? 'selected' : ''}"
                     data-id="${field.id}"
                     tabindex="0"
                     role="group"
                     aria-label="${escapeHtml(getFieldTypeLabel(field))}">
                    ${innerHtml}
                </div>
            `);

            $canvas.append($field);
        });

        // After rendering fields, initialise inline routing for selected service_card
        if (selectedFieldId) {
            const stepAgain = getActiveStep();
            const field = stepAgain.fields.find(f => f.id === selectedFieldId && f.type === 'service_card');
            if (field) {
                const isOpen = !!choicesPanelState[field.id];

                // Bind header toggle
                $(`.choices-panel-header[data-field-id="${field.id}"]`)
                    .off('click')
                    .on('click', function (e) {
                        e.stopPropagation();
                        toggleChoicesPanel(field.id);
                    });

                // Only render the repeater if panel is open
                if (isOpen) {
                    const $container = $(`#options-repeater-${field.id}`);
                    if ($container.length) {
                        renderOptionsRepeater(field, $container);
                    }
                }
            }
        }
    }

    function toggleChoicesPanel(fieldId) {
        const current = !!choicesPanelState[fieldId];
        choicesPanelState[fieldId] = !current;
        renderCanvas();
    }

    function getFieldTypeLabel(field) {
        if (field.type === 'service_card') {
            switch (field.variant) {
                case 'budget':
                    return 'Budget range';
                case 'timeline':
                    return 'Timeline';
                default:
                    return 'Service options';
            }
        }
        if (field.type === 'range_slider') return 'Quantity slider';
        if (field.type === 'toggle') return 'Yes / No option';
        if (field.type === 'text_input') return 'Short text answer';
        if (field.type === 'rich_text') return 'Project Brief';
        if (field.type === 'file_upload') return 'File Upload';
        if (field.type === 'section_title') return 'Section title';
        if (field.type === 'description') return 'Description text';
        if (field.type === 'divider') return 'Divider / spacer';
        return 'Field';
    }

    function getFieldPreviewHTML(field) {
        if (field.type === 'service_card') {
            const title = field.label || defaultServiceCardLabel(field.variant);
            const helper = field.helper || defaultServiceCardHelper(field.variant);
            const options = field.options || [];
            const pills = options.length
                ? options.slice(0, 4).map(o => `<span class="preview-pill">${escapeHtml(o.label || '')}</span>`).join('')
                : '<span class="preview-pill preview-pill-empty">Add choices in the settings →</span>';

            return `
                <div class="preview-label">${escapeHtml(title)}</div>
                <div class="preview-sub">${escapeHtml(helper)}</div>
                <div class="preview-pill-row">${pills}</div>
            `;
        }

        if (field.type === 'range_slider') {
            return `
                <div class="preview-label">${escapeHtml(field.label || 'Quantity')}</div>
                <div class="preview-range" aria-hidden="true">
                    <div class="preview-range-minmax">${field.min || 1}</div>
                    <div class="preview-range-track">
                        <div class="preview-range-thumb"></div>
                    </div>
                    <div class="preview-range-minmax">${field.max || 50}</div>
                </div>
                <div class="preview-sub">
                    £${field.price_per_unit || 0} per ${escapeHtml(field.unit || 'unit')}
                </div>
            `;
        }

        if (field.type === 'toggle') {
            return `
                <div class="preview-label">${escapeHtml(field.label || 'Include this extra?')}</div>
                <div class="preview-toggle-row" aria-hidden="true">
                    <div class="preview-toggle-pill">${escapeHtml(field.yes_label || 'Yes')}</div>
                    <div class="preview-toggle-pill">${escapeHtml(field.no_label || 'No')}</div>
                </div>
                <div class="preview-sub">Add-on price: £${field.price || 0}</div>
            `;
        }

        if (field.type === 'text_input') {
            return `
                <div class="preview-label">${escapeHtml(field.label || 'Short answer')}</div>
                <div class="preview-input-placeholder">
                    ${escapeHtml(field.placeholder || 'Type answer here…')}
                </div>
            `;
        }

        if (field.type === 'rich_text') {
            return `
                <div class="preview-label">${escapeHtml(field.label || 'Project Brief')}</div>
                <div class="preview-input-placeholder" style="height:80px; background:#fafafa; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#999;">
                    <span class="dashicons dashicons-editor-alignleft" style="margin-right:5px;"></span> Large Rich Text Area
                </div>
                <div class="preview-sub">${escapeHtml(field.helper || '')}</div>
            `;
        }

        if (field.type === 'file_upload') {
            return `
                <div class="preview-label">${escapeHtml(field.label || 'Upload documents')}</div>
                <div class="preview-input-placeholder" style="height:60px; background:#f0f6fc; border:1px dashed #2271b1; display:flex; align-items:center; justify-content:center; color:#2271b1;">
                    <span class="dashicons dashicons-upload" style="margin-right:8px;"></span> Drag & drop files here
                </div>
                <div class="preview-sub">${escapeHtml(field.helper || '')}</div>
            `;
        }

        if (field.type === 'section_title') {
            return `
                <div class="preview-label" style="font-size:15px;">
                    ${escapeHtml(field.label || 'Section title')}
                </div>
                <div class="preview-sub">${escapeHtml(field.helper || '')}</div>
            `;
        }

        if (field.type === 'description') {
            return `
                <div class="preview-sub">
                    ${escapeHtml(field.text || 'Description text shown above / below questions.')}
                </div>
            `;
        }

        if (field.type === 'divider') {
            return `<hr class="preview-divider" />`;
        }

        return '<div class="preview-label">Field</div>';
    }

    function defaultServiceCardLabel(variant) {
        if (variant === 'budget') return 'Do you have a budget allocated?';
        if (variant === 'timeline') return 'When are you aiming to complete the project?';
        return 'Which services do you need?';
    }

    function defaultServiceCardHelper(variant) {
        if (variant === 'budget') return 'Pick the range that feels closest.';
        if (variant === 'timeline') return 'Rough timings are fine.';
        return 'You can select multiple services.';
    }


    // --------------------------------------------------
    // 5. SETTINGS PANEL
    // --------------------------------------------------

    function renderSettings() {
        const step = getActiveStep();
        const $settings = $('#settings-content');

        const field = step.fields.find(f => f.id === selectedFieldId);

        // No field selected → show screen settings
        if (!field) {
            const html = `
                <div class="settings-group">
                    <h4 class="settings-heading">Screen Settings</h4>
                    <label for="step-title-input">Screen Title</label>
                    <input type="text" id="step-title-input" value="${escapeHtml(step.title || '')}">
                    <p class="settings-help">Shown as the main heading on this screen.</p>
                </div>

                <div class="settings-group">
                    <h4 class="settings-heading">Subtitle</h4>
                    <label for="step-subtitle-input">Subtitle (optional)</label>
                    <input type="text" id="step-subtitle-input" value="${escapeHtml(step.subtitle || '')}">
                    <p class="settings-help">Optional helper line under the title.</p>
                </div>

                <div class="settings-group" style="background:#f9f9f9; padding:10px; border:1px solid #eee; border-radius:4px;">
                    <h4 class="settings-heading">Flow Logic</h4>
                    <label class="settings-checkbox">
                        <input type="checkbox" id="step-conditional-input" ${step.is_conditional ? 'checked' : ''}>
                        <span>Conditional Screen (Hidden by default)</span>
                    </label>
                    <p class="settings-help">
                        If checked, this screen will be <strong>skipped</strong> unless a Service Option specifically links to it via the "Route to" dropdown.
                    </p>
                </div>
            `;
            $settings.html(html);

            $('#step-title-input').on('input', function () {
                step.title = $(this).val();
                renderStepTabs();
            });

            $('#step-subtitle-input').on('input', function () {
                step.subtitle = $(this).val();
            });

            $('#step-conditional-input').on('change', function () {
                step.is_conditional = $(this).is(':checked');
                renderStepTabs();
                renderCanvas();
            });

            return;
        }

        // Field selected → field settings
        let html = '';

        if (['service_card', 'range_slider', 'toggle', 'text_input', 'rich_text', 'file_upload'].includes(field.type)) {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Question title</h4>
                    <label for="setting-label">What do you want to ask?</label>
                    <input type="text" id="setting-label" value="${escapeHtml(field.label || '')}">
                    <p class="settings-help">This is the main question the customer sees.</p>
                </div>
            `;
        }

        if (field.type === 'service_card') {
            const variant = field.variant || 'services';
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Purpose</h4>
                    <label for="setting-variant">What is this picker used for?</label>
                    <select id="setting-variant">
                        <option value="services" ${variant === 'services' ? 'selected' : ''}>Service options</option>
                        <option value="budget" ${variant === 'budget' ? 'selected' : ''}>Budget range</option>
                        <option value="timeline" ${variant === 'timeline' ? 'selected' : ''}>Timeline</option>
                    </select>
                    <p class="settings-help">
                        This only affects labels and layout, not pricing.
                    </p>
                </div>

                <div class="settings-group">
                    <h4 class="settings-heading">Small description (optional)</h4>
                    <label for="setting-helper">Helper text shown under the question</label>
                    <input type="text" id="setting-helper"
                           value="${escapeHtml(field.helper || '')}"
                           placeholder="e.g. You can select more than one.">
                </div>

                <p style="color:#666; font-style:italic; margin-top:20px;">
                    <span class="dashicons dashicons-arrow-left-alt"></span> 
                    Edit <strong>Choices & Routing</strong> directly under the selected field in the main canvas.
                </p>
            `;
        }

        if (field.type === 'range_slider') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Slider settings</h4>
                    <div class="settings-grid-3">
                        <div>
                            <label for="setting-min">Minimum</label>
                            <input type="number" id="setting-min" value="${field.min || 1}">
                        </div>
                        <div>
                            <label for="setting-max">Maximum</label>
                            <input type="number" id="setting-max" value="${field.max || 50}">
                        </div>
                        <div>
                            <label for="setting-step">Step</label>
                            <input type="number" id="setting-step" value="${field.step || 1}">
                        </div>
                    </div>
                    <div class="settings-grid-2">
                        <div>
                            <label for="setting-unit">Unit label</label>
                            <input type="text" id="setting-unit" value="${escapeHtml(field.unit || 'pages')}">
                        </div>
                        <div>
                            <label for="setting-price-per-unit">Price per unit (£)</label>
                            <input type="number" step="0.01" id="setting-price-per-unit" value="${field.price_per_unit || 0}">
                        </div>
                    </div>
                </div>
            `;
        }

        if (field.type === 'toggle') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Yes / No labels</h4>
                    <div class="settings-grid-2">
                        <div>
                            <label for="setting-yes-label">Yes label</label>
                            <input type="text" id="setting-yes-label" value="${escapeHtml(field.yes_label || 'Yes')}">
                        </div>
                        <div>
                            <label for="setting-no-label">No label</label>
                            <input type="text" id="setting-no-label" value="${escapeHtml(field.no_label || 'No')}">
                        </div>
                    </div>
                    <div class="settings-group-inner">
                        <label for="setting-toggle-price">Add-on price (£)</label>
                        <input type="number" step="0.01" id="setting-toggle-price" value="${field.price || 0}">
                    </div>
                </div>
            `;
        }

        if (field.type === 'text_input') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Input settings</h4>
                    <label for="setting-placeholder">Placeholder</label>
                    <input type="text" id="setting-placeholder"
                           value="${escapeHtml(field.placeholder || '')}"
                           placeholder="Type answer here…">
                    <label class="settings-checkbox">
                        <input type="checkbox" id="setting-required" ${field.required ? 'checked' : ''}>
                        <span>Required field</span>
                    </label>
                </div>
            `;
        }

        if (field.type === 'rich_text') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Rich Text Settings</h4>
                    <label for="setting-helper">Helper Text (Instructions)</label>
                    <input type="text" id="setting-helper" value="${escapeHtml(field.helper || '')}" placeholder="e.g. Please include as much detail as possible.">
                </div>
                <div class="settings-group">
                    <label for="setting-placeholder">Placeholder</label>
                    <input type="text" id="setting-placeholder" value="${escapeHtml(field.placeholder || '')}" placeholder="Start typing...">
                </div>
                <div class="settings-group">
                    <label class="settings-checkbox">
                        <input type="checkbox" id="setting-required" ${field.required ? 'checked' : ''}>
                        <span>Required field</span>
                    </label>
                </div>
            `;
        }

        if (field.type === 'file_upload') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">File Upload Settings</h4>
                    <label for="setting-helper">Helper Text</label>
                    <input type="text" id="setting-helper" value="${escapeHtml(field.helper || '')}" placeholder="e.g. PDF, PNG, JPG (Max 10MB)">
                </div>
                <div class="settings-group">
                    <label class="settings-checkbox">
                        <input type="checkbox" id="setting-required" ${field.required ? 'checked' : ''}>
                        <span>Required field</span>
                    </label>
                </div>
            `;
        }

        if (field.type === 'section_title') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Title</h4>
                    <label for="setting-label">Section heading</label>
                    <input type="text" id="setting-label" value="${escapeHtml(field.label || '')}" placeholder="e.g. Project scope">
                </div>
                <div class="settings-group">
                    <h4 class="settings-heading">Subtitle (optional)</h4>
                    <label for="setting-helper">Short helper line</label>
                    <input type="text" id="setting-helper" value="${escapeHtml(field.helper || '')}" placeholder="Short helper line.">
                </div>
            `;
        }

        if (field.type === 'description') {
            html += `
                <div class="settings-group">
                    <h4 class="settings-heading">Description text</h4>
                    <label for="setting-text">Paragraph text</label>
                    <textarea id="setting-text" rows="4" style="width:100%;">${escapeHtml(field.text || '')}</textarea>
                    <p class="settings-help">Plain text shown as a paragraph.</p>
                </div>
            `;
        }

        $settings.html(html);

        // Shared label binding
        if ($('#setting-label').length) {
            $('#setting-label').on('input', function () {
                field.label = $(this).val();
                renderCanvas();
            });
        }

        // Type-specific bindings
        if (field.type === 'service_card') {
            $('#setting-helper').on('input', function () {
                field.helper = $(this).val();
                renderCanvas();
            });

            $('#setting-variant').on('change', function () {
                field.variant = $(this).val();
                if (!field.label) field.label = defaultServiceCardLabel(field.variant);
                if (!field.helper) field.helper = defaultServiceCardHelper(field.variant);
                renderCanvas();
            });
        }

        if (field.type === 'range_slider') {
            $('#setting-min').on('input', function () {
                field.min = parseInt(this.value || 0, 10);
                renderCanvas();
            });
            $('#setting-max').on('input', function () {
                field.max = parseInt(this.value || 0, 10);
                renderCanvas();
            });
            $('#setting-step').on('input', function () {
                field.step = parseInt(this.value || 1, 10);
            });
            $('#setting-unit').on('input', function () {
                field.unit = this.value;
                renderCanvas();
            });
            $('#setting-price-per-unit').on('input', function () {
                field.price_per_unit = parseFloat(this.value || 0);
                renderCanvas();
            });
        }

        if (field.type === 'toggle') {
            $('#setting-yes-label').on('input', function () {
                field.yes_label = this.value;
                renderCanvas();
            });
            $('#setting-no-label').on('input', function () {
                field.no_label = this.value;
                renderCanvas();
            });
            $('#setting-toggle-price').on('input', function () {
                field.price = parseFloat(this.value || 0);
                renderCanvas();
            });
        }

        if (field.type === 'text_input') {
            $('#setting-placeholder').on('input', function () {
                field.placeholder = this.value;
                renderCanvas();
            });
            $('#setting-required').on('change', function () {
                field.required = $(this).is(':checked');
            });
        }

        if (field.type === 'rich_text') {
            $('#setting-helper').on('input', function () {
                field.helper = this.value;
                renderCanvas();
            });
            $('#setting-placeholder').on('input', function () {
                field.placeholder = this.value;
                renderCanvas();
            });
            $('#setting-required').on('change', function () {
                field.required = $(this).is(':checked');
            });
        }

        if (field.type === 'file_upload') {
            $('#setting-helper').on('input', function () {
                field.helper = this.value;
                renderCanvas();
            });
            $('#setting-required').on('change', function () {
                field.required = $(this).is(':checked');
            });
        }

        if (field.type === 'section_title') {
            $('#setting-helper').on('input', function () {
                field.helper = this.value;
                renderCanvas();
            });
        }

        if (field.type === 'description') {
            $('#setting-text').on('input', function () {
                field.text = this.value;
                renderCanvas();
            });
        }
    }

    // --------------------------------------------------
    // 6. REPEATER for service_card options – inline under field
    // --------------------------------------------------

    function renderOptionsRepeater(field, $container) {
        if (!Array.isArray(field.options)) {
            field.options = [];
        }

        $container.empty();

        (field.options || []).forEach((opt, index) => {
            const routeStepId = opt.route_step_id || '';

            let routeOptionsHtml = '<option value="">— Route to... —</option>';
            appState.steps.forEach((step, stepIdx) => {
                if (stepIdx === appState.activeStepIndex) return; // Don't link to self

                const selectedAttr = step.id === routeStepId ? 'selected' : '';
                const conditionalBadge = step.is_conditional ? ' (Cond.)' : '';
                routeOptionsHtml += `<option value="${step.id}" ${selectedAttr}>${escapeHtml(step.title || 'Screen ' + (stepIdx + 1))}${conditionalBadge}</option>`;
            });

            const row = $(`
                <div class="repeater-row" data-index="${index}">
                    <input type="text"
                           class="opt-label"
                           value="${escapeHtml(opt.label || '')}"
                           placeholder="Label">
                    <input type="number"
                           step="0.01"
                           class="opt-price"
                           value="${opt.price || 0}"
                           placeholder="Price (£)">
                    <select class="opt-route" title="Route to specific screen">
                        ${routeOptionsHtml}
                    </select>
                    <span class="dashicons dashicons-move handle" title="Drag to reorder" aria-hidden="true"></span>
                    <span class="dashicons dashicons-trash remove-row" title="Remove" aria-hidden="true"></span>
                </div>
            `);
            $container.append(row);
        });

        const $editor = $container.closest('.inline-routing-editor');
        const $addBtn = $editor.find('.add-option-btn');
        const $badge = $editor.find('.choices-badge');

        function updateBadge() {
            const count = field.options.length;
            const label = count === 1 ? '1 option' : count + ' options';
            $badge.text(label);
        }

        $addBtn.off('click').on('click', function () {
            field.options.push({ label: 'New choice', price: 0, route_step_id: '' });
            renderOptionsRepeater(field, $container);
            renderCanvas();
        });

        $container.find('.opt-label').on('input', function () {
            const idx = $(this).closest('.repeater-row').data('index');
            field.options[idx].label = $(this).val();
            renderCanvas();
        });

        $container.find('.opt-price').on('input', function () {
            const idx = $(this).closest('.repeater-row').data('index');
            field.options[idx].price = parseFloat(this.value || 0);
        });

        $container.find('.opt-route').on('change', function () {
            const idx = $(this).closest('.repeater-row').data('index');
            const value = $(this).val();
            field.options[idx].route_step_id = value || '';
        });

        $container.find('.remove-row').on('click', function () {
            const idx = $(this).closest('.repeater-row').data('index');
            field.options.splice(idx, 1);
            renderOptionsRepeater(field, $container);
            renderCanvas();
        });

        if ($.fn.sortable) {
            $container.sortable({
                handle: '.handle',
                update: function () {
                    const newOrder = [];
                    $container.find('.repeater-row').each(function () {
                        const idx = $(this).data('index');
                        newOrder.push(field.options[idx]);
                    });
                    field.options = newOrder;
                    renderOptionsRepeater(field, $container);
                    renderCanvas();
                }
            });
        }

        updateBadge();
    }


    // --------------------------------------------------
    // 7. EVENTS – TOOLBOX, CANVAS, STEPS
    // --------------------------------------------------

    $(document).on('click', '.draggable-item', function () {
        const type = $(this).data('type');
        const variant = $(this).data('variant') || null;
        addFieldToActiveStep(type, variant);
    });

    $(document).on('keydown', '.draggable-item', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    $(document).on('click', '.step-tab', function (e) {
        if ($(e.target).closest('.step-delete-btn, .step-tab-drag').length) return;

        const index = parseInt($(this).data('index'), 10);
        appState.activeStepIndex = index;
        selectedFieldId = null;
        renderApp();
    });

    $(document).on('click', '.step-delete-btn', function (e) {
        e.stopPropagation();
        const $tab = $(this).closest('.step-tab');
        const indexToDelete = parseInt($tab.data('index'), 10);

        if (!confirm('Are you sure you want to delete this screen? This cannot be undone.')) {
            return;
        }

        // AUTO-CLEANUP: Remove routes pointing to this deleted step
        const stepIdToDelete = appState.steps[indexToDelete].id;
        appState.steps.forEach(s => {
            (s.fields || []).forEach(f => {
                if (Array.isArray(f.options)) {
                    f.options.forEach(opt => {
                        if (opt.route_step_id === stepIdToDelete) {
                            opt.route_step_id = ''; // Reset to default flow
                        }
                    });
                }
            });
        });

        appState.steps.splice(indexToDelete, 1);

        if (appState.activeStepIndex >= appState.steps.length) {
            appState.activeStepIndex = Math.max(0, appState.steps.length - 1);
        } else if (appState.activeStepIndex === indexToDelete) {
            appState.activeStepIndex = Math.min(indexToDelete, appState.steps.length - 1);
        }

        selectedFieldId = null;
        renderApp();
    });

    $('#add-step-btn').on('click', function () {
        // FIX: Use Date.now() to ensure unique IDs. Old method used length+1 which caused duplicates.
        const uniqueId = 'step_' + Date.now();
        const newStep = {
            id: uniqueId,
            title: 'Screen ' + (appState.steps.length + 1),
            subtitle: '',
            is_conditional: false,
            fields: []
        };
        appState.steps.push(newStep);
        appState.activeStepIndex = appState.steps.length - 1;
        selectedFieldId = null;
        renderApp();
    });

    $(document).on('click', '.canvas-field', function (e) {
        if ($(e.target).closest('.field-actions, .inline-routing-editor').length) return;
        const id = $(this).data('id');
        selectedFieldId = id;
        renderApp();
    });

    $(document).on('keydown', '.canvas-field', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            if ($(e.target).closest('.field-actions, .inline-routing-editor').length) return;
            const id = $(this).data('id');
            selectedFieldId = id;
            renderApp();
        }
    });

    $(document).on('click', '.canvas-field .delete-field', function (e) {
        e.stopPropagation();
        const id = $(this).closest('.canvas-field').data('id');
        const step = getActiveStep();
        step.fields = step.fields.filter(f => f.id !== id);
        if (selectedFieldId === id) selectedFieldId = null;
        renderApp();
    });

    $(document).on('click', '.canvas-field .move-up', function (e) {
        e.stopPropagation();
        const id = $(this).closest('.canvas-field').data('id');
        const step = getActiveStep();
        const idx = step.fields.findIndex(f => f.id === id);
        if (idx > 0) {
            [step.fields[idx - 1], step.fields[idx]] = [step.fields[idx], step.fields[idx - 1]];
            renderCanvas();
        }
    });

    $(document).on('click', '.canvas-field .move-down', function (e) {
        e.stopPropagation();
        const id = $(this).closest('.canvas-field').data('id');
        const step = getActiveStep();
        const idx = step.fields.findIndex(f => f.id === id);
        if (idx < step.fields.length - 1) {
            [step.fields[idx + 1], step.fields[idx]] = [step.fields[idx], step.fields[idx + 1]];
            renderCanvas();
        }
    });


    // --------------------------------------------------
    // 8. ADD FIELD
    // --------------------------------------------------

    function addFieldToActiveStep(type, variant) {
        const step = getActiveStep();
        const id = 'field_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

        const field = {
            id: id,
            type: type,
            variant: variant || null
        };

        if (type === 'service_card') {
            field.variant = variant || 'services';
            field.label = defaultServiceCardLabel(field.variant);
            field.helper = defaultServiceCardHelper(field.variant);
            field.options = [
                { label: field.variant === 'budget' ? '£6k–£10k' : 'Option 1', price: 0, route_step_id: '' },
                { label: field.variant === 'budget' ? '£10k–£15k' : 'Option 2', price: 0, route_step_id: '' }
            ];
        } else if (type === 'range_slider') {
            field.label = 'How many pages do you need?';
            field.min = 1;
            field.max = 50;
            field.step = 1;
            field.unit = 'pages';
            field.price_per_unit = 0;
        } else if (type === 'toggle') {
            field.label = 'Do you want ongoing support?';
            field.yes_label = 'Yes';
            field.no_label = 'No';
            field.price = 0;
        } else if (type === 'text_input') {
            field.label = 'Tell us more about your project';
            field.placeholder = 'Type answer here…';
            field.required = false;
        } else if (type === 'rich_text') {
            field.label = 'Project Brief';
            field.placeholder = 'Start typing...';
            field.helper = 'Please include as much detail as possible.';
            field.required = true;
        } else if (type === 'file_upload') {
            field.label = 'Upload Branding / Assets';
            field.helper = 'Upload any relevant files (PDF, JPG, PNG).';
            field.required = false;
        } else if (type === 'section_title') {
            field.label = 'Section title';
            field.helper = '';
        } else if (type === 'description') {
            field.text = 'Description text.';
        } else if (type === 'divider') {
            // nothing extra
        }

        step.fields.push(field);
        selectedFieldId = id;
        renderApp();
    }


    // --------------------------------------------------
    // 9. SAVE & EXPORT
    // --------------------------------------------------

    $('#save-form-btn').on('click', function () {
        if (typeof foundationData === 'undefined') {
            alert('Cannot save: foundationData is missing.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).text('Saving…');

        $.ajax({
            url: foundationData.apiUrl + 'save',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', foundationData.nonce);
            },
            data: {
                form_data: appState.steps
            },
            success: function () {
                $btn.prop('disabled', false).text('Save Form');
                $('#save-status').fadeIn().delay(2000).fadeOut();
            },
            error: function () {
                alert('Save failed.');
                $btn.prop('disabled', false).text('Save Form');
            }
        });
    });

    $('#export-form-btn').on('click', function () {
        try {
            const json = JSON.stringify(appState.steps, null, 2);
            const blob = new Blob([json], { type: 'application/json' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'foundation-form.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (e) {
            console.error('Export failed:', e);
            alert('Failed to generate export file.');
        }
    });

    $('#import-form-btn').on('click', function () {
        $('#import-file-input').click();
    });

    $('#import-file-input').on('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            try {
                let jsonString = event.target.result;

                if (jsonString.startsWith('{\\rtf')) {
                    throw new Error('The file appears to be in Rich Text Format (RTF), not plain JSON. Please convert it to Plain Text.');
                }

                const parsedData = JSON.parse(jsonString);
                let stepsToImport = [];

                if (Array.isArray(parsedData)) {
                    stepsToImport = parsedData;
                } else if (typeof parsedData === 'object' && parsedData !== null && Array.isArray(parsedData.steps)) {
                    stepsToImport = parsedData.steps;
                } else {
                    throw new Error('Invalid format: Root must be an array of steps or an object containing a "steps" array.');
                }

                if (stepsToImport.length > 0) {
                    if (!stepsToImport[0].id && !stepsToImport[0].title) {
                        throw new Error('Invalid step format: Missing "id" or "title".');
                    }
                }

                stepsToImport.forEach(step => {
                    // FIX: Strict boolean conversion on import too
                    const rawCond = String(step.is_conditional).toLowerCase();
                    if (rawCond === 'false' || rawCond === '0' || rawCond === 'null' || rawCond === 'undefined' || !step.is_conditional) {
                        step.is_conditional = false;
                    } else {
                        step.is_conditional = true;
                    }

                    (step.fields || []).forEach(f => {
                        if (f.type === 'service_card' && !f.variant) {
                            f.variant = 'services';
                        }
                        if (f.type === 'service_card' && !Array.isArray(f.options)) {
                            f.options = [];
                        }
                    });
                });

                appState.steps = stepsToImport;
                appState.activeStepIndex = 0;
                selectedFieldId = null;

                Object.keys(choicesPanelState).forEach(key => delete choicesPanelState[key]);

                renderApp();

                alert('Form imported successfully!');
                $('#import-file-input').val('');

            } catch (err) {
                console.error(err);
                alert('Import Failed:\n' + err.message);
                $('#import-file-input').val('');
            }
        };
        reader.readAsText(file);
    });


    // --------------------------------------------------
    // 10. INIT
    // --------------------------------------------------

    loadInitialData();

});