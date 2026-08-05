(function () {
  'use strict';

  var originalFetch = window.fetch;
  window.fetch = function (url, options) {
    var action = '';
    var body = options && options.body;
    if (body instanceof URLSearchParams) action = body.get('action') || '';
    else if (body instanceof FormData) action = String(body.get('action') || '');

    var data = { tracked: true };
    if (action === 'foundation_submit_quote') {
      data = {
        quote: {
          one_off_min: 4260,
          one_off_max: 4260,
          monthly_min: 45,
          monthly_max: 45,
          has_pricing: true,
          line_items: [],
          manual_items: [],
          currency: '£'
        },
        reference: 'INK-20260805-SMOKE01',
        admin_email_status: 'sent',
        customer_email_status: 'sent',
        duplicate: false,
        message: 'Your estimate has been sent to Inkfire.'
      };
    } else if (action === 'foundation_save_quote_draft') {
      data = { token: 'smoke-resume-token-1234567890', resume_url: '#resume', email_sent: true };
    }
    return Promise.resolve({
      ok: true,
      status: 200,
      json: function () { return Promise.resolve({ success: true, data: data }); }
    });
  };

  function waitFor(selector, timeout) {
    timeout = timeout || 6000;
    var started = Date.now();
    return new Promise(function (resolve, reject) {
      (function poll() {
        var node = document.querySelector(selector);
        if (node) return resolve(node);
        if (Date.now() - started > timeout) return reject(new Error('Timed out waiting for ' + selector));
        window.setTimeout(poll, 25);
      }());
    });
  }

  function click(selector) {
    var node = document.querySelector(selector);
    if (!node) throw new Error('Missing control: ' + selector);
    node.click();
  }

  function choose(fieldId, optionIndex) {
    var selector = '[data-field-id="' + fieldId + '"][value="' + optionIndex + '"]';
    var node = document.querySelector(selector);
    if (!node) throw new Error('Missing choice: ' + selector);
    node.checked = true;
    node.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function typeValue(fieldId, value) {
    var node = document.querySelector('[data-field-id="' + fieldId + '"]');
    if (!node) throw new Error('Missing field: ' + fieldId);
    node.value = value;
    node.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function fillContact(name, value) {
    var node = document.querySelector('[data-foundation-contact-form] [name="' + name + '"]');
    if (!node) throw new Error('Missing contact field: ' + name);
    if (node.type === 'checkbox') node.checked = Boolean(value);
    else node.value = value;
    node.dispatchEvent(new Event(node.type === 'checkbox' ? 'change' : 'input', { bubbles: true }));
  }

  function assertContains(selector, expected) {
    var node = document.querySelector(selector);
    var text = node ? node.textContent.replace(/\s+/g, ' ').trim() : '';
    if (text.indexOf(expected) === -1) throw new Error('Expected “' + expected + '” in ' + selector + ', got: ' + text);
  }

  function next() { click('[data-foundation-next]'); }
  function pause() { return new Promise(function (resolve) { window.setTimeout(resolve, 40); }); }

  async function run() {
    await waitFor('[data-foundation-start]');
    click('[data-foundation-start]');
    await waitFor('[data-field-id="route_selection"]');

    choose('route_selection', 0);
    next();
    await pause();

    choose('web_project_type', 0);
    next();
    await pause();

    typeValue('web_small_pages', '5');
    next();
    await pause();

    choose('web_shop', 0);
    choose('web_hosting', 2);
    choose('web_alt_text', 2);
    choose('web_accessibility_testing', 0);
    choose('web_accessibility_setup', 0);
    next();
    await waitFor('.foundation-review');

    assertContains('.foundation-review', '£4,260');
    assertContains('.foundation-review', '£45');
    assertContains('.foundation-review', 'Hosting + plugins');
    assertContains('.foundation-live-summary', '£4,260');

    next();
    await waitFor('[data-foundation-contact-form]');
    fillContact('name', 'Browser Smoke');
    fillContact('company', 'Inkfire QA');
    fillContact('email', 'qa@example.test');
    fillContact('website', 'https://example.test');
    fillContact('privacy', true);

    var contactForm = document.querySelector('[data-foundation-contact-form]');
    var submitWasPrevented = false;
    contactForm.addEventListener('submit', function (event) {
      submitWasPrevented = event.defaultPrevented;
      if (!event.defaultPrevented) event.preventDefault();
    });
    click('[data-contact-submit]');
    await pause();
    if (!submitWasPrevented) throw new Error('The contact form did not prevent a native page submission.');

    await waitFor('.foundation-success');
    assertContains('.foundation-success', 'INK-20260805-SMOKE01');
    assertContains('.foundation-success', '£4,260');
    assertContains('.foundation-success', '£45');
    assertContains('.foundation-success', 'Your estimate has been sent to Inkfire.');
    var saveProgress = document.querySelector('[data-foundation-save-progress]');
    if (!saveProgress || !saveProgress.hidden) throw new Error('Save progress remained visible after a completed enquiry.');

    document.body.dataset.smokeStage = 'success';
    var captureStarted = Date.now();
    while (document.body.dataset.smokeCapture !== 'done' && Date.now() - captureStarted < 3000) await pause();

    click('[data-success-close]');
    await new Promise(function (resolve) { window.setTimeout(resolve, 240); });
    document.dispatchEvent(new CustomEvent('foundation:open'));
    await waitFor('[data-foundation-start]');
    if (document.querySelector('.foundation-success')) throw new Error('A completed enquiry was not reset before reopening.');

    document.body.dataset.smoke = 'pass';
    document.getElementById('smoke-result').textContent = 'PASS: complete web route, review totals, contact validation and success receipt';
  }

  run().catch(function (error) {
    document.body.dataset.smoke = 'fail';
    document.getElementById('smoke-result').textContent = 'FAIL: ' + (error && error.message ? error.message : String(error));
    console.error(error);
  }).finally(function () {
    window.fetch = originalFetch || window.fetch;
  });
}());
