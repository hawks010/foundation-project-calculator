(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn, { once: true });
	}

	function closest(element, selector) {
		return element && element.closest ? element.closest(selector) : null;
	}

	function journeyConfig() {
		return window.FoundationJourneyAdmin || null;
	}

	function ajaxPost(action, data) {
		var config = journeyConfig();
		if (!config || !config.ajaxUrl) {
			return Promise.reject(new Error('Journey Editor configuration is unavailable.'));
		}
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', config.nonce || '');
		Object.keys(data || {}).forEach(function (key) {
			var value = data[key];
			body.set(key, typeof value === 'string' ? value : JSON.stringify(value));
		});
		return window.fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			return response.json().catch(function () {
				throw new Error('The server returned an unreadable response.');
			}).then(function (payload) {
				if (!response.ok || !payload || !payload.success) {
					var message = payload && payload.data && payload.data.message ? payload.data.message : (config.strings && config.strings.error ? config.strings.error : 'That change could not be saved.');
					throw new Error(message);
				}
				return payload.data || {};
			});
		});
	}

	function announce(message, isError) {
		var toast = document.querySelector('[data-fpc-journey-toast]');
		if (!toast) {
			toast = document.createElement('div');
			toast.className = 'fpc-journey-toast';
			toast.setAttribute('data-fpc-journey-toast', '');
			toast.setAttribute('role', 'status');
			toast.setAttribute('aria-live', 'polite');
			toast.setAttribute('aria-atomic', 'true');
			(document.querySelector('.foundation-calculator-admin') || document.body).appendChild(toast);
		}
		toast.textContent = message || '';
		toast.classList.toggle('is-error', !!isError);
		toast.classList.toggle('is-visible', !!message);
		if (message) {
			window.setTimeout(function () {
				if (toast.textContent === message) toast.classList.remove('is-visible');
			}, 4200);
		}
	}

	function setExpanded(card, expanded, focusInside) {
		if (!card) return;
		var editor = card.querySelector('[data-fpc-step-editor]');
		if (!editor) return;
		editor.hidden = !expanded;
		card.classList.toggle('is-expanded', expanded);
		card.querySelectorAll('[data-fpc-toggle-step]').forEach(function (button) {
			button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			var label = button.querySelector('[data-fpc-toggle-label]');
			if (label) label.textContent = expanded ? 'Minimise' : 'Edit';
		});
		if (expanded && focusInside) {
			var first = editor.querySelector('input:not([type="hidden"]), textarea, select, button');
			if (first) first.focus({ preventScroll: true });
		}
	}

	function replaceJourneyBuilder(html, focusStepId, shouldExpand) {
		var current = document.querySelector('[data-fpc-journey-builder]');
		if (!current || !html) return;
		var holder = document.createElement('div');
		holder.innerHTML = String(html).trim();
		var fresh = holder.querySelector('[data-fpc-journey-builder]');
		if (!fresh) return;
		var oldTop = null;
		if (focusStepId) {
			var oldCard = current.querySelector('[data-fpc-step-id="' + CSS.escape(focusStepId) + '"]');
			if (oldCard) oldTop = oldCard.getBoundingClientRect().top;
		}
		current.replaceWith(fresh);
		if (focusStepId) {
			var newCard = fresh.querySelector('[data-fpc-step-id="' + CSS.escape(focusStepId) + '"]');
			if (newCard) {
				if (shouldExpand) setExpanded(newCard, true, false);
				if (oldTop !== null) {
					var delta = newCard.getBoundingClientRect().top - oldTop;
					window.scrollBy(0, delta);
				} else {
					newCard.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
				}
				var toggle = newCard.querySelector('[data-fpc-toggle-step]');
				if (toggle) toggle.focus({ preventScroll: true });
			}
		}
	}

	function requestBuilderAction(action, data, focusStepId, shouldExpand) {
		return ajaxPost(action, data).then(function (result) {
			replaceJourneyBuilder(result.builder_html, result.focus_step_id || focusStepId || '', !!shouldExpand);
			announce(result.message || 'Saved.', false);
			return result;
		}).catch(function (error) {
			announce(error.message, true);
			throw error;
		});
	}

	function fieldPayload(fieldEditor) {
		var field = {
			id: fieldEditor.getAttribute('data-field-id') || '',
			type: fieldEditor.getAttribute('data-field-type') || '',
			label: (fieldEditor.querySelector('[data-fpc-field-label]') || {}).value || '',
			helper: (fieldEditor.querySelector('[data-fpc-field-helper]') || {}).value || '',
			required: !!(fieldEditor.querySelector('[data-fpc-field-required]') || {}).checked
		};
		var node;
		if (field.type === 'service_card') {
			node = fieldEditor.querySelector('[data-fpc-field-selection-mode]');
			field.selection_mode = node ? node.value : 'single';
			field.options = [];
			fieldEditor.querySelectorAll('[data-fpc-option-row]').forEach(function (row) {
				var valueInput = row.querySelector('[data-fpc-option-value]');
				var labelInput = row.querySelector('[data-fpc-option-label]');
				field.options.push({
					value: valueInput ? valueInput.value : '',
					label: labelInput ? labelInput.value : ''
				});
			});
		}
		if (field.type === 'number_input' || field.type === 'range_slider') {
			['min', 'max', 'step'].forEach(function (key) {
				var input = fieldEditor.querySelector('[data-fpc-field-' + key + ']');
				field[key] = input ? input.value : '';
			});
			node = fieldEditor.querySelector('[data-fpc-field-unit]');
			field.unit = node ? node.value : '';
		}
		if (field.type === 'toggle') {
			node = fieldEditor.querySelector('[data-fpc-field-yes-label]');
			field.yes_label = node ? node.value : 'Yes';
			node = fieldEditor.querySelector('[data-fpc-field-no-label]');
			field.no_label = node ? node.value : 'No';
		}
		if (field.type === 'text_input') {
			node = fieldEditor.querySelector('[data-fpc-field-placeholder]');
			field.placeholder = node ? node.value : '';
		}
		if (field.type === 'description' || field.type === 'section_title' || field.type === 'rich_text') {
			node = fieldEditor.querySelector('[data-fpc-field-text]');
			field.text = node ? node.value : '';
		}
		if (field.type === 'file_upload') {
			node = fieldEditor.querySelector('[data-fpc-field-accept]');
			field.accept = node ? node.value : '';
			node = fieldEditor.querySelector('[data-fpc-field-max-files]');
			field.max_files = node ? node.value : '';
			node = fieldEditor.querySelector('[data-fpc-field-max-size]');
			field.max_file_size_mb = node ? node.value : '';
		}
		return field;
	}

	function stepPayload(card) {
		var editor = card.querySelector('[data-fpc-step-editor]');
		var conditional = editor.querySelector('[data-fpc-step-conditional]');
		return {
			title: (editor.querySelector('[data-fpc-step-title]') || {}).value || '',
			subtitle: (editor.querySelector('[data-fpc-step-subtitle]') || {}).value || '',
			is_conditional: !!(conditional && conditional.checked),
			connections: Array.prototype.map.call(editor.querySelectorAll('[data-fpc-connection]:checked'), function (input) { return input.value; }),
			fields: Array.prototype.map.call(editor.querySelectorAll('[data-fpc-field-editor]'), fieldPayload)
		};
	}

	function markDirty(editor) {
		if (!editor) return;
		editor.classList.add('is-dirty');
		var status = editor.querySelector('[data-fpc-step-save-status]');
		if (status) status.textContent = 'Unsaved changes';
	}

	function setSaving(card, saving, message) {
		var editor = card.querySelector('[data-fpc-step-editor]');
		if (!editor) return;
		var button = editor.querySelector('[data-fpc-save-step]');
		var status = editor.querySelector('[data-fpc-step-save-status]');
		if (button) {
			button.disabled = !!saving;
			button.textContent = saving ? 'Saving…' : 'Save screen';
		}
		if (status && message) status.textContent = message;
	}

	function updateConditionalState(card) {
		var editor = card.querySelector('[data-fpc-step-editor]');
		if (!editor) return;
		var conditional = editor.querySelector('[data-fpc-step-conditional]');
		var section = editor.querySelector('[data-fpc-connections-section]');
		if (!conditional || !section) return;
		section.classList.toggle('is-disabled', !conditional.checked);
		section.querySelectorAll('input').forEach(function (input) {
			input.disabled = !conditional.checked;
		});
	}

	function routeOrder(group) {
		var list = document.querySelector('[data-fpc-journey-list="' + CSS.escape(group) + '"]');
		if (!list) return [];
		return Array.prototype.map.call(list.querySelectorAll(':scope > [data-fpc-step-id]'), function (card) {
			return card.getAttribute('data-fpc-step-id') || '';
		}).filter(Boolean);
	}

	function reorder(group, order, focusStepId) {
		return requestBuilderAction('foundation_journey_reorder', { group: group, order: order }, focusStepId || '', false);
	}


	function syncLeadStatus(recordType, recordId, status, label) {
		document.querySelectorAll('[data-fpc-lead-status][data-record-type="' + CSS.escape(recordType) + '"][data-record-id="' + CSS.escape(String(recordId)) + '"]').forEach(function (select) {
			select.value = status;
		});
		document.querySelectorAll('[data-fpc-workflow-label][data-record-type="' + CSS.escape(recordType) + '"][data-record-id="' + CSS.escape(String(recordId)) + '"]').forEach(function (node) {
			node.textContent = label || status;
			node.setAttribute('data-status', status);
		});
	}

	function initLeadControls() {
		document.addEventListener('change', function (event) {
			var select = closest(event.target, '[data-fpc-lead-status]');
			if (!select) return;
			select.disabled = true;
			ajaxPost('foundation_lead_update', {
				record_type: select.getAttribute('data-record-type') || '',
				record_id: select.getAttribute('data-record-id') || '',
				status: select.value
			}).then(function (result) {
				syncLeadStatus(select.getAttribute('data-record-type') || '', select.getAttribute('data-record-id') || '', result.status || select.value, result.status_label || '');
				announce(result.message || 'Lead updated.', false);
			}).catch(function (error) {
				announce(error.message, true);
			}).finally(function () { select.disabled = false; });
		});

		document.addEventListener('click', function (event) {
			var saveContact = closest(event.target, '[data-fpc-lead-save-contact]');
			if (saveContact) {
				var manager = closest(saveContact, '[data-fpc-lead-manager]');
				if (!manager) return;
				var email = manager.querySelector('[data-fpc-lead-email]');
				var name = manager.querySelector('[data-fpc-lead-name]');
				saveContact.disabled = true;
				ajaxPost('foundation_lead_update', {
					record_type: manager.getAttribute('data-record-type') || '',
					record_id: manager.getAttribute('data-record-id') || '',
					email: email ? email.value : '',
					name: name ? name.value : ''
				}).then(function (result) {
					announce(result.message || 'Customer details updated.', false);
					if (result.email_changed && manager.getAttribute('data-record-type') === 'brief') {
						manager.classList.add('needs-magic-resend');
						var verification = manager.querySelector('[data-fpc-verification-pill]');
						if (verification) {
							verification.textContent = 'Email not verified';
							verification.classList.remove('is-good');
							verification.classList.add('is-warning');
						}
					}
				}).catch(function (error) { announce(error.message, true); }).finally(function () { saveContact.disabled = false; });
				return;
			}

			var resend = closest(event.target, '[data-fpc-resend-magic]');
			if (resend) {
				resend.disabled = true;
				ajaxPost('foundation_lead_resend_magic', { record_id: resend.getAttribute('data-record-id') || '' }).then(function (result) {
					announce(result.message || 'Magic link sent.', false);
					var manager = closest(resend, '[data-fpc-lead-manager]');
					if (manager) manager.classList.remove('needs-magic-resend');
				}).catch(function (error) { announce(error.message, true); }).finally(function () { resend.disabled = false; });
				return;
			}

			var del = closest(event.target, '[data-fpc-delete-lead]');
			if (del) {
				var config = journeyConfig();
				var message = config && config.strings ? config.strings.deleteLeadConfirm : 'Permanently delete this lead?';
				if (!window.confirm(message)) return;
				del.disabled = true;
				ajaxPost('foundation_lead_delete', {
					record_type: del.getAttribute('data-record-type') || '',
					record_id: del.getAttribute('data-record-id') || ''
				}).then(function (result) {
					announce(result.message || 'Lead deleted.', false);
					var returnUrl = del.getAttribute('data-return-url') || '';
					if (returnUrl) window.location.assign(returnUrl);
					else window.location.reload();
				}).catch(function (error) { del.disabled = false; announce(error.message, true); });
				return;
			}

			var clearArchived = closest(event.target, '[data-fpc-clear-archived]');
			if (clearArchived) {
				var cfg = journeyConfig();
				var confirmMessage = cfg && cfg.strings ? cfg.strings.clearArchivedConfirm : 'Delete every archived lead?';
				if (!window.confirm(confirmMessage)) return;
				clearArchived.disabled = true;
				ajaxPost('foundation_lead_clear_archived', {}).then(function (result) {
					announce(result.message || 'Archived leads cleared.', false);
					window.setTimeout(function () { window.location.reload(); }, 350);
				}).catch(function (error) { clearArchived.disabled = false; announce(error.message, true); });
			}
		});
	}

	function saveJourneyMedia(picker, url) {
		var key = picker.getAttribute('data-setting-key') || '';
		var status = picker.querySelector('[data-fpc-media-status]');
		if (status) status.textContent = 'Saving…';
		return ajaxPost('foundation_journey_save_media', { setting_key: key, image_url: url || '' }).then(function (result) {
			var image = picker.querySelector('[data-fpc-media-preview]');
			var empty = picker.querySelector('[data-fpc-media-empty]');
			if (image) {
				image.src = result.image_url || '';
				image.hidden = !result.image_url;
			}
			if (empty) empty.hidden = !!result.image_url;
			var remove = picker.querySelector('[data-fpc-media-remove]');
			if (remove) remove.hidden = !result.image_url;
			if (status) status.textContent = result.message || 'Saved';
			announce(result.message || 'Journey image updated.', false);
			return result;
		}).catch(function (error) {
			if (status) status.textContent = error.message;
			announce(error.message, true);
			throw error;
		});
	}

	function initMediaControls() {
		document.addEventListener('click', function (event) {
			var select = closest(event.target, '[data-fpc-media-select]');
			if (select) {
				var picker = closest(select, '[data-fpc-media-picker]');
				if (!picker || !window.wp || !wp.media) {
					announce('The WordPress media library is unavailable on this screen.', true);
					return;
				}
				var cfg = journeyConfig();
				var frame = wp.media({
					title: cfg && cfg.strings ? cfg.strings.mediaTitle : 'Choose a calculator image',
					button: { text: cfg && cfg.strings ? cfg.strings.mediaButton : 'Use this image' },
					library: { type: 'image' },
					multiple: false
				});
				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					if (attachment && attachment.url) saveJourneyMedia(picker, attachment.url);
				});
				frame.open();
				return;
			}
			var remove = closest(event.target, '[data-fpc-media-remove]');
			if (remove) {
				var removePicker = closest(remove, '[data-fpc-media-picker]');
				if (removePicker) saveJourneyMedia(removePicker, '');
			}
		});
	}

	ready(function () {
		document.querySelectorAll('[data-fpc-confirm]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				var message = form.getAttribute('data-fpc-confirm') || 'Are you sure?';
				if (!window.confirm(message)) event.preventDefault();
			});
		});

		document.querySelectorAll('[data-fpc-copy]').forEach(function (button) {
			button.addEventListener('click', function () {
				var value = button.getAttribute('data-fpc-copy') || '';
				var original = button.textContent;
				function done() {
					button.textContent = 'Copied';
					window.setTimeout(function () { button.textContent = original; }, 1400);
				}
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(value).then(done).catch(function () {});
				} else {
					var area = document.createElement('textarea');
					area.value = value;
					area.setAttribute('readonly', 'readonly');
					area.style.position = 'fixed';
					area.style.opacity = '0';
					document.body.appendChild(area);
					area.select();
					try { document.execCommand('copy'); done(); } catch (error) {}
					document.body.removeChild(area);
				}
			});
		});

		var search = document.querySelector('[data-fpc-price-search]');
		if (search) {
			search.addEventListener('input', function () {
				var needle = String(search.value || '').trim().toLowerCase();
				document.querySelectorAll('[data-fpc-price-section]').forEach(function (section) {
					var visible = 0;
					section.querySelectorAll('[data-fpc-price-row]').forEach(function (row) {
						var haystack = row.getAttribute('data-search-text') || '';
						var match = !needle || haystack.indexOf(needle) !== -1;
						row.classList.toggle('is-filtered-out', !match);
						if (match) visible += 1;
					});
					section.classList.toggle('is-empty', visible === 0);
				});
			});
		}

		initLeadControls();
		initMediaControls();

		if (!document.querySelector('[data-fpc-journey-builder]')) return;

		document.addEventListener('click', function (event) {
			var toggle = closest(event.target, '[data-fpc-toggle-step]');
			if (toggle) {
				var toggleCard = closest(toggle, '[data-fpc-step-id]');
				var editor = toggleCard ? toggleCard.querySelector('[data-fpc-step-editor]') : null;
				var opening = !!(editor && editor.hidden);
				if (opening) {
					document.querySelectorAll('[data-fpc-step-id].is-expanded').forEach(function (other) {
						if (other !== toggleCard) setExpanded(other, false, false);
					});
				}
				setExpanded(toggleCard, opening, false);
				if (opening) updateConditionalState(toggleCard);
				return;
			}

			var collapse = closest(event.target, '[data-fpc-collapse-step]');
			if (collapse) {
				var collapseCard = closest(collapse, '[data-fpc-step-id]');
				setExpanded(collapseCard, false, false);
				var collapseToggle = collapseCard ? collapseCard.querySelector('[data-fpc-toggle-step]') : null;
				if (collapseToggle) collapseToggle.focus({ preventScroll: true });
				return;
			}

			var add = closest(event.target, '[data-fpc-add-step]');
			if (add) {
				add.disabled = true;
				requestBuilderAction('foundation_journey_add_step', { group: add.getAttribute('data-group') || 'start', after_step_id: add.getAttribute('data-after-step-id') || '' }, '', true).catch(function () {
					add.disabled = false;
				});
				return;
			}

			var duplicate = closest(event.target, '[data-fpc-duplicate-step]');
			if (duplicate) {
				var duplicateCard = closest(duplicate, '[data-fpc-step-id]');
				if (!duplicateCard) return;
				duplicate.disabled = true;
				requestBuilderAction('foundation_journey_duplicate_step', { step_id: duplicateCard.getAttribute('data-fpc-step-id') || '' }, '', true).catch(function () {
					duplicate.disabled = false;
				});
				return;
			}

			var save = closest(event.target, '[data-fpc-save-step]');
			if (save) {
				var saveCard = closest(save, '[data-fpc-step-id]');
				if (!saveCard) return;
				var saveId = saveCard.getAttribute('data-fpc-step-id') || '';
				setSaving(saveCard, true, 'Saving…');
				ajaxPost('foundation_journey_save_step', { step_id: saveId, payload: JSON.stringify(stepPayload(saveCard)) }).then(function (result) {
					replaceJourneyBuilder(result.builder_html, result.focus_step_id || saveId, true);
					announce(result.message || 'Screen saved.', false);
				}).catch(function (error) {
					setSaving(saveCard, false, error.message);
					announce(error.message, true);
				});
				return;
			}

			var removeOption = closest(event.target, '[data-fpc-remove-option]');
			if (removeOption) {
				var row = closest(removeOption, '[data-fpc-option-row]');
				var list = closest(removeOption, '[data-fpc-option-list]');
				if (!row || !list) return;
				if (list.querySelectorAll('[data-fpc-option-row]').length <= 1) {
					announce('Keep at least one choice on this question.', true);
					return;
				}
				if (row.getAttribute('data-option-routes') === '1') {
					var config = journeyConfig();
					var message = config && config.strings ? config.strings.removeOptionConfirm : 'This choice routes to another screen. Continue?';
					if (!window.confirm(message)) return;
				}
				var optionEditor = closest(row, '[data-fpc-step-editor]');
				row.remove();
				markDirty(optionEditor);
				return;
			}

			var addOption = closest(event.target, '[data-fpc-add-option]');
			if (addOption) {
				var optionContainer = closest(addOption, '[data-fpc-option-editor]');
				var optionList = optionContainer ? optionContainer.querySelector('[data-fpc-option-list]') : null;
				if (!optionList) return;
				var row = document.createElement('div');
				var value = 'choice_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 7);
				row.className = 'fpc-option-row is-new';
				row.setAttribute('data-fpc-option-row', '');
				row.setAttribute('data-option-routes', '0');
				row.innerHTML = '<span class="fpc-option-grip" aria-hidden="true">•</span><input type="hidden" data-fpc-option-value><label><span class="screen-reader-text">Choice label</span><input type="text" data-fpc-option-label maxlength="180" placeholder="New choice"></label><button class="button-link-delete" type="button" data-fpc-remove-option>Remove</button>';
				row.querySelector('[data-fpc-option-value]').value = value;
				optionList.appendChild(row);
				var input = row.querySelector('[data-fpc-option-label]');
				if (input) input.focus();
				markDirty(closest(addOption, '[data-fpc-step-editor]'));
				return;
			}

			var move = closest(event.target, '[data-fpc-move-step]');
			if (move && !move.disabled) {
				var moveCard = closest(move, '[data-fpc-step-id]');
				if (!moveCard) return;
				var group = moveCard.getAttribute('data-fpc-group') || '';
				var order = routeOrder(group);
				var id = moveCard.getAttribute('data-fpc-step-id') || '';
				var index = order.indexOf(id);
				var targetIndex = move.getAttribute('data-fpc-move-step') === 'up' ? index - 1 : index + 1;
				if (index < 0 || targetIndex < 0 || targetIndex >= order.length) return;
				order.splice(index, 1);
				order.splice(targetIndex, 0, id);
				move.disabled = true;
				reorder(group, order, id).catch(function () { move.disabled = false; });
				return;
			}

			var del = closest(event.target, '[data-fpc-delete-draft]');
			if (del) {
				var deleteCard = closest(del, '[data-fpc-step-id]');
				var config = journeyConfig();
				var confirmMessage = config && config.strings ? config.strings.deleteDraftConfirm : 'Delete this unconnected draft screen?';
				if (!deleteCard || !window.confirm(confirmMessage)) return;
				del.disabled = true;
				requestBuilderAction('foundation_journey_delete_draft', { step_id: deleteCard.getAttribute('data-fpc-step-id') || '' }, '', false).catch(function () {
					del.disabled = false;
				});
				return;
			}

			var undo = closest(event.target, '[data-fpc-journey-undo]');
			if (undo && !undo.disabled) {
				undo.disabled = true;
				requestBuilderAction('foundation_journey_undo', {}, '', false).catch(function () { undo.disabled = false; });
			}
		});

		document.addEventListener('input', function (event) {
			var editor = closest(event.target, '[data-fpc-step-editor]');
			if (editor) markDirty(editor);
		});
		document.addEventListener('change', function (event) {
			var editor = closest(event.target, '[data-fpc-step-editor]');
			if (editor) markDirty(editor);
			if (closest(event.target, '[data-fpc-step-conditional]')) {
				var card = closest(event.target, '[data-fpc-step-id]');
				if (card) updateConditionalState(card);
			}
		});

		var dragged = null;
		var originalOrder = null;
		document.addEventListener('pointerdown', function (event) {
			var handle = closest(event.target, '[data-fpc-drag-handle]');
			var card = handle ? closest(handle, '[data-fpc-step-id]') : null;
			if (card) card.setAttribute('draggable', 'true');
		});
		document.addEventListener('dragstart', function (event) {
			var card = closest(event.target, '[data-fpc-step-id]');
			if (!card || card.getAttribute('draggable') !== 'true') return;
			dragged = card;
			originalOrder = routeOrder(card.getAttribute('data-fpc-group') || '');
			card.classList.add('is-dragging');
			if (event.dataTransfer) {
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setData('text/plain', card.getAttribute('data-fpc-step-id') || '');
			}
		});
		document.addEventListener('dragover', function (event) {
			if (!dragged) return;
			var target = closest(event.target, '[data-fpc-step-id]');
			if (!target || target === dragged || target.getAttribute('data-fpc-group') !== dragged.getAttribute('data-fpc-group')) return;
			event.preventDefault();
			document.querySelectorAll('.fpc-journey-step.is-drag-over').forEach(function (node) { node.classList.remove('is-drag-over'); });
			target.classList.add('is-drag-over');
		});
		document.addEventListener('drop', function (event) {
			if (!dragged) return;
			var target = closest(event.target, '[data-fpc-step-id]');
			if (!target || target === dragged || target.getAttribute('data-fpc-group') !== dragged.getAttribute('data-fpc-group')) return;
			event.preventDefault();
			var rect = target.getBoundingClientRect();
			var after = event.clientY > rect.top + rect.height / 2;
			target.parentNode.insertBefore(dragged, after ? target.nextSibling : target);
			var group = dragged.getAttribute('data-fpc-group') || '';
			var id = dragged.getAttribute('data-fpc-step-id') || '';
			var newOrder = routeOrder(group);
			reorder(group, newOrder, id).catch(function () {
				var list = document.querySelector('[data-fpc-journey-list="' + CSS.escape(group) + '"]');
				if (list && originalOrder) {
					originalOrder.forEach(function (stepId) {
						var node = list.querySelector('[data-fpc-step-id="' + CSS.escape(stepId) + '"]');
						if (node) list.appendChild(node);
					});
				}
			});
		});
		document.addEventListener('dragend', function () {
			document.querySelectorAll('.fpc-journey-step.is-dragging, .fpc-journey-step.is-drag-over').forEach(function (node) {
				node.classList.remove('is-dragging', 'is-drag-over');
				node.removeAttribute('draggable');
			});
			dragged = null;
			originalOrder = null;
		});
		document.addEventListener('pointerup', function () {
			document.querySelectorAll('[data-fpc-step-id][draggable="true"]:not(.is-dragging)').forEach(function (card) { card.removeAttribute('draggable'); });
		});

		window.addEventListener('beforeunload', function (event) {
			if (!document.querySelector('[data-fpc-step-editor].is-dirty')) return;
			event.preventDefault();
			event.returnValue = '';
		});
	});
}());
