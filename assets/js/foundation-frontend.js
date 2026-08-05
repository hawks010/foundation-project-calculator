(function () {
	'use strict';

	var config = window.foundationConfig || {};
	var branding = config.branding || {};
	var uploadRules = config.uploads || {};
	var pricingCatalog = config.pricingCatalog || {};
	var currency = branding.currencySymbol || '£';
	var quoteMode = Boolean(branding.quoteModeEnabled);
	var steps = normalizeSteps(Array.isArray(config.formData) ? config.formData : []);
	var overlay = document.getElementById('foundation-app-overlay');
	if (!overlay || !steps.length) return;

	var state = {
		view: 'intro',
		currentStepIndex: -1,
		selections: {},
		files: {},
		contact: { name: '', company: '', email: '', phone: '', website: '', notes: '', privacy: false },
		routeIds: new Set(),
		resumeToken: '',
		started: false,
		completed: false,
		lastActiveElement: null,
		submissionId: createSubmissionId(),
		savePanelReturnFocus: null
	};

	trackViewOnce();
	bindGlobalEvents();

	var resumeTokenFromUrl = getResumeTokenFromUrl();
	if (resumeTokenFromUrl) restoreDraft(resumeTokenFromUrl);
	if (window.foundationAutoOpen && !resumeTokenFromUrl) {
		window.foundationAutoOpen = false;
		window.setTimeout(openCalculator, 0);
	}

	function normalizeSteps(input) {
		return input.map(function (step, stepIndex) {
			var normalized = Object.assign({
				id: 'step_' + (stepIndex + 1),
				title: 'Screen ' + (stepIndex + 1),
				subtitle: '',
				is_conditional: false,
				fields: []
			}, step || {});
			normalized.is_conditional = normalizeBool(normalized.is_conditional);
			normalized.fields = Array.isArray(normalized.fields) ? normalized.fields.map(function (field, fieldIndex) {
				var item = Object.assign({ id: 'field_' + (fieldIndex + 1), type: 'text_input', label: '', helper: '', placeholder: '', required: false }, field || {});
				item.required = normalizeBool(item.required);
				item.options = Array.isArray(item.options) ? item.options : [];
				item.selection_mode = item.selection_mode === 'multi' ? 'multi' : 'single';
				return item;
			}) : [];
			return normalized;
		});
	}

	function normalizeBool(value) {
		if (typeof value === 'boolean') return value;
		return ['1', 'true', 'yes', 'on'].indexOf(String(value || '').toLowerCase()) !== -1;
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function createSubmissionId() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
		return 'fpc-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
	}

	function bindGlobalEvents() {
		document.addEventListener('foundation:open', function () {
			if (window.foundationAutoOpen) window.foundationAutoOpen = false;
			openCalculator();
		});
		document.addEventListener('keydown', function (event) {
			if (!overlay.classList.contains('is-active')) return;
			if (event.key === 'Escape') {
				var savePanel = overlay.querySelector('.foundation-save-panel');
				if (savePanel) closeSavePanel();
				else closeCalculator();
				return;
			}
			if (event.key === 'Tab') trapFocus(event);
		});
	}

	function openCalculator() {
		if (overlay.classList.contains('is-active')) return;
		state.lastActiveElement = document.activeElement;
		if (!state.resumeToken && !state.completed) {
			state.view = 'intro';
			state.currentStepIndex = -1;
		}
		buildShell();
		overlay.hidden = false;
		overlay.className = 'foundation-overlay is-active';
		document.documentElement.classList.add('foundation-modal-open');
		document.body.classList.add('foundation-modal-open');
		renderCurrentView();
		window.setTimeout(function () {
			var target = overlay.querySelector('[data-foundation-initial-focus], button, input, textarea, select, a[href]');
			if (target) target.focus();
		}, 20);
	}

	function closeCalculator() {
		if (state.started && !state.completed) trackEvent('incomplete');
		overlay.classList.remove('is-active');
		window.setTimeout(function () {
			overlay.hidden = true;
			overlay.innerHTML = '';
			document.documentElement.classList.remove('foundation-modal-open');
			document.body.classList.remove('foundation-modal-open');
			if (state.lastActiveElement && typeof state.lastActiveElement.focus === 'function') state.lastActiveElement.focus();
			if (state.completed) resetCompletedSession();
		}, 180);
	}

	function resetCompletedSession() {
		state.view = 'intro';
		state.currentStepIndex = -1;
		state.selections = {};
		state.files = {};
		state.contact = { name: '', company: '', email: '', phone: '', website: '', notes: '', privacy: false };
		state.routeIds = new Set();
		state.resumeToken = '';
		state.started = false;
		state.completed = false;
		state.serverQuote = null;
		state.customerEmailStatus = '';
		state.adminEmailStatus = '';
		state.serverMessage = '';
		state.reference = '';
		state.submissionId = createSubmissionId();
	}

	function trapFocus(event) {
		var scope = overlay.querySelector('.foundation-save-panel') || overlay;
		var nodes = Array.prototype.slice.call(scope.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'))
			.filter(function (node) { return node.offsetParent !== null && !node.hidden; });
		if (!nodes.length) return;
		var first = nodes[0];
		var last = nodes[nodes.length - 1];
		if (!scope.contains(document.activeElement)) {
			event.preventDefault();
			(event.shiftKey ? last : first).focus();
			return;
		}
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	function buildShell() {
		var logo = branding.logoUrl ? '<img class="foundation-modal-logo" src="' + escapeHtml(branding.logoUrl) + '" alt="">' : '';
		overlay.setAttribute('role', 'dialog');
		overlay.setAttribute('aria-modal', 'true');
		overlay.setAttribute('aria-labelledby', 'foundation-modal-title');
		overlay.innerHTML = '' +
			'<div class="foundation-modal">' +
				'<header class="foundation-modal-header">' +
					'<div class="foundation-brand">' + logo + '<div><span>Project estimate</span><strong id="foundation-modal-title">' + escapeHtml(branding.wizardTitle || 'Inkfire Project Calculator') + '</strong></div></div>' +
					'<div class="foundation-header-actions">' +
						'<button type="button" class="foundation-header-button" data-foundation-save-progress>Save progress</button>' +
						'<button type="button" class="foundation-close-button" data-foundation-close aria-label="Close calculator"><span aria-hidden="true">×</span></button>' +
					'</div>' +
				'</header>' +
				'<div class="foundation-progress-wrap" hidden><div class="foundation-progress-meta"><span data-foundation-progress-label></span><span data-foundation-progress-count></span></div><div class="foundation-progress-track" aria-hidden="true"><span data-foundation-progress-bar></span></div></div>' +
				'<div class="foundation-message" role="alert" aria-live="assertive" hidden></div>' +
				'<div class="foundation-modal-body">' +
					'<main class="foundation-canvas" data-foundation-canvas></main>' +
					'<aside class="foundation-live-summary" data-foundation-live-summary aria-label="Current estimate"></aside>' +
				'</div>' +
				'<footer class="foundation-modal-footer" hidden>' +
					'<button type="button" class="foundation-secondary-button" data-foundation-back>Back</button>' +
					'<button type="button" class="foundation-primary-button" data-foundation-next>Continue</button>' +
				'</footer>' +
			'</div>';

		overlay.querySelector('[data-foundation-close]').addEventListener('click', closeCalculator);
		overlay.querySelector('[data-foundation-save-progress]').addEventListener('click', openSavePanel);
		overlay.querySelector('[data-foundation-back]').addEventListener('click', goBack);
		overlay.querySelector('[data-foundation-next]').addEventListener('click', goNext);
	}

	function getCanvas() { return overlay.querySelector('[data-foundation-canvas]'); }
	function getFooter() { return overlay.querySelector('.foundation-modal-footer'); }
	function getNextButton() { return overlay.querySelector('[data-foundation-next]'); }
	function getBackButton() { return overlay.querySelector('[data-foundation-back]'); }

	function updateHeaderActions() {
		var saveButton = overlay.querySelector('[data-foundation-save-progress]');
		if (saveButton) saveButton.hidden = state.view === 'intro' || state.view === 'success' || state.completed;
	}

	function renderCurrentView() {
		clearMessage();
		state.routeIds = computeRouteIds();
		if (state.view === 'intro') renderIntro();
		else if (state.view === 'step') renderStep(state.currentStepIndex);
		else if (state.view === 'review') renderReview();
		else if (state.view === 'contact') renderContact();
		else if (state.view === 'success') renderSuccess(state.serverQuote || calculateQuote());
		updateHeaderActions();
		updateLiveSummary();
	}

	function renderIntro() {
		setProgress(false);
		setFooter(false);
		var image = branding.introImageUrl ? '<div class="foundation-intro-image" role="img" aria-label="Inkfire team" style="background-image:url(\'' + escapeHtml(String(branding.introImageUrl).replace(/'/g, '%27')) + '\')"></div>' : '';
		var testimonial = branding.testimonialQuote ? '<blockquote><p>' + escapeHtml(branding.testimonialQuote) + '</p><cite>' + escapeHtml(branding.testimonialAttribution || '') + '</cite></blockquote>' : '';
		getCanvas().innerHTML = '<section class="foundation-intro">' + image + '<div class="foundation-intro-copy"><p class="foundation-kicker">One joined-up estimate</p><h1>' + escapeHtml(branding.introHeading || 'Build your project estimate') + '</h1><p>' + escapeHtml(branding.introText || '') + '</p><ul class="foundation-intro-points"><li>One-off and monthly costs shown separately</li><li>Only relevant questions appear</li><li>Complex work is marked for a tailored quote</li></ul>' + testimonial + '<button type="button" class="foundation-primary-button foundation-large-button" data-foundation-start data-foundation-initial-focus>Start my estimate</button><p class="foundation-small-print">Usually takes around 3 to 6 minutes. You can save and return later.</p></div></section>';
		getCanvas().querySelector('[data-foundation-start]').addEventListener('click', function () {
			state.started = true;
			trackEvent('start');
			var visible = getVisibleStepIndexes();
			if (!visible.length) return showMessage('The calculator journey is not configured yet.');
			state.view = 'step';
			state.currentStepIndex = visible[0];
			renderCurrentView();
			focusViewHeading();
		});
	}

	function renderStep(index) {
		state.routeIds = computeRouteIds();
		var visible = getVisibleStepIndexes();
		if (visible.indexOf(index) === -1) {
			index = visible.length ? visible[0] : -1;
			state.currentStepIndex = index;
		}
		if (index < 0 || !steps[index]) {
			state.view = 'review';
			renderReview();
			return;
		}
		var step = steps[index];
		setProgress(true, visible.indexOf(index), visible.length, step.title);
		setFooter(true, visible.indexOf(index) > 0, 'Continue');
		var fieldsMarkup = (step.fields || []).map(renderField).join('');
		getCanvas().innerHTML = '<section class="foundation-step" data-step-id="' + escapeHtml(step.id) + '"><p class="foundation-kicker">Your project</p><h1 tabindex="-1" data-foundation-view-heading>' + escapeHtml(step.title) + '</h1>' + (step.subtitle ? '<p class="foundation-step-intro">' + escapeHtml(step.subtitle) + '</p>' : '') + '<div class="foundation-fields">' + fieldsMarkup + '</div></section>';
		bindStepInputs(step);
	}

	function renderField(field) {
		if (!field || ['calculation'].indexOf(field.type) !== -1) return '';
		if (field.type === 'section_title') return '<h2 class="foundation-section-title">' + escapeHtml(field.text || field.label) + '</h2>';
		if (field.type === 'description') return '<p class="foundation-description">' + escapeHtml(field.text || '') + '</p>';
		if (field.type === 'divider') return '<hr class="foundation-divider">';
		var required = field.required ? '<span class="foundation-required">Required</span>' : '';
		var helper = field.helper ? '<p class="foundation-field-helper" id="' + escapeHtml(field.id) + '-help">' + escapeHtml(field.helper) + '</p>' : '';
		var header = '<div class="foundation-field-heading"><legend>' + escapeHtml(field.label || '') + '</legend>' + required + '</div>' + helper;

		if (field.type === 'service_card') {
			var selected = getSelectedIndexes(field.id);
			var isMulti = field.selection_mode === 'multi';
			var type = isMulti ? 'checkbox' : 'radio';
			var options = (field.options || []).map(function (option, index) {
				var checked = selected.indexOf(String(index)) !== -1 ? ' checked' : '';
				var hint = getOptionHint(option);
				return '<label class="foundation-option-card' + (checked ? ' is-selected' : '') + '"><input type="' + type + '" name="' + escapeHtml(field.id) + (isMulti ? '[]' : '') + '" value="' + index + '" data-foundation-option data-field-id="' + escapeHtml(field.id) + '"' + checked + '><span class="foundation-option-check" aria-hidden="true"></span><span class="foundation-option-copy"><strong>' + escapeHtml(option.label || 'Option') + '</strong>' + (hint ? '<small>' + escapeHtml(hint) + '</small>' : '') + '</span></label>';
			}).join('');
			return '<fieldset class="foundation-field foundation-card-field" data-foundation-field="' + escapeHtml(field.id) + '">' + header + '<div class="foundation-option-grid' + (field.options && field.options.length > 5 ? ' is-compact' : '') + '">' + options + '</div></fieldset>';
		}

		if (field.type === 'toggle') {
			var toggleSelected = getSelectedIndexes(field.id);
			var yesChecked = toggleSelected.indexOf('0') !== -1 ? ' checked' : '';
			var noChecked = toggleSelected.indexOf('1') !== -1 ? ' checked' : '';
			return '<fieldset class="foundation-field" data-foundation-field="' + escapeHtml(field.id) + '">' + header + '<div class="foundation-toggle-group"><label class="foundation-toggle-choice' + (yesChecked ? ' is-selected' : '') + '"><input type="radio" name="' + escapeHtml(field.id) + '" value="0" data-foundation-option data-field-id="' + escapeHtml(field.id) + '"' + yesChecked + '><span>' + escapeHtml(field.yes_label || 'Yes') + '</span></label><label class="foundation-toggle-choice' + (noChecked ? ' is-selected' : '') + '"><input type="radio" name="' + escapeHtml(field.id) + '" value="1" data-foundation-option data-field-id="' + escapeHtml(field.id) + '"' + noChecked + '><span>' + escapeHtml(field.no_label || 'No') + '</span></label></div></fieldset>';
		}

		if (field.type === 'number_input' || field.type === 'range_slider') {
			var numericValue = getNumericSelection(field.id);
			var min = field.min != null ? Number(field.min) : 0;
			var max = field.max != null ? Number(field.max) : 1000;
			var step = field.step != null ? Number(field.step) : 1;
			var inputType = field.type === 'range_slider' ? 'range' : 'number';
			var valueAttr = numericValue !== '' ? ' value="' + escapeHtml(numericValue) + '"' : (field.type === 'range_slider' ? ' value="' + min + '"' : '');
			var described = field.helper ? ' aria-describedby="' + escapeHtml(field.id) + '-help"' : '';
			return '<fieldset class="foundation-field foundation-number-field" data-foundation-field="' + escapeHtml(field.id) + '">' + header + '<div class="foundation-number-input"><input id="' + escapeHtml(field.id) + '" type="' + inputType + '" inputmode="decimal" min="' + min + '" max="' + max + '" step="' + step + '" data-foundation-value data-field-id="' + escapeHtml(field.id) + '"' + valueAttr + described + '><span>' + escapeHtml(field.unit || 'units') + '</span></div>' + (field.type === 'range_slider' ? '<output data-foundation-range-output>' + escapeHtml(numericValue !== '' ? numericValue : min) + '</output>' : '') + '</fieldset>';
		}

		if (field.type === 'text_input') {
			var value = state.selections[field.id] || '';
			var inputTypeText = /url|website/i.test(field.id + ' ' + field.label) ? 'url' : 'text';
			var inputMaxLength = inputTypeText === 'url' ? 500 : 5000;
			return '<div class="foundation-field" data-foundation-field="' + escapeHtml(field.id) + '"><div class="foundation-field-heading"><label for="' + escapeHtml(field.id) + '">' + escapeHtml(field.label || '') + '</label>' + required + '</div>' + helper + '<input id="' + escapeHtml(field.id) + '" type="' + inputTypeText + '" maxlength="' + inputMaxLength + '" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(field.placeholder || '') + '" data-foundation-value data-field-id="' + escapeHtml(field.id) + '"></div>';
		}

		if (field.type === 'rich_text') {
			var textValue = state.selections[field.id] || '';
			return '<div class="foundation-field" data-foundation-field="' + escapeHtml(field.id) + '"><div class="foundation-field-heading"><label for="' + escapeHtml(field.id) + '">' + escapeHtml(field.label || '') + '</label>' + required + '</div>' + helper + '<textarea id="' + escapeHtml(field.id) + '" rows="5" maxlength="5000" placeholder="' + escapeHtml(field.placeholder || '') + '" data-foundation-value data-field-id="' + escapeHtml(field.id) + '">' + escapeHtml(textValue) + '</textarea></div>';
		}

		if (field.type === 'file_upload') {
			var names = state.files[field.id] && state.files[field.id].length ? state.files[field.id].map(function (file) { return file.name; }).join(', ') : 'No files selected';
			var configuredAccept = String(field.accept || '').trim();
			var allowedAccept = Array.isArray(uploadRules.allowedTypes) ? uploadRules.allowedTypes.map(function (extension) { return '.' + String(extension).replace(/^\./, ''); }).join(',') : '';
			var acceptAttribute = configuredAccept || allowedAccept;
			return '<div class="foundation-field" data-foundation-field="' + escapeHtml(field.id) + '"><div class="foundation-field-heading"><label for="' + escapeHtml(field.id) + '">' + escapeHtml(field.label || '') + '</label>' + required + '</div>' + helper + '<input id="' + escapeHtml(field.id) + '" type="file" multiple' + (acceptAttribute ? ' accept="' + escapeHtml(acceptAttribute) + '"' : '') + ' data-foundation-file data-field-id="' + escapeHtml(field.id) + '"><p class="foundation-file-summary" data-foundation-file-summary>' + escapeHtml(names) + '</p></div>';
		}
		return '';
	}

	function getOptionHint(option) {
		if (!option) return '';
		if (option.pricing_type === 'manual') return 'Tailored quote';
		if (option.price_key && pricingCatalog[option.price_key] != null) return formatMoney(pricingCatalog[option.price_key]) + (option.billing === 'monthly' ? ' / month' : ' one-off');
		if (option.unit_price_key && pricingCatalog[option.unit_price_key] != null && option.quantity_min != null) {
			var min = Number(pricingCatalog[option.unit_price_key]) * Number(option.quantity_min || 0);
			var max = Number(pricingCatalog[option.unit_price_key]) * Number(option.quantity_max != null ? option.quantity_max : option.quantity_min || 0);
			return formatRange(min, max) + (option.billing === 'monthly' ? ' / month' : ' one-off');
		}
		return '';
	}

	function bindStepInputs(step) {
		getCanvas().querySelectorAll('[data-foundation-option]').forEach(function (input) {
			input.addEventListener('change', function () {
				var field = findField(input.getAttribute('data-field-id'));
				if (!field) return;
				var key = field.id + '_options';
				if (field.type === 'service_card' && field.selection_mode === 'multi') {
					var selected = getSelectedIndexes(field.id);
					var value = String(input.value);
					if (input.checked && selected.indexOf(value) === -1) selected.push(value);
					if (!input.checked) selected = selected.filter(function (item) { return item !== value; });
					state.selections[key] = selected;
				} else {
					state.selections[key] = input.checked ? [String(input.value)] : [];
				}
				state.routeIds = computeRouteIds();
				refreshSelectionStyles(input.closest('[data-foundation-field]'));
				clearFieldError(field.id);
				updateLiveSummary();
			});
		});
		getCanvas().querySelectorAll('[data-foundation-value]').forEach(function (input) {
			var eventName = input.tagName === 'TEXTAREA' ? 'input' : 'input';
			input.addEventListener(eventName, function () {
				var fieldId = input.getAttribute('data-field-id');
				var field = findField(fieldId);
				if (!field) return;
				if (field.type === 'number_input' || field.type === 'range_slider') {
					state.selections[fieldId + '_val'] = input.value;
					var output = input.closest('[data-foundation-field]') && input.closest('[data-foundation-field]').querySelector('[data-foundation-range-output]');
					if (output) output.textContent = input.value;
				} else state.selections[fieldId] = input.value;
				clearFieldError(fieldId);
				updateLiveSummary();
			});
		});
		getCanvas().querySelectorAll('[data-foundation-file]').forEach(function (input) {
			input.addEventListener('change', function () { handleFiles(input); });
		});
	}

	function refreshSelectionStyles(fieldRoot) {
		if (!fieldRoot) return;
		fieldRoot.querySelectorAll('.foundation-option-card, .foundation-toggle-choice').forEach(function (label) {
			var input = label.querySelector('input');
			label.classList.toggle('is-selected', Boolean(input && input.checked));
		});
	}

	function handleFiles(input) {
		var fieldId = input.getAttribute('data-field-id');
		var field = findField(fieldId) || {};
		var files = Array.prototype.slice.call(input.files || []);
		var maxFiles = Number(field.max_files || uploadRules.maxFilesPerField || 5);
		var maxFileMb = Number(field.max_file_size_mb || uploadRules.maxFileSizeMb || 10);
		var allowed = Array.isArray(uploadRules.allowedTypes) ? uploadRules.allowedTypes.map(function (item) { return String(item).toLowerCase(); }) : [];
		var totalBytes = 0;
		if (files.length > maxFiles) return rejectFiles(input, fieldId, 'Choose no more than ' + maxFiles + ' files.');
		for (var i = 0; i < files.length; i += 1) {
			var file = files[i];
			var extension = String(file.name.split('.').pop() || '').toLowerCase();
			if (allowed.length && allowed.indexOf(extension) === -1) return rejectFiles(input, fieldId, file.name + ' is not an allowed file type.');
			if (file.size > maxFileMb * 1024 * 1024) return rejectFiles(input, fieldId, file.name + ' is larger than ' + maxFileMb + 'MB.');
			totalBytes += file.size;
		}
		if (totalBytes > Number(uploadRules.maxTotalSizeMb || 25) * 1024 * 1024) return rejectFiles(input, fieldId, 'The selected files exceed the total upload limit.');
		state.files[fieldId] = files;
		state.selections[fieldId] = files.map(function (file) { return file.name; }).join(', ');
		var summary = input.parentNode.querySelector('[data-foundation-file-summary]');
		if (summary) summary.textContent = files.length ? files.map(function (file) { return file.name; }).join(', ') : 'No files selected';
		clearFieldError(fieldId);
	}

	function rejectFiles(input, fieldId, message) {
		input.value = '';
		state.files[fieldId] = [];
		state.selections[fieldId] = '';
		showMessage(message);
		input.focus();
	}

	function goNext() {
		clearMessage();
		if (state.view === 'step') {
			if (!validateStep(state.currentStepIndex)) return;
			state.routeIds = computeRouteIds();
			var visible = getVisibleStepIndexes();
			var position = visible.indexOf(state.currentStepIndex);
			if (position >= 0 && position < visible.length - 1) {
				state.currentStepIndex = visible[position + 1];
				renderCurrentView();
				focusViewHeading();
			} else {
				state.view = 'review';
				renderCurrentView();
				focusViewHeading();
			}
		} else if (state.view === 'review') {
			state.view = 'contact';
			renderCurrentView();
			focusViewHeading();
		}
	}

	function goBack() {
		clearMessage();
		if (state.view === 'contact') {
			captureContactValues();
			state.view = 'review';
			renderCurrentView();
			focusViewHeading();
			return;
		}
		if (state.view === 'review') {
			var reviewVisible = getVisibleStepIndexes();
			if (reviewVisible.length) {
				state.view = 'step';
				state.currentStepIndex = reviewVisible[reviewVisible.length - 1];
				renderCurrentView();
				focusViewHeading();
			}
			return;
		}
		if (state.view === 'step') {
			var visible = getVisibleStepIndexes();
			var position = visible.indexOf(state.currentStepIndex);
			if (position > 0) {
				state.currentStepIndex = visible[position - 1];
				renderCurrentView();
				focusViewHeading();
			} else {
				state.view = 'intro';
				state.currentStepIndex = -1;
				renderCurrentView();
				focusViewHeading();
			}
		}
	}

	function validateStep(index) {
		var step = steps[index];
		if (!step) return true;
		for (var i = 0; i < step.fields.length; i += 1) {
			var field = step.fields[i];
			if (!field.required || ['calculation', 'section_title', 'description', 'divider'].indexOf(field.type) !== -1) continue;
			var valid = true;
			if (field.type === 'service_card' || field.type === 'toggle') valid = getSelectedIndexes(field.id).length > 0;
			else if (field.type === 'number_input' || field.type === 'range_slider') {
				var raw = getNumericSelection(field.id);
				var number = Number(raw);
				var min = Number(field.min != null ? field.min : 0);
				var max = Number(field.max != null ? field.max : Number.MAX_SAFE_INTEGER);
				var stepValue = Math.max(0.01, Number(field.step != null ? field.step : 1));
				var onStep = Number.isFinite(number) && Math.abs(((number - min) / stepValue) - Math.round((number - min) / stepValue)) <= 0.00001;
				valid = raw !== '' && Number.isFinite(number) && number >= min && number <= max && onStep;
			} else if (field.type === 'file_upload') valid = Boolean(state.files[field.id] && state.files[field.id].length);
			else valid = String(state.selections[field.id] || '').trim() !== '';
			if (!valid) {
				markFieldError(field.id, 'Please complete “' + (field.label || 'this question') + '” before continuing.');
				return false;
			}
		}
		return true;
	}

	function markFieldError(fieldId, message) {
		showMessage(message);
		var root = getCanvas().querySelector('[data-foundation-field="' + cssEscape(fieldId) + '"]');
		if (root) {
			root.classList.add('has-error');
			var target = root.querySelector('input, textarea, select, button');
			if (target) {
				target.setAttribute('aria-invalid', 'true');
				target.focus();
			}
		}
	}

	function clearFieldError(fieldId) {
		var root = getCanvas().querySelector('[data-foundation-field="' + cssEscape(fieldId) + '"]');
		if (root) {
			root.classList.remove('has-error');
			root.querySelectorAll('[aria-invalid="true"]').forEach(function (node) { node.setAttribute('aria-invalid', 'false'); });
		}
		clearMessage();
	}

	function cssEscape(value) {
		if (window.CSS && window.CSS.escape) return window.CSS.escape(String(value));
		return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
	}

	function renderReview() {
		state.routeIds = computeRouteIds();
		var quote = calculateQuote();
		setProgress(true, getVisibleStepIndexes().length, getVisibleStepIndexes().length + 2, 'Review');
		setFooter(true, true, 'Continue to your details');
		var totals = renderQuoteTotals(quote, true);
		var lines = quote.line_items.length ? '<div class="foundation-review-list">' + quote.line_items.map(function (item) {
			return '<div><span>' + escapeHtml(item.label) + '</span><strong>' + escapeHtml(formatRange(item.min, item.max)) + (item.billing === 'monthly' ? ' / month' : '') + '</strong></div>';
		}).join('') + '</div>' : '';
		var manual = quote.manual_items.length ? '<section class="foundation-manual-review"><h2>Items needing a tailored quote</h2>' + quote.manual_items.map(function (item) { return '<div><strong>' + escapeHtml(item.label) + '</strong><p>' + escapeHtml(item.note) + '</p></div>'; }).join('') + '</section>' : '';
		var empty = !quote.has_pricing && !quote.manual_items.length ? '<p class="foundation-empty-state">Choose at least one priced service or tailored-quote item before continuing.</p>' : '';
		getCanvas().innerHTML = '<section class="foundation-review"><p class="foundation-kicker">Nearly there</p><h1 tabindex="-1" data-foundation-view-heading>Your estimated quote</h1><p class="foundation-step-intro">Here is the planning estimate based on your answers. One-off work and ongoing support are kept separate.</p>' + totals + lines + manual + empty + '<div class="foundation-estimate-note"><strong>Before you send it</strong><p>' + escapeHtml(branding.vatNote || '') + '</p><p>' + escapeHtml(branding.estimateDisclaimer || '') + '</p></div></section>';
	}

	function renderQuoteTotals(quote, prominent) {
		if (quoteMode) return '<div class="foundation-quote-mode-note"><strong>Tailored quotation</strong><span>Inkfire will review your selections and prepare the right quote.</span></div>';
		var cards = '';
		if (quote.one_off_max > 0) cards += '<div class="foundation-total-card"><span>One-off estimate</span><strong>' + escapeHtml(formatRange(quote.one_off_min, quote.one_off_max)) + '</strong><small>excluding VAT</small></div>';
		if (quote.monthly_max > 0) cards += '<div class="foundation-total-card"><span>Monthly estimate</span><strong>' + escapeHtml(formatRange(quote.monthly_min, quote.monthly_max)) + '</strong><small>per month, excluding VAT</small></div>';
		if (!cards) cards = '<div class="foundation-total-card is-empty"><span>Calculated estimate</span><strong>Tailored quote</strong><small>We need to confirm the scope</small></div>';
		return '<div class="foundation-total-grid' + (prominent ? ' is-prominent' : '') + '">' + cards + '</div>';
	}

	function renderContact() {
		setProgress(true, getVisibleStepIndexes().length + 1, getVisibleStepIndexes().length + 2, 'Your details');
		setFooter(false);
		var requiredPhone = Boolean(branding.phoneRequired);
		var privacyText = branding.privacyConsentLabel || 'I agree that Inkfire may use these details to respond to my enquiry.';
		var privacyLink = branding.privacyPolicyUrl ? ' <a href="' + escapeHtml(branding.privacyPolicyUrl) + '" target="_blank" rel="noopener noreferrer">Read the privacy policy</a>.' : '';
		getCanvas().innerHTML = '<section class="foundation-contact"><p class="foundation-kicker">Send your estimate</p><h1 tabindex="-1" data-foundation-view-heading>Where should we send it?</h1><p class="foundation-step-intro">We will email your estimate and use your answers to prepare for the next conversation.</p><form data-foundation-contact-form novalidate><div class="foundation-contact-grid"><label><span>Full name <b>Required</b></span><input type="text" name="name" autocomplete="name" minlength="2" maxlength="120" value="' + escapeHtml(state.contact.name) + '" required></label><label><span>Business name <b>Required</b></span><input type="text" name="company" autocomplete="organization" minlength="2" maxlength="160" value="' + escapeHtml(state.contact.company) + '" required></label><label><span>Email address <b>Required</b></span><input type="email" name="email" autocomplete="email" inputmode="email" maxlength="190" value="' + escapeHtml(state.contact.email) + '" required></label><label><span>Phone number' + (requiredPhone ? ' <b>Required</b>' : ' <em>Optional</em>') + '</span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" minlength="' + (requiredPhone ? '3' : '0') + '" maxlength="60" value="' + escapeHtml(state.contact.phone) + '"' + (requiredPhone ? ' required' : '') + '></label><label class="foundation-contact-full"><span>Website <em>Optional</em></span><input type="url" name="website" autocomplete="url" maxlength="500" placeholder="https://" value="' + escapeHtml(state.contact.website) + '"></label><label class="foundation-contact-full"><span>Anything else we should know? <em>Optional</em></span><textarea name="notes" rows="4" maxlength="5000" placeholder="Deadlines, access needs, context or anything that will help us understand the project.">' + escapeHtml(state.contact.notes) + '</textarea></label></div><label class="foundation-privacy-check"><input type="checkbox" name="privacy" value="1"' + (state.contact.privacy ? ' checked' : '') + ' required><span>' + escapeHtml(privacyText) + privacyLink + '</span></label><div class="foundation-honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="foundation_honey" tabindex="-1" autocomplete="off"></label></div><div class="foundation-contact-actions"><button type="button" class="foundation-secondary-button" data-contact-back>Back to estimate</button><button type="submit" class="foundation-primary-button" data-contact-submit>Email my estimate</button></div><p class="foundation-submit-status" role="status" aria-live="polite" hidden></p></form></section>';
		var form = getCanvas().querySelector('[data-foundation-contact-form]');
		form.addEventListener('input', captureContactValues);
		form.addEventListener('change', captureContactValues);
		form.addEventListener('submit', submitContactForm);
		form.querySelector('[data-contact-back]').addEventListener('click', goBack);
	}

	function captureContactValues() {
		var form = getCanvas().querySelector('[data-foundation-contact-form]');
		if (!form) return;
		state.contact.name = String(form.elements.name.value || '').trim();
		state.contact.company = String(form.elements.company.value || '').trim();
		state.contact.email = String(form.elements.email.value || '').trim();
		state.contact.phone = String(form.elements.phone.value || '').trim();
		state.contact.website = String(form.elements.website.value || '').trim();
		state.contact.notes = String(form.elements.notes.value || '').trim();
		state.contact.privacy = Boolean(form.elements.privacy.checked);
	}

	function validateContactForm(form) {
		captureContactValues();
		Array.prototype.slice.call(form.querySelectorAll('[aria-invalid]')).forEach(function (input) { input.removeAttribute('aria-invalid'); });
		var fields = [
			{ name: 'name', label: 'full name', minLength: 2 },
			{ name: 'company', label: 'business name', minLength: 2 },
			{ name: 'email', label: 'email address', minLength: 1 }
		];
		if (branding.phoneRequired) fields.push({ name: 'phone', label: 'phone number', minLength: 3 });
		for (var i = 0; i < fields.length; i += 1) {
			var item = fields[i];
			var input = form.elements[item.name];
			if (!input || String(input.value || '').trim().length < item.minLength) {
				showMessage('Please enter your ' + item.label + '.');
				if (input) { input.setAttribute('aria-invalid', 'true'); input.focus(); }
				return false;
			}
		}
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.contact.email)) {
			showMessage('Please enter a valid email address.');
			form.elements.email.setAttribute('aria-invalid', 'true');
			form.elements.email.focus();
			return false;
		}
		if (state.contact.website && !form.elements.website.checkValidity()) {
			showMessage('Please enter the website address in a format such as https://example.com.');
			form.elements.website.setAttribute('aria-invalid', 'true');
			form.elements.website.focus();
			return false;
		}
		if (!form.elements.privacy.checked) {
			showMessage('Please confirm the privacy statement so we can respond to your enquiry.');
			form.elements.privacy.setAttribute('aria-invalid', 'true');
			form.elements.privacy.focus();
			return false;
		}
		return true;
	}

	function submitContactForm(event) {
		event.preventDefault();
		var form = event.currentTarget;
		if (!validateContactForm(form)) return;
		var button = form.querySelector('[data-contact-submit]');
		var status = form.querySelector('.foundation-submit-status');
		button.disabled = true;
		button.textContent = 'Sending securely…';
		status.hidden = false;
		status.textContent = 'Checking your estimate and sending your details.';

		var body = new FormData();
		body.append('action', 'foundation_submit_quote');
		body.append('nonce', config.nonce || '');
		body.append('submission_id', state.submissionId);
		body.append('foundation_honey', form.elements.foundation_honey.value || '');
		Object.keys(state.contact).forEach(function (key) {
			body.append('contact[' + key + ']', key === 'privacy' ? (state.contact[key] ? '1' : '0') : String(state.contact[key] || ''));
		});
		appendSelections(body);
		appendFiles(body);

		fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (response) { return response.json().then(function (payload) { return { response: response, payload: payload }; }); })
			.then(function (result) {
				if (!result.response.ok || !result.payload || !result.payload.success) {
					var message = result.payload && result.payload.data && result.payload.data.message ? result.payload.data.message : 'We could not send your estimate. Please try again.';
					throw new Error(message);
				}
				state.completed = true;
				state.serverQuote = result.payload.data.quote || calculateQuote();
				state.customerEmailStatus = result.payload.data.customer_email_status || 'unknown';
				state.adminEmailStatus = result.payload.data.admin_email_status || 'unknown';
				state.serverMessage = result.payload.data.message || '';
				state.reference = result.payload.data.reference || '';
				state.resumeToken = '';
				clearResumeTokenFromUrl();
				state.view = 'success';
				renderCurrentView();
				focusViewHeading();
			})
			.catch(function (error) {
				button.disabled = false;
				button.textContent = 'Email my estimate';
				status.hidden = true;
				showMessage(error && error.message ? error.message : 'We could not send your estimate. Please try again.');
				trackEvent('failure', error && error.message ? error.message : 'Submission failed');
			});
	}

	function appendSelections(body) {
		Object.keys(state.selections).forEach(function (key) {
			var value = state.selections[key];
			if (Array.isArray(value)) value.forEach(function (item) { body.append('selections[' + key + '][]', String(item)); });
			else body.append('selections[' + key + ']', String(value == null ? '' : value));
		});
	}

	function appendFiles(body) {
		Object.keys(state.files).forEach(function (fieldId) {
			(state.files[fieldId] || []).forEach(function (file) { body.append('uploads[' + fieldId + '][]', file, file.name); });
		});
	}

	function renderSuccess(quote) {
		setProgress(false);
		setFooter(false);
		var primaryMessage = state.serverMessage || branding.successMessage || 'Your enquiry has been received safely.';
		var confirmationMessage = '';
		if (state.customerEmailStatus === 'sent') confirmationMessage = 'A copy of the estimate has been emailed to you.';
		else if (state.customerEmailStatus === 'failed') confirmationMessage = 'Your enquiry is safely stored, but the confirmation email could not be sent. Please keep the reference below.';
		else if (state.customerEmailStatus === 'disabled') confirmationMessage = 'Email confirmations are currently switched off. Please keep the reference below.';
		var reference = state.reference ? '<p class="foundation-success-reference"><span>Your reference</span><strong>' + escapeHtml(state.reference) + '</strong></p>' : '';
		var confirmation = confirmationMessage ? '<p class="foundation-success-email-note">' + escapeHtml(confirmationMessage) + '</p>' : '';
		getCanvas().innerHTML = '<section class="foundation-success"><div class="foundation-success-mark" aria-hidden="true">✓</div><p class="foundation-kicker">Safely received</p><h1 tabindex="-1" data-foundation-view-heading>Thank you, ' + escapeHtml(state.contact.name || 'there') + '</h1><p>' + escapeHtml(primaryMessage) + '</p>' + reference + confirmation + renderQuoteTotals(quote, false) + '<button type="button" class="foundation-primary-button" data-success-close>Close calculator</button></section>';
		getCanvas().querySelector('[data-success-close]').addEventListener('click', closeCalculator);
	}

	function setFooter(show, showBack, nextLabel) {
		var footer = getFooter();
		footer.hidden = !show;
		if (!show) return;
		getBackButton().hidden = !showBack;
		getNextButton().textContent = nextLabel || 'Continue';
	}

	function setProgress(show, position, total, label) {
		var wrap = overlay.querySelector('.foundation-progress-wrap');
		wrap.hidden = !show;
		if (!show) return;
		var safeTotal = Math.max(1, Number(total || 1));
		var current = Math.min(safeTotal, Math.max(1, Number(position || 0) + 1));
		overlay.querySelector('[data-foundation-progress-label]').textContent = label || 'Project estimate';
		overlay.querySelector('[data-foundation-progress-count]').textContent = current + ' of ' + safeTotal;
		overlay.querySelector('[data-foundation-progress-bar]').style.width = Math.round((current / safeTotal) * 100) + '%';
	}

	function showMessage(message) {
		var node = overlay.querySelector('.foundation-message');
		if (!node) return;
		node.textContent = message;
		node.hidden = false;
		node.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'nearest' });
	}

	function clearMessage() {
		var node = overlay.querySelector('.foundation-message');
		if (!node) return;
		node.hidden = true;
		node.textContent = '';
	}

	function focusViewHeading() {
		window.setTimeout(function () {
			var heading = getCanvas().querySelector('[data-foundation-view-heading]');
			if (heading) heading.focus();
		}, 10);
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	function getSelectedIndexes(fieldId) {
		var value = state.selections[fieldId + '_options'];
		return Array.isArray(value) ? value.map(String) : [];
	}

	function getNumericSelection(fieldId) {
		var value = state.selections[fieldId + '_val'];
		if (value == null) value = state.selections[fieldId];
		return value == null ? '' : String(value);
	}

	function findField(fieldId) {
		for (var i = 0; i < steps.length; i += 1) {
			for (var j = 0; j < steps[i].fields.length; j += 1) if (steps[i].fields[j].id === fieldId) return steps[i].fields[j];
		}
		return null;
	}

	function getSelectedOptions(field) {
		var indexes = getSelectedIndexes(field.id);
		return (field.options || []).filter(function (option, index) { return indexes.indexOf(String(index)) !== -1; });
	}

	function computeRouteIds() {
		var routes = new Set();
		var changed = true;
		var passes = 0;
		while (changed && passes < 20) {
			changed = false;
			passes += 1;
			steps.forEach(function (step) {
				if (step.is_conditional && !routes.has(step.id)) return;
				(step.fields || []).forEach(function (field) {
					if (field.type === 'service_card') {
						getSelectedOptions(field).forEach(function (option) {
							var targets = Array.isArray(option.route_step_ids) ? option.route_step_ids : (option.route_step_id ? [option.route_step_id] : []);
							targets.forEach(function (target) { if (target && !routes.has(target)) { routes.add(target); changed = true; } });
						});
					}
					if (field.type === 'toggle' && getSelectedIndexes(field.id).indexOf('0') !== -1) {
						(Array.isArray(field.yes_route_step_ids) ? field.yes_route_step_ids : []).forEach(function (target) { if (target && !routes.has(target)) { routes.add(target); changed = true; } });
					}
				});
			});
		}
		return routes;
	}

	function getVisibleStepIndexes() {
		state.routeIds = computeRouteIds();
		var indexes = [];
		steps.forEach(function (step, index) { if (!step.is_conditional || state.routeIds.has(step.id)) indexes.push(index); });
		return indexes;
	}

	function getVisibleSteps() {
		var indexes = getVisibleStepIndexes();
		return indexes.map(function (index) { return steps[index]; });
	}

	function resolvePrice(source, keyName, literalName) {
		keyName = keyName || 'price_key';
		literalName = literalName || 'price';
		var key = source && source[keyName] ? String(source[keyName]) : '';
		if (key && Object.prototype.hasOwnProperty.call(pricingCatalog, key)) return roundMoney(pricingCatalog[key]);
		return roundMoney(source && source[literalName] != null ? source[literalName] : 0);
	}

	function resolveUnitPrice(source) {
		var keys = [];
		if (source && source.unit_price_key) keys.push(source.unit_price_key);
		if (source && source.price_per_unit_key) keys.push(source.price_per_unit_key);
		if (source && Array.isArray(source.price_per_unit_keys)) keys = keys.concat(source.price_per_unit_keys);
		var total = keys.reduce(function (sum, key) { return sum + (pricingCatalog[key] != null ? Number(pricingCatalog[key]) : 0); }, 0);
		if (!total && source && source.price_per_unit != null) total = Number(source.price_per_unit);
		return roundMoney(total);
	}

	function resolveBilling(source) {
		var billing = source && source.billing === 'monthly' ? 'monthly' : 'one_off';
		if (source && source.billing_from_field) {
			var option = findSelectedOption(source.billing_from_field);
			if (option && option.billing_override) billing = option.billing_override === 'monthly' ? 'monthly' : 'one_off';
		}
		return billing;
	}

	function findSelectedOption(fieldId) {
		var field = findField(fieldId);
		if (!field) return null;
		var selected = getSelectedOptions(field);
		return selected.length ? selected[0] : null;
	}

	function calculateQuote() {
		var quote = { one_off_min: 0, one_off_max: 0, monthly_min: 0, monthly_max: 0, line_items: [], manual_items: [], currency: currency, vat_note: branding.vatNote || '' };
		getVisibleSteps().forEach(function (step) {
			(step.fields || []).forEach(function (field) {
				var type = field.type || '';
				var label = field.line_item_label || field.label || 'Service';
				var pricingType = field.pricing_type || 'fixed';
				var billing = resolveBilling(field);
				var manualNote = field.manual_note || 'A tailored quote is required.';

				if (type === 'calculation') {
					if (pricingType === 'manual') { addManual(quote, label, manualNote); return; }
					if (field.unit_source_field_id && field.quantity_source_field_id) {
						var unitOption = findSelectedOption(field.unit_source_field_id);
						var qtyOption = findSelectedOption(field.quantity_source_field_id);
						if (!unitOption || !qtyOption) return;
						if (unitOption.pricing_type === 'manual' || qtyOption.pricing_type === 'manual') {
							addManual(quote, label, unitOption.manual_note || qtyOption.manual_note || manualNote);
							return;
						}
						var unit = resolveUnitPrice(unitOption);
						var qtyMin = Math.max(0, Number(qtyOption.quantity_min || 0));
						var qtyMax = Math.max(qtyMin, Number(qtyOption.quantity_max != null ? qtyOption.quantity_max : qtyMin));
						addLine(quote, label, unit * qtyMin, unit * qtyMax, billing, { unit_price: unit, quantity_min: qtyMin, quantity_max: qtyMax });
					}
					return;
				}

				if (type === 'service_card') {
					getSelectedOptions(field).forEach(function (option) {
						var optionLabel = option.line_item_label || option.label || label;
						if (option.pricing_type === 'manual') { addManual(quote, optionLabel, option.manual_note || manualNote); return; }
						if (option.pricing_component || field.pricing_component) return;
						var optionBilling = option.billing === 'monthly' ? 'monthly' : billing;
						var unit = resolveUnitPrice(option);
						if (unit > 0 && option.quantity_min != null) {
							var minQty = Math.max(0, Number(option.quantity_min));
							var maxQty = Math.max(minQty, Number(option.quantity_max != null ? option.quantity_max : minQty));
							addLine(quote, optionLabel, unit * minQty, unit * maxQty, optionBilling, { unit_price: unit, quantity_min: minQty, quantity_max: maxQty });
						} else {
							var price = resolvePrice(option);
							addLine(quote, optionLabel, price, price, optionBilling);
						}
					});
					return;
				}

				if (type === 'toggle') {
					var yes = getSelectedIndexes(field.id).indexOf('0') !== -1;
					if (!yes) return;
					if (pricingType === 'manual' || pricingType === 'manual_when_yes') { addManual(quote, label, manualNote); return; }
					var price = resolvePrice(field);
					addLine(quote, label, price, price, billing);
					return;
				}

				if (type === 'number_input' || type === 'range_slider') {
					var raw = getNumericSelection(field.id);
					if (raw === '' || !Number.isFinite(Number(raw))) return;
					var minValue = Number.isFinite(Number(field.min)) ? Number(field.min) : 0;
					var maxValue = Number.isFinite(Number(field.max)) ? Math.max(minValue, Number(field.max)) : Math.max(minValue, Number(raw));
					var quantity = Math.min(maxValue, Math.max(minValue, Number(raw)));
					var base = resolvePrice(field, 'base_price_key', 'base_price');
					var perUnit = resolveUnitPrice(field);
					addLine(quote, label, base + perUnit * quantity, base + perUnit * quantity, billing, { unit_price: perUnit, quantity_min: quantity, quantity_max: quantity, base_price: base });
				}
			});
		});
		['one_off_min', 'one_off_max', 'monthly_min', 'monthly_max'].forEach(function (key) { quote[key] = roundMoney(quote[key]); });
		quote.has_range = quote.one_off_min !== quote.one_off_max || quote.monthly_min !== quote.monthly_max;
		quote.manual_count = quote.manual_items.length;
		quote.has_pricing = quote.one_off_max > 0 || quote.monthly_max > 0;
		return quote;
	}

	function addLine(quote, label, min, max, billing, meta) {
		min = roundMoney(Math.max(0, Number(min || 0)));
		max = roundMoney(Math.max(min, Number(max || 0)));
		billing = billing === 'monthly' ? 'monthly' : 'one_off';
		if (min <= 0 && max <= 0) return;
		quote[billing + '_min'] += min;
		quote[billing + '_max'] += max;
		quote.line_items.push(Object.assign({ label: String(label || 'Service'), min: min, max: max, billing: billing }, meta || {}));
	}

	function addManual(quote, label, note) {
		var key = String(label || '').toLowerCase() + '|' + String(note || '').toLowerCase();
		var exists = quote.manual_items.some(function (item) { return (String(item.label).toLowerCase() + '|' + String(item.note).toLowerCase()) === key; });
		if (!exists) quote.manual_items.push({ label: String(label || 'Service'), note: String(note || 'A tailored quote is required.') });
	}

	function roundMoney(value) { return Math.round((Number(value) + Number.EPSILON) * 100) / 100; }
	function formatMoney(value) {
		var number = Number(value || 0);
		return currency + number.toLocaleString(undefined, { minimumFractionDigits: number % 1 ? 2 : 0, maximumFractionDigits: 2 });
	}
	function formatRange(min, max) {
		min = Number(min || 0); max = Number(max || min);
		return min === max ? formatMoney(min) : formatMoney(min) + ' to ' + formatMoney(max);
	}

	function updateLiveSummary() {
		var aside = overlay.querySelector('[data-foundation-live-summary]');
		var body = overlay.querySelector('.foundation-modal-body');
		if (!aside) return;
		var shouldHide = !branding.showLiveSummary || state.view === 'intro' || state.view === 'success';
		if (body) body.classList.toggle('is-summary-hidden', shouldHide);
		if (shouldHide) {
			aside.hidden = true;
			aside.innerHTML = '';
			return;
		}
		aside.hidden = false;
		var quote = calculateQuote();
		var totals = quoteMode ? '<div class="foundation-live-tailored"><strong>Tailored quote</strong><span>We will review your selections.</span></div>' : '';
		if (!quoteMode && quote.one_off_max > 0) totals += '<div class="foundation-live-total"><span>One-off</span><strong>' + escapeHtml(formatRange(quote.one_off_min, quote.one_off_max)) + '</strong></div>';
		if (!quoteMode && quote.monthly_max > 0) totals += '<div class="foundation-live-total"><span>Monthly</span><strong>' + escapeHtml(formatRange(quote.monthly_min, quote.monthly_max)) + '</strong></div>';
		if (!totals) totals = '<p class="foundation-live-empty">Your estimate will appear here as you answer.</p>';
		var list = quote.line_items.slice(-5).map(function (item) { return '<li><span>' + escapeHtml(item.label) + '</span><strong>' + escapeHtml(formatRange(item.min, item.max)) + (item.billing === 'monthly' ? '/mo' : '') + '</strong></li>'; }).join('');
		var manual = quote.manual_items.length ? '<div class="foundation-live-manual"><strong>' + quote.manual_items.length + ' tailored item' + (quote.manual_items.length === 1 ? '' : 's') + '</strong><span>Inkfire will scope these with you.</span></div>' : '';
		aside.innerHTML = '<div class="foundation-live-heading"><span>Live estimate</span><small>Excluding VAT</small></div>' + totals + (list ? '<ul>' + list + '</ul>' : '') + manual + '<p class="foundation-live-note">' + escapeHtml(branding.estimateDisclaimer || '') + '</p>';
	}

	function openSavePanel() {
		if (overlay.querySelector('.foundation-save-panel')) return;
		state.savePanelReturnFocus = document.activeElement;
		var panel = document.createElement('div');
		panel.className = 'foundation-save-panel';
		panel.setAttribute('role', 'dialog');
		panel.setAttribute('aria-modal', 'true');
		panel.setAttribute('aria-labelledby', 'foundation-save-title');
		panel.innerHTML = '<div class="foundation-save-dialog"><button type="button" class="foundation-save-close" data-save-close aria-label="Close save panel">×</button><p class="foundation-kicker">Pause here</p><h2 id="foundation-save-title">Save and resume later</h2><p>Enter an email address and we will send a private resume link. Uploaded files cannot be stored and will need adding again.</p><label><span>Name <em>Optional</em></span><input type="text" name="save_name" autocomplete="name" maxlength="120" value="' + escapeHtml(state.contact.name) + '"></label><label><span>Email address <b>Required</b></span><input type="email" name="save_email" autocomplete="email" inputmode="email" maxlength="190" value="' + escapeHtml(state.contact.email) + '" required></label><div class="foundation-save-actions"><button type="button" class="foundation-secondary-button" data-save-close>Cancel</button><button type="button" class="foundation-primary-button" data-save-submit>Send resume link</button></div><p class="foundation-save-status" role="status" aria-live="polite" hidden></p></div>';
		var modal = overlay.querySelector('.foundation-modal');
		if (modal) {
			modal.setAttribute('aria-hidden', 'true');
			if ('inert' in modal) modal.inert = true;
		}
		overlay.appendChild(panel);
		panel.querySelectorAll('[data-save-close]').forEach(function (button) { button.addEventListener('click', closeSavePanel); });
		panel.querySelector('[data-save-submit]').addEventListener('click', function () { saveDraft(panel); });
		panel.querySelector('input[name="save_email"]').focus();
	}

	function closeSavePanel() {
		var panel = overlay.querySelector('.foundation-save-panel');
		if (panel) panel.remove();
		var modal = overlay.querySelector('.foundation-modal');
		if (modal) {
			modal.removeAttribute('aria-hidden');
			if ('inert' in modal) modal.inert = false;
		}
		if (state.savePanelReturnFocus && typeof state.savePanelReturnFocus.focus === 'function') state.savePanelReturnFocus.focus();
	}

	function saveDraft(panel) {
		var emailInput = panel.querySelector('input[name="save_email"]');
		var nameInput = panel.querySelector('input[name="save_name"]');
		var status = panel.querySelector('.foundation-save-status');
		var button = panel.querySelector('[data-save-submit]');
		var email = String(emailInput.value || '').trim();
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
			status.hidden = false; status.textContent = 'Enter a valid email address.'; emailInput.focus(); return;
		}
		state.contact.email = email;
		state.contact.name = String(nameInput.value || '').trim();
		button.disabled = true; button.textContent = 'Sending…'; status.hidden = false; status.textContent = 'Saving your answers.';
		var body = new FormData();
		body.append('action', 'foundation_save_quote_draft');
		body.append('nonce', config.nonce || '');
		body.append('token', state.resumeToken || '');
		body.append('current_step', String(state.currentStepIndex));
		body.append('resume_base', config.resume && config.resume.baseUrl ? config.resume.baseUrl : window.location.href.split('?')[0]);
		body.append('send_email', '1');
		Object.keys(state.contact).forEach(function (key) { body.append('contact[' + key + ']', key === 'privacy' ? (state.contact[key] ? '1' : '0') : String(state.contact[key] || '')); });
		appendSelections(body);
		fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (response) { return response.json().then(function (payload) { return { response: response, payload: payload }; }); })
			.then(function (result) {
				if (!result.response.ok || !result.payload || !result.payload.success) throw new Error(result.payload && result.payload.data && result.payload.data.message ? result.payload.data.message : 'We could not save your progress.');
				state.resumeToken = result.payload.data.token || state.resumeToken;
				button.hidden = true;
				status.textContent = result.payload.data.email_sent ? 'Your resume link has been emailed.' : 'Your answers were saved, but the email could not be sent. Please try again.';
			})
			.catch(function (error) { button.disabled = false; button.textContent = 'Send resume link'; status.textContent = error.message || 'We could not save your progress.'; });
	}

	function getResumeTokenFromUrl() {
		try { return new URLSearchParams(window.location.search).get((config.resume && config.resume.queryParam) || 'foundation_resume') || ''; }
		catch (error) { return ''; }
	}

	function clearResumeTokenFromUrl() {
		if (!window.history || typeof window.history.replaceState !== 'function') return;
		try {
			var url = new URL(window.location.href);
			var key = (config.resume && config.resume.queryParam) || 'foundation_resume';
			if (!url.searchParams.has(key)) return;
			url.searchParams.delete(key);
			window.history.replaceState(window.history.state, document.title, url.pathname + (url.search || '') + (url.hash || ''));
		} catch (error) {}
	}

	function restoreDraft(token) {
		var body = new URLSearchParams();
		body.set('action', 'foundation_resume_quote_draft');
		body.set('nonce', config.nonce || '');
		body.set('token', token);
		fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
			.then(function (response) { return response.json(); })
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) throw new Error('This saved estimate has expired.');
				state.resumeToken = token;
				clearResumeTokenFromUrl();
				state.selections = payload.data.selections && typeof payload.data.selections === 'object' ? payload.data.selections : {};
				state.contact = Object.assign(state.contact, payload.data.contact || {});
				state.started = true;
				state.routeIds = computeRouteIds();
				var requested = parseInt(payload.data.current_step, 10);
				var visible = getVisibleStepIndexes();
				state.currentStepIndex = visible.indexOf(requested) !== -1 ? requested : (visible.length ? visible[0] : -1);
				state.view = state.currentStepIndex >= 0 ? 'step' : 'review';
				openCalculator();
			})
			.catch(function (error) {
				openCalculator();
				showMessage(error.message || 'This saved estimate could not be restored.');
			});
	}

	function trackEvent(eventName, message) {
		if (!config.ajaxUrl || !config.nonce) return;
		var body = new URLSearchParams();
		body.set('action', 'foundation_track_quote_event');
		body.set('nonce', config.nonce);
		body.set('event', eventName);
		if (message) body.set('message', String(message).slice(0, 300));
		fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() }).catch(function () {});
	}

	function trackViewOnce() {
		var key = 'foundation_project_calculator_view_' + (config.blueprintVersion || config.version || '1');
		try {
			if (window.sessionStorage && !window.sessionStorage.getItem(key)) { window.sessionStorage.setItem(key, '1'); trackEvent('view'); }
		} catch (error) { trackEvent('view'); }
	}
}());
