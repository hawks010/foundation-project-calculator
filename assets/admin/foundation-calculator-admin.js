(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn, { once: true });
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
	});
}());
