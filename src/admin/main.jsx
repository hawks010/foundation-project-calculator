import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

const fieldBlueprints = [
  {
    type: 'service_card',
    variant: 'services',
    label: 'Service choices',
    helper: 'Let customers select one or more services.',
    group: 'Customer questions',
  },
  {
    type: 'service_card',
    variant: 'budget',
    role: 'budget',
    label: 'Budget range',
    helper: 'Budget bands used by the frontend quote summary.',
    group: 'Customer questions',
  },
  {
    type: 'service_card',
    variant: 'timeline',
    role: 'timeline',
    label: 'Timeline',
    helper: 'When the customer wants to start or complete the project.',
    group: 'Customer questions',
  },
  {
    type: 'toggle',
    label: 'Yes / no toggle',
    helper: 'A priced add-on or simple binary choice.',
    group: 'Customer questions',
  },
  {
    type: 'range_slider',
    label: 'Quantity slider',
    helper: 'Let customers choose pages, hours, or another quantity.',
    group: 'Customer questions',
  },
  {
    type: 'text_input',
    label: 'Short answer',
    helper: 'A short text field for details.',
    group: 'Customer questions',
  },
  {
    type: 'rich_text',
    label: 'Project brief',
    helper: 'A longer answer area for detailed notes.',
    group: 'Customer questions',
  },
  {
    type: 'file_upload',
    label: 'File upload',
    helper: 'Collect documents, screenshots, or brand assets.',
    group: 'Customer questions',
  },
  {
    type: 'section_title',
    label: 'Section title',
    helper: 'A visual heading inside a slide.',
    group: 'Content and layout',
  },
  {
    type: 'description',
    label: 'Description text',
    helper: 'Plain helper copy between questions.',
    group: 'Content and layout',
  },
  {
    type: 'divider',
    label: 'Divider',
    helper: 'A quiet break between sections.',
    group: 'Content and layout',
  },
];

const groupedBlueprints = fieldBlueprints.reduce((groups, item) => {
  groups[item.group] = groups[item.group] || [];
  groups[item.group].push(item);
  return groups;
}, {});

const typeLabels = {
  service_card: 'Selection cards',
  range_slider: 'Quantity slider',
  toggle: 'Yes / no toggle',
  text_input: 'Short answer',
  section_title: 'Section title',
  description: 'Description text',
  divider: 'Divider',
  rich_text: 'Project brief',
  file_upload: 'File upload',
};

const roleOptions = [
  { value: '', label: 'No special role' },
  { value: 'budget', label: 'Core budget picker' },
  { value: 'timeline', label: 'Core timeline picker' },
  { value: 'services_main', label: 'Core services picker' },
];

const variantOptions = [
  { value: 'services', label: 'Services' },
  { value: 'budget', label: 'Budget' },
  { value: 'timeline', label: 'Timeline' },
];

const settingsSections = [
  {
    title: 'Email',
    description: 'Recipients, sender details, and customer confirmation copy.',
    fields: [
      { key: 'admin_email', label: 'Admin email', type: 'email' },
      { key: 'cc_emails', label: 'CC emails', help: 'Comma separated.' },
      { key: 'from_name', label: 'From name' },
      { key: 'from_email', label: 'From email', type: 'email' },
      { key: 'customer_confirmation_enabled', label: 'Send customer confirmation email', type: 'checkbox' },
      { key: 'admin_subject_prefix', label: 'Admin subject prefix' },
      { key: 'customer_subject', label: 'Customer subject' },
      { key: 'customer_intro', label: 'Customer intro', type: 'textarea' },
      { key: 'success_message', label: 'Success message', type: 'textarea' },
    ],
  },
  {
    title: 'Branding',
    description: 'Public calculator labels, imagery, and final-step content.',
    fields: [
      { key: 'launch_button_label', label: 'Launch button label' },
      { key: 'wizard_title', label: 'Wizard title' },
      { key: 'quote_mode_enabled', label: 'Quote-only mode', type: 'checkbox', help: 'Hides automated estimates and treats submissions as custom quote briefs.' },
      { key: 'currency_symbol', label: 'Currency symbol' },
      { key: 'logo_url', label: 'Logo URL' },
      { key: 'intro_image_url', label: 'Intro image URL' },
      { key: 'intro_heading', label: 'Intro heading', type: 'textarea' },
      { key: 'intro_text', label: 'Intro text', type: 'textarea' },
      { key: 'testimonial_image_url', label: 'Testimonial image URL' },
      { key: 'testimonial_heading', label: 'Testimonial heading', type: 'textarea' },
      { key: 'testimonial_quote', label: 'Testimonial quote', type: 'textarea' },
      { key: 'testimonial_attribution', label: 'Testimonial attribution' },
      { key: 'portfolio_url', label: 'Customer CTA URL' },
    ],
  },
  {
    title: 'Uploads',
    description: 'File validation and staff attachment packaging.',
    fields: [
      { key: 'allowed_file_types', label: 'Allowed file types', help: 'Comma separated extensions. SVG is excluded by default.' },
      { key: 'max_file_size_mb', label: 'Max file size per file (MB)', type: 'number' },
      { key: 'max_total_upload_mb', label: 'Max total upload size (MB)', type: 'number' },
      { key: 'max_files_per_field', label: 'Max files per upload field', type: 'number' },
      { key: 'attach_pdf_summary', label: 'Attach PDF summary', type: 'checkbox' },
      { key: 'attach_json_summary', label: 'Attach JSON summary', type: 'checkbox' },
      { key: 'attach_zip_package', label: 'Attach ZIP package', type: 'checkbox' },
    ],
  },
  {
    title: 'Social',
    description: 'Links used in customer follow-up content.',
    fields: [
      { key: 'linkedin_url', label: 'LinkedIn URL' },
      { key: 'twitter_url', label: 'Twitter/X URL' },
      { key: 'facebook_url', label: 'Facebook URL' },
      { key: 'instagram_url', label: 'Instagram URL' },
      { key: 'tiktok_url', label: 'TikTok URL' },
    ],
  },
];

const emptyStep = () => ({
  id: makeId('step'),
  title: 'New slide',
  subtitle: 'Fill in the details below.',
  is_conditional: false,
  fields: [],
});

function makeId(prefix) {
  if (window.crypto?.getRandomValues) {
    const bytes = new Uint32Array(2);
    window.crypto.getRandomValues(bytes);
    return `${prefix}_${Array.from(bytes).map((item) => item.toString(36)).join('')}`;
  }
  return `${prefix}_${Math.random().toString(36).slice(2, 12)}`;
}

function classNames(...items) {
  return items.filter(Boolean).join(' ');
}

function parseNumber(value, fallback = 0) {
  if (value === '' || value === null || value === undefined) {
    return fallback;
  }
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function createField(type, extras = {}) {
  const blueprint = fieldBlueprints.find((item) => item.type === type && (!extras.variant || item.variant === extras.variant))
    || fieldBlueprints.find((item) => item.type === type)
    || fieldBlueprints[0];
  const label = blueprint.label || typeLabels[type] || 'Question';
  const base = {
    id: makeId('field'),
    type,
    label,
    helper: blueprint.helper || '',
    placeholder: '',
    text: type === 'description' ? 'Add helpful context here.' : '',
    required: false,
  };

  if (type === 'service_card') {
    const variant = extras.variant || blueprint.variant || 'services';
    return {
      ...base,
      variant,
      role: extras.role || blueprint.role || (variant === 'services' ? '' : variant),
      options: defaultOptionsForVariant(variant),
    };
  }

  if (type === 'range_slider') {
    return { ...base, min: 1, max: 10, step: 1, unit: 'items', price_per_unit: 0 };
  }

  if (type === 'toggle') {
    return { ...base, yes_label: 'Yes', no_label: 'No', price: 0 };
  }

  if (type === 'file_upload') {
    return { ...base, accept: 'pdf,jpg,jpeg,png,webp,doc,docx,zip', max_files: 5, max_file_size_mb: 10 };
  }

  return base;
}

function defaultOptionsForVariant(variant) {
  if (variant === 'budget') {
    return [
      { label: 'Up to GBP 3,000', price: 0, route_step_id: '' },
      { label: 'GBP 3,000-GBP 6,000', price: 0, route_step_id: '' },
      { label: 'GBP 6,000-GBP 10,000', price: 0, route_step_id: '' },
      { label: 'GBP 10,000+', price: 0, route_step_id: '' },
    ];
  }
  if (variant === 'timeline') {
    return [
      { label: 'As soon as possible', price: 0, route_step_id: '' },
      { label: 'In the next 1-2 months', price: 0, route_step_id: '' },
      { label: 'In 3-6 months', price: 0, route_step_id: '' },
      { label: 'I am flexible', price: 0, route_step_id: '' },
    ];
  }
  return [
    { label: 'Website design', price: 0, route_step_id: '' },
    { label: 'Branding', price: 0, route_step_id: '' },
    { label: 'Care plan', price: 0, route_step_id: '' },
  ];
}

function normalizeClientSteps(input) {
  if (!Array.isArray(input) || input.length === 0) {
    return [emptyStep()];
  }
  return input.map((step, stepIndex) => ({
    id: step.id || makeId('step'),
    title: step.title || `Slide ${stepIndex + 1}`,
    subtitle: step.subtitle || 'Fill in the details below.',
    is_conditional: Boolean(step.is_conditional),
    fields: Array.isArray(step.fields) ? step.fields.map(normalizeClientField).filter(Boolean) : [],
  }));
}

function normalizeClientField(field) {
  if (!field || !field.type || !typeLabels[field.type]) {
    return null;
  }
  const base = {
    id: field.id || makeId('field'),
    type: field.type,
    label: field.label || typeLabels[field.type],
    helper: field.helper || '',
    placeholder: field.placeholder || '',
    text: field.text || '',
    required: Boolean(field.required),
  };
  if (field.type === 'service_card') {
    const inferred = inferServiceMeta(field);
    return {
      ...base,
      variant: inferred.variant,
      role: inferred.role,
      options: Array.isArray(field.options) ? field.options.map((option) => ({
        label: option?.label || 'Option',
        price: parseNumber(option?.price, 0),
        route_step_id: option?.route_step_id || '',
      })) : [],
    };
  }
  if (field.type === 'range_slider') {
    return {
      ...base,
      min: parseNumber(field.min, 1),
      max: parseNumber(field.max, 10),
      step: Math.max(1, parseNumber(field.step, 1)),
      unit: field.unit || 'items',
      price_per_unit: parseNumber(field.price_per_unit, 0),
    };
  }
  if (field.type === 'toggle') {
    return {
      ...base,
      yes_label: field.yes_label || 'Yes',
      no_label: field.no_label || 'No',
      price: parseNumber(field.price, 0),
    };
  }
  if (field.type === 'file_upload') {
    return {
      ...base,
      accept: field.accept || 'pdf,jpg,jpeg,png,webp,doc,docx,zip',
      max_files: Math.max(1, parseNumber(field.max_files, 5)),
      max_file_size_mb: Math.max(1, parseNumber(field.max_file_size_mb, 10)),
    };
  }
  return base;
}

function inferServiceMeta(field) {
  let variant = ['services', 'budget', 'timeline'].includes(field.variant) ? field.variant : 'services';
  let role = field.role || '';
  const hint = `${field.id || ''} ${field.label || ''}`.toLowerCase();

  if (!role) {
    if (hint.includes('budget')) {
      role = 'budget';
      variant = 'budget';
    } else if (hint.includes('timeline') || hint.includes('when would you like to start')) {
      role = 'timeline';
      variant = 'timeline';
    } else if (hint.includes('services_main') || hint.includes('which services') || hint.includes('services do you need')) {
      role = 'services_main';
      variant = 'services';
    }
  }

  if (role === 'budget') {
    variant = 'budget';
  }
  if (role === 'timeline') {
    variant = 'timeline';
  }

  return { variant, role };
}

function reorder(items, fromIndex, toIndex) {
  const next = [...items];
  const [moved] = next.splice(fromIndex, 1);
  next.splice(toIndex, 0, moved);
  return next;
}

function getSyncWarnings(steps, settings = null) {
  const warnings = [];
  const quoteModeEnabled = Boolean(settings?.quote_mode_enabled === true || settings?.quote_mode_enabled === 1 || settings?.quote_mode_enabled === '1');
  let hasBudget = false;
  let hasTimeline = false;
  let hasServices = false;
  const stepIds = new Set(steps.map((step) => step.id));

  steps.forEach((step) => {
    step.fields.forEach((field) => {
      if (field.type !== 'service_card') {
        return;
      }
      if (field.role === 'budget' || field.variant === 'budget') {
        hasBudget = true;
      }
      if (field.role === 'timeline' || field.variant === 'timeline') {
        hasTimeline = true;
      }
      if (field.role === 'services_main' || field.variant === 'services') {
        hasServices = true;
      }
      if (!field.options?.length) {
        warnings.push(`${field.label || 'A selection card'} has no options.`);
      }
      (field.options || []).forEach((option) => {
        if (option.route_step_id && !stepIds.has(option.route_step_id)) {
          warnings.push(`${option.label || 'An option'} routes to a missing slide.`);
        }
      });
    });
  });

  if (!quoteModeEnabled && !hasBudget) warnings.push('No budget picker is marked for the frontend quote summary.');
  if (!hasTimeline) warnings.push('No timeline picker is marked for the frontend quote summary.');
  if (!hasServices) warnings.push('No services picker is marked for the frontend quote summary.');

  return warnings;
}

function App() {
  const config = window.foundationData || {};
  const [theme, setTheme] = useState(() => window.localStorage?.getItem('foundation_admin_theme') || 'light');
  const [steps, setSteps] = useState([emptyStep()]);
  const [settings, setSettings] = useState(null);
  const [activeStepId, setActiveStepId] = useState('');
  const [selectedFieldId, setSelectedFieldId] = useState('');
  const [status, setStatus] = useState({ type: 'idle', message: 'Loading builder data...' });
  const [settingsStatus, setSettingsStatus] = useState({ type: 'idle', message: 'Loading settings...' });
  const [dragState, setDragState] = useState(null);
  const [panel, setPanel] = useState(() => (
    new URLSearchParams(window.location.search).get('foundation_panel') === 'settings' ? 'settings' : 'builder'
  ));

  useEffect(() => {
    let cancelled = false;
    const headers = { 'X-WP-Nonce': config.nonce || '' };
    fetch(`${config.apiUrl || ''}get`, {
      headers,
      credentials: 'same-origin',
    })
      .then((response) => {
        if (!response.ok) throw new Error(`Load failed with ${response.status}`);
        return response.json();
      })
      .then((data) => {
        if (cancelled) return;
        const normalized = normalizeClientSteps(data);
        setSteps(normalized);
        setActiveStepId(normalized[0]?.id || '');
        setSelectedFieldId(normalized[0]?.fields?.[0]?.id || '');
        setStatus({ type: 'success', message: 'Builder synced.' });
      })
      .catch((error) => {
        if (!cancelled) {
          setStatus({ type: 'error', message: error.message || 'Could not load the builder data.' });
        }
      });

    fetch(`${config.apiUrl || ''}settings`, {
      headers: { 'X-WP-Nonce': config.nonce || '' },
      credentials: 'same-origin',
    })
      .then((response) => {
        if (!response.ok) throw new Error(`Load failed with ${response.status}`);
        return response.json();
      })
      .then((data) => {
        if (cancelled) return;
        setSettings(data);
        setSettingsStatus({ type: 'success', message: 'Settings synced.' });
      })
      .catch((error) => {
        if (!cancelled) {
          setSettingsStatus({ type: 'error', message: error.message || 'Could not load settings.' });
        }
      });
    return () => {
      cancelled = true;
    };
  }, [config.apiUrl, config.nonce]);

  useEffect(() => {
    try {
      window.localStorage.setItem('foundation_admin_theme', theme);
    } catch (error) {
      // Theme persistence is a convenience only.
    }
  }, [theme]);

  const activeStepIndex = Math.max(0, steps.findIndex((step) => step.id === activeStepId));
  const activeStep = steps[activeStepIndex] || steps[0];
  const selectedField = activeStep?.fields?.find((field) => field.id === selectedFieldId) || null;
  const warnings = useMemo(() => getSyncWarnings(steps, settings), [steps, settings]);
  const metrics = config.dashboard?.metrics || [];
  const journey = config.dashboard?.journey || [];

  function updateStep(stepId, patch) {
    setSteps((current) => current.map((step) => (step.id === stepId ? { ...step, ...patch } : step)));
  }

  function updateSelectedField(patch) {
    if (!activeStep || !selectedField) return;
    setSteps((current) => current.map((step) => {
      if (step.id !== activeStep.id) return step;
      return {
        ...step,
        fields: step.fields.map((field) => (field.id === selectedField.id ? { ...field, ...patch } : field)),
      };
    }));
  }

  function updateServiceOption(optionIndex, patch) {
    if (!selectedField || selectedField.type !== 'service_card') return;
    updateSelectedField({
      options: selectedField.options.map((option, index) => (index === optionIndex ? { ...option, ...patch } : option)),
    });
  }

  function addStep() {
    const nextStep = emptyStep();
    setSteps((current) => [...current, nextStep]);
    setActiveStepId(nextStep.id);
    setSelectedFieldId('');
    setStatus({ type: 'idle', message: 'New slide added. Remember to save when ready.' });
  }

  function duplicateStep(stepId) {
    const source = steps.find((step) => step.id === stepId);
    if (!source) return;
    const copy = {
      ...source,
      id: makeId('step'),
      title: `${source.title || 'Slide'} copy`,
      fields: source.fields.map((field) => ({ ...field, id: makeId('field') })),
    };
    setSteps((current) => {
      const index = current.findIndex((step) => step.id === stepId);
      const next = [...current];
      next.splice(index + 1, 0, copy);
      return next;
    });
    setActiveStepId(copy.id);
    setSelectedFieldId(copy.fields[0]?.id || '');
  }

  function removeStep(stepId) {
    if (steps.length === 1) {
      setStatus({ type: 'error', message: 'Keep at least one slide in the calculator.' });
      return;
    }
    setSteps((current) => {
      const next = current.filter((step) => step.id !== stepId);
      const fallback = next[Math.max(0, activeStepIndex - 1)] || next[0];
      setActiveStepId(fallback.id);
      setSelectedFieldId(fallback.fields[0]?.id || '');
      return next.map((step) => ({
        ...step,
        fields: step.fields.map((field) => {
          if (field.type !== 'service_card') return field;
          return {
            ...field,
            options: field.options.map((option) => (
              option.route_step_id === stepId ? { ...option, route_step_id: '' } : option
            )),
          };
        }),
      }));
    });
  }

  function addFieldFromBlueprint(blueprint) {
    if (!activeStep) return;
    const field = createField(blueprint.type, blueprint);
    setSteps((current) => current.map((step) => (
      step.id === activeStep.id ? { ...step, fields: [...step.fields, field] } : step
    )));
    setSelectedFieldId(field.id);
    setStatus({ type: 'idle', message: `${typeLabels[field.type]} added to ${activeStep.title || 'this slide'}.` });
  }

  function removeField(fieldId) {
    if (!activeStep) return;
    setSteps((current) => current.map((step) => (
      step.id === activeStep.id ? { ...step, fields: step.fields.filter((field) => field.id !== fieldId) } : step
    )));
    setSelectedFieldId('');
  }

  function moveField(fromIndex, toIndex) {
    if (!activeStep || toIndex < 0 || toIndex >= activeStep.fields.length) return;
    setSteps((current) => current.map((step) => (
      step.id === activeStep.id ? { ...step, fields: reorder(step.fields, fromIndex, toIndex) } : step
    )));
  }

  function moveStep(fromIndex, toIndex) {
    if (toIndex < 0 || toIndex >= steps.length) return;
    setSteps((current) => reorder(current, fromIndex, toIndex));
  }

  function addOption() {
    if (!selectedField || selectedField.type !== 'service_card') return;
    updateSelectedField({
      options: [...selectedField.options, { label: 'New option', price: 0, route_step_id: '' }],
    });
  }

  function removeOption(index) {
    if (!selectedField || selectedField.type !== 'service_card') return;
    updateSelectedField({ options: selectedField.options.filter((_, itemIndex) => itemIndex !== index) });
  }

  function moveOption(index, direction) {
    if (!selectedField || selectedField.type !== 'service_card') return;
    const toIndex = index + direction;
    if (toIndex < 0 || toIndex >= selectedField.options.length) return;
    updateSelectedField({ options: reorder(selectedField.options, index, toIndex) });
  }

  function saveBuilder() {
    setStatus({ type: 'idle', message: 'Saving to WordPress...' });
    fetch(`${config.apiUrl || ''}save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce || '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ form_data: steps }),
    })
      .then((response) => {
        if (!response.ok) throw new Error(`Save failed with ${response.status}`);
        return response.json();
      })
      .then(() => setStatus({ type: 'success', message: 'Saved to frontend.' }))
      .catch((error) => setStatus({ type: 'error', message: error.message || 'Save failed.' }));
  }

  function saveSettings() {
    if (!settings) return;
    setSettingsStatus({ type: 'idle', message: 'Saving settings...' });
    fetch(`${config.apiUrl || ''}settings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce || '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ settings }),
    })
      .then((response) => {
        if (!response.ok) throw new Error(`Save failed with ${response.status}`);
        return response.json();
      })
      .then((data) => {
        setSettings(data.settings || settings);
        setSettingsStatus({ type: 'success', message: 'Settings saved.' });
      })
      .catch((error) => setSettingsStatus({ type: 'error', message: error.message || 'Settings save failed.' }));
  }

  function exportJson() {
    const blob = new Blob([JSON.stringify(steps, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = 'foundation-project-calculator-form.json';
    anchor.click();
    URL.revokeObjectURL(url);
  }

  function importJson(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const normalized = normalizeClientSteps(JSON.parse(reader.result));
        setSteps(normalized);
        setActiveStepId(normalized[0]?.id || '');
        setSelectedFieldId(normalized[0]?.fields?.[0]?.id || '');
        setStatus({ type: 'success', message: 'JSON imported. Review it, then save to publish it to the calculator.' });
      } catch (error) {
        setStatus({ type: 'error', message: 'That JSON file could not be imported.' });
      }
    };
    reader.readAsText(file);
    event.target.value = '';
  }

  return (
    <div className={classNames('foundation-app-root', theme === 'dark' ? 'fp-theme-dark' : 'fp-theme-light')}>
      <div className="min-h-[calc(100vh-92px)] rounded-[30px] bg-[var(--fp-bg)] p-4 text-[var(--fp-text)] shadow-soft transition-colors duration-300 md:p-5">
        <Header
          config={config}
          panel={panel}
          setPanel={setPanel}
          theme={theme}
          setTheme={setTheme}
          status={status}
          settingsStatus={settingsStatus}
          onSave={saveBuilder}
          onSaveSettings={saveSettings}
          onExport={exportJson}
          onImport={importJson}
        />

        {panel === 'dashboard' ? (
          <Dashboard metrics={metrics} journey={journey} config={config} warnings={warnings} />
        ) : panel === 'settings' ? (
          <SettingsPanel
            settings={settings}
            setSettings={setSettings}
            settingsStatus={settingsStatus}
            onSaveSettings={saveSettings}
          />
        ) : (
          <main className="grid gap-4 xl:grid-cols-[250px_minmax(0,1fr)]">
            <SlideRail
              steps={steps}
              activeStepId={activeStep?.id || ''}
              setActiveStepId={setActiveStepId}
              setSelectedFieldId={setSelectedFieldId}
              addStep={addStep}
              duplicateStep={duplicateStep}
              removeStep={removeStep}
              moveStep={moveStep}
              dragState={dragState}
              setDragState={setDragState}
            />

            <div className="grid min-w-0 gap-4 2xl:grid-cols-[minmax(0,1fr)_340px]">
              <BuilderCanvas
                activeStep={activeStep}
                activeStepIndex={activeStepIndex}
                selectedFieldId={selectedFieldId}
                setSelectedFieldId={setSelectedFieldId}
                addFieldFromBlueprint={addFieldFromBlueprint}
                removeField={removeField}
                moveField={moveField}
                dragState={dragState}
                setDragState={setDragState}
              />

              <Inspector
                steps={steps}
                activeStep={activeStep}
                selectedField={selectedField}
                updateStep={updateStep}
                updateSelectedField={updateSelectedField}
                updateServiceOption={updateServiceOption}
                addOption={addOption}
                removeOption={removeOption}
                moveOption={moveOption}
                warnings={warnings}
              />
            </div>
          </main>
        )}
      </div>
    </div>
  );
}

function Header({ config, panel, setPanel, theme, setTheme, status, settingsStatus, onSave, onSaveSettings, onExport, onImport }) {
  const activeStatus = panel === 'settings' ? settingsStatus : status;
  const saveLabel = panel === 'settings' ? 'Save settings' : 'Save';
  return (
    <header className="mb-4 overflow-hidden rounded-[24px] border border-[var(--fp-border)] bg-[var(--fp-panel)] shadow-[var(--fp-panel-shadow)]">
      <div className="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-6">
        <div className="flex items-center gap-4">
          {config.logoUrl ? (
            <img className="h-11 w-11 object-contain drop-shadow-md" src={config.logoUrl} alt="" />
          ) : null}
          <div>
            <p className="m-0 text-[0.72rem] font-black tracking-[0.08em] text-[var(--fp-accent)]">Foundation</p>
            <h1 className="m-0 text-xl font-black tracking-[-0.02em] text-[var(--fp-heading)] md:text-2xl">Project calculator</h1>
          </div>
        </div>
        <div className="flex flex-wrap items-center gap-2 lg:justify-end">
          <button className="fp-button fp-button-ghost fp-button-compact" type="button" onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}>
            {theme === 'dark' ? 'Light' : 'Dark'}
          </button>
          <span className="rounded-full bg-[var(--fp-chip)] px-3 py-1 text-xs font-black text-[var(--fp-chip-text)]">v{config.pluginVersion || '1.3.0'}</span>
        </div>
      </div>
      <div className="fp-header-bottom">
        <div className={classNames('fp-status', activeStatus.type === 'success' && 'is-success', activeStatus.type === 'error' && 'is-error')} role="status" aria-live="polite">
          {activeStatus.message}
        </div>
        <div className="flex justify-center">
          <nav className="fp-segmented" aria-label="Foundation admin sections">
            <button className={classNames('fp-tab', panel === 'dashboard' && 'is-active')} type="button" onClick={() => setPanel('dashboard')}>Dashboard</button>
            <button className={classNames('fp-tab', panel === 'builder' && 'is-active')} type="button" onClick={() => setPanel('builder')}>Builder</button>
            <button className={classNames('fp-tab', panel === 'settings' && 'is-active')} type="button" onClick={() => setPanel('settings')}>Settings</button>
          </nav>
        </div>
        <div className="flex flex-wrap justify-end gap-2">
          {panel === 'builder' ? (
            <>
              <button className="fp-button fp-button-ghost fp-button-compact" type="button" onClick={onExport}>Export</button>
              <label className="fp-button fp-button-ghost fp-button-compact cursor-pointer">
                Import
                <input className="sr-only" type="file" accept="application/json,.json" onChange={onImport} />
              </label>
            </>
          ) : null}
          {panel !== 'dashboard' ? (
            <button className="fp-button fp-button-primary fp-button-compact" type="button" onClick={panel === 'settings' ? onSaveSettings : onSave}>{saveLabel}</button>
          ) : null}
        </div>
      </div>
    </header>
  );
}

function SettingsPanel({ settings, setSettings, settingsStatus, onSaveSettings }) {
  function updateSetting(key, value) {
    setSettings((current) => ({ ...(current || {}), [key]: value }));
  }

  if (!settings) {
    return (
      <main className="fp-panel p-5 md:p-6">
        <p className="fp-kicker">Settings</p>
        <h2 className="fp-heading">Loading settings</h2>
        <p className="m-0 mt-3 text-sm leading-6 text-[var(--fp-muted)]">{settingsStatus.message}</p>
      </main>
    );
  }

  return (
    <main className="fp-panel overflow-hidden">
      <div className="border-b border-[var(--fp-border)] p-5 md:p-6">
        <p className="fp-kicker">Settings</p>
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="fp-heading">Settings</h2>
            <p className="m-0 mt-2 text-sm leading-6 text-[var(--fp-muted)]">Email, branding, uploads, and follow-up links.</p>
          </div>
          <button className="fp-button fp-button-primary fp-button-compact" type="button" onClick={onSaveSettings}>Save settings</button>
        </div>
      </div>
      <div className="grid gap-4 p-4 md:p-6 xl:grid-cols-2">
        {settingsSections.map((section) => (
          <section className="fp-settings-section" key={section.title}>
            <div className="mb-4">
              <h3 className="m-0 text-lg font-black text-[var(--fp-heading)]">{section.title}</h3>
              <p className="m-0 mt-1 text-sm leading-6 text-[var(--fp-muted)]">{section.description}</p>
            </div>
            <div className="grid gap-4">
              {section.fields.map((field) => (
                <SettingsField
                  field={field}
                  key={field.key}
                  settings={settings}
                  updateSetting={updateSetting}
                />
              ))}
            </div>
          </section>
        ))}
      </div>
    </main>
  );
}

function SettingsField({ field, settings, updateSetting }) {
  const value = settings[field.key] ?? '';
  const id = `foundation-setting-${field.key}`;

  if (field.type === 'checkbox') {
    return (
      <label className="fp-setting-toggle" htmlFor={id}>
        <input
          checked={value === true || value === 1 || value === '1'}
          id={id}
          onChange={(event) => updateSetting(field.key, event.target.checked ? 1 : 0)}
          type="checkbox"
        />
        <span>{field.label}</span>
      </label>
    );
  }

  return (
    <label className="block" htmlFor={id}>
      <span className="mb-2 block text-sm font-black text-[var(--fp-heading)]">{field.label}</span>
      {field.type === 'textarea' ? (
        <textarea
          className="fp-input min-h-24"
          id={id}
          onChange={(event) => updateSetting(field.key, event.target.value)}
          value={value}
        />
      ) : (
        <input
          className="fp-input"
          id={id}
          onChange={(event) => updateSetting(field.key, event.target.value)}
          type={field.type || 'text'}
          value={value}
        />
      )}
      {field.help ? <span className="mt-1 block text-xs leading-5 text-[var(--fp-muted)]">{field.help}</span> : null}
    </label>
  );
}

function Dashboard({ metrics, journey, config, warnings }) {
  return (
    <main className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
      <section className="fp-panel p-5 md:p-6">
        <p className="fp-kicker">Form health</p>
        <h2 className="fp-heading">Overview</h2>
        <div className="mt-5 grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
          {metrics.map((metric) => (
            <div className="rounded-3xl border border-[var(--fp-border)] bg-[var(--fp-surface)] p-5" key={metric.label}>
              <p className="m-0 text-sm font-bold text-[var(--fp-muted)]">{metric.label}</p>
              <p className="m-0 mt-3 text-4xl font-black tracking-[-0.05em] text-[var(--fp-heading)]">{metric.value}</p>
              <p className="m-0 mt-3 text-sm text-[var(--fp-muted)]">{metric.note}</p>
            </div>
          ))}
        </div>
      </section>
      <aside className="fp-panel p-5 md:p-6">
        <p className="fp-kicker">Frontend sync</p>
        <h2 className="fp-heading">Sync checks</h2>
        <div className="mt-5 space-y-3">
          {warnings.length ? warnings.map((warning) => (
            <div className="fp-warning" key={warning}>{warning}</div>
          )) : <div className="fp-success">Service picker, timeline, and route targets look synced.</div>}
        </div>
        <div className="mt-6 rounded-3xl border border-[var(--fp-border)] bg-[var(--fp-surface)] p-5">
          <p className="m-0 text-sm font-black text-[var(--fp-heading)]">Journey checkpoints</p>
          <div className="mt-4 space-y-3">
            {journey.map((item) => (
              <div className="flex items-center justify-between gap-4 text-sm" key={item.label}>
                <span className="font-bold text-[var(--fp-muted)]">{item.label}</span>
                <span className="font-black text-[var(--fp-heading)]">{item.value} / {item.percent}</span>
              </div>
            ))}
          </div>
        </div>
        <div className="mt-6 rounded-3xl border border-[var(--fp-border)] bg-[var(--fp-surface)] p-5">
          <p className="m-0 text-sm font-black text-[var(--fp-heading)]">Latest notes</p>
          <p className="m-0 mt-3 text-sm leading-6 text-[var(--fp-muted)]">Failure: {config.dashboard?.last_failure || 'No failure logged.'}</p>
          <p className="m-0 mt-2 text-sm leading-6 text-[var(--fp-muted)]">Draft: {config.dashboard?.last_saved_draft || 'No draft saved.'}</p>
        </div>
      </aside>
    </main>
  );
}

function SlideRail({ steps, activeStepId, setActiveStepId, setSelectedFieldId, addStep, duplicateStep, removeStep, moveStep, dragState, setDragState }) {
  return (
    <aside className="fp-panel overflow-hidden">
      <div className="flex items-center justify-between gap-3 border-b border-[var(--fp-border)] p-5">
        <div>
          <p className="fp-kicker">Slides</p>
          <h2 className="fp-heading">Slides</h2>
        </div>
        <button className="fp-icon-button" type="button" onClick={addStep} aria-label="Add slide">+</button>
      </div>
      <div className="max-h-[72vh] space-y-3 overflow-auto p-4">
        {steps.map((step, index) => (
          <button
            className={classNames('fp-slide-card', step.id === activeStepId && 'is-active')}
            draggable
            key={step.id}
            type="button"
            onClick={() => {
              setActiveStepId(step.id);
              setSelectedFieldId(step.fields[0]?.id || '');
            }}
            onDragStart={() => setDragState({ type: 'step', fromIndex: index })}
            onDragEnd={() => setDragState(null)}
            onDragOver={(event) => event.preventDefault()}
            onDrop={() => {
              if (dragState?.type === 'step') moveStep(dragState.fromIndex, index);
              setDragState(null);
            }}
          >
            <span className="text-xs font-black uppercase tracking-[0.2em] text-[var(--fp-accent)]">Slide {index + 1}</span>
            <strong>{step.title || 'Untitled slide'}</strong>
            <small>{step.fields.length} question{step.fields.length === 1 ? '' : 's'} {step.is_conditional ? ' / routed' : ''}</small>
            <span className="fp-card-actions" aria-label={`Slide ${index + 1} actions`}>
              <span className="fp-mini-action" title="Move up" onClick={(event) => { event.stopPropagation(); moveStep(index, index - 1); }}>↑</span>
              <span className="fp-mini-action" title="Move down" onClick={(event) => { event.stopPropagation(); moveStep(index, index + 1); }}>↓</span>
              <span className="fp-mini-action" onClick={(event) => { event.stopPropagation(); duplicateStep(step.id); }}>Copy</span>
              <span className="fp-mini-action danger" onClick={(event) => { event.stopPropagation(); removeStep(step.id); }}>Delete</span>
            </span>
          </button>
        ))}
      </div>
    </aside>
  );
}

function BuilderCanvas({ activeStep, activeStepIndex, selectedFieldId, setSelectedFieldId, addFieldFromBlueprint, removeField, moveField, dragState, setDragState }) {
  return (
    <section className="fp-panel min-w-0 overflow-hidden">
      <div className="border-b border-[var(--fp-border)] p-5 md:p-6">
        <p className="fp-kicker">Slide {activeStepIndex + 1}</p>
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 className="fp-heading">{activeStep?.title || 'Untitled slide'}</h2>
            <p className="m-0 mt-2 text-sm leading-6 text-[var(--fp-muted)]">{activeStep?.subtitle || 'No helper copy set.'}</p>
          </div>
        </div>
      </div>

      <div className="border-b border-[var(--fp-border)] p-4 md:p-5">
        <Toolbox addFieldFromBlueprint={addFieldFromBlueprint} setDragState={setDragState} />
      </div>
      <div>
        <div className="min-h-[620px] space-y-4 p-4 md:p-6">
          {!activeStep?.fields?.length ? (
            <div
              className="fp-empty"
              onDragOver={(event) => event.preventDefault()}
              onDrop={() => {
                if (dragState?.type === 'palette') addFieldFromBlueprint(dragState.blueprint);
                setDragState(null);
              }}
            >
              <p className="m-0 text-lg font-black text-[var(--fp-heading)]">Add a question</p>
              <p className="m-0 mt-2 text-sm leading-6 text-[var(--fp-muted)]">Choose a question type above.</p>
            </div>
          ) : null}
          {activeStep?.fields?.map((field, index) => (
            <FieldPreview
              field={field}
              index={index}
              key={field.id}
              selected={selectedFieldId === field.id}
              setSelectedFieldId={setSelectedFieldId}
              removeField={removeField}
              moveField={moveField}
              dragState={dragState}
              setDragState={setDragState}
              addFieldFromBlueprint={addFieldFromBlueprint}
            />
          ))}
        </div>
      </div>
    </section>
  );
}

function Toolbox({ addFieldFromBlueprint, setDragState }) {
  return (
    <div>
      <p className="fp-kicker">Add question</p>
      <div className="mt-3 grid gap-3 xl:grid-cols-2">
        {Object.entries(groupedBlueprints).map(([group, items]) => (
          <div key={group}>
            <h3 className="m-0 mb-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--fp-muted)]">{group.replace(' and ', ' + ')}</h3>
            <div className="flex flex-wrap gap-2">
              {items.map((item) => (
                <button
                  className="fp-tool"
                  draggable
                  key={`${item.type}-${item.variant || 'default'}`}
                  type="button"
                  onClick={() => addFieldFromBlueprint(item)}
                  onDragStart={(event) => {
                    event.dataTransfer.effectAllowed = 'copy';
                    setDragState({ type: 'palette', blueprint: item });
                  }}
                  onDragEnd={() => setDragState(null)}
                >
                  <strong>{item.label}</strong>
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function FieldPreview({ field, index, selected, setSelectedFieldId, removeField, moveField, dragState, setDragState, addFieldFromBlueprint }) {
  const isService = field.type === 'service_card';
  return (
    <article
      className={classNames('fp-field-card', selected && 'is-selected')}
      draggable
      onClick={() => setSelectedFieldId(field.id)}
      onDragStart={() => setDragState({ type: 'field', fromIndex: index })}
      onDragEnd={() => setDragState(null)}
      onDragOver={(event) => event.preventDefault()}
      onDrop={() => {
        if (dragState?.type === 'field') moveField(dragState.fromIndex, index);
        if (dragState?.type === 'palette') addFieldFromBlueprint(dragState.blueprint);
        setDragState(null);
      }}
    >
      <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
          <span className="fp-field-type">{typeLabels[field.type] || field.type}</span>
          <h3 className="m-0 mt-3 text-lg font-black text-[var(--fp-heading)]">{field.label || 'Untitled question'}</h3>
          {field.helper ? <p className="m-0 mt-2 text-sm leading-6 text-[var(--fp-muted)]">{field.helper}</p> : null}
        </div>
        <div className="flex flex-wrap gap-2">
          <button className="fp-mini-action" type="button" title="Move up" onClick={(event) => { event.stopPropagation(); moveField(index, index - 1); }}>↑</button>
          <button className="fp-mini-action" type="button" title="Move down" onClick={(event) => { event.stopPropagation(); moveField(index, index + 1); }}>↓</button>
          <button className="fp-mini-action danger" type="button" onClick={(event) => { event.stopPropagation(); removeField(field.id); }}>Delete</button>
        </div>
      </div>
      {isService ? (
        <div className="mt-4 flex flex-wrap gap-2">
          {(field.options || []).map((option, optionIndex) => (
            <span className="fp-option-pill" key={`${option.label}-${optionIndex}`}>
              {option.label}
              {parseNumber(option.price, 0) ? ` / GBP ${option.price}` : ''}
              {option.route_step_id ? ' / routed' : ''}
            </span>
          ))}
        </div>
      ) : <FieldMiniPreview field={field} />}
    </article>
  );
}

function FieldMiniPreview({ field }) {
  if (field.type === 'range_slider') {
    return <p className="m-0 mt-4 text-sm text-[var(--fp-muted)]">Range: {field.min} to {field.max} {field.unit} / GBP {field.price_per_unit} per unit</p>;
  }
  if (field.type === 'toggle') {
    return <p className="m-0 mt-4 text-sm text-[var(--fp-muted)]">{field.yes_label} / {field.no_label} / GBP {field.price}</p>;
  }
  if (field.type === 'file_upload') {
    return <p className="m-0 mt-4 text-sm text-[var(--fp-muted)]">Allowed: {field.accept || 'site defaults'} / {field.max_files} file(s)</p>;
  }
  if (field.text) {
    return <p className="m-0 mt-4 text-sm text-[var(--fp-muted)]">{field.text}</p>;
  }
  return null;
}

function Inspector({ steps, activeStep, selectedField, updateStep, updateSelectedField, updateServiceOption, addOption, removeOption, moveOption, warnings }) {
  return (
    <aside className="fp-panel max-h-[82vh] overflow-auto p-5 md:p-6">
      <p className="fp-kicker">Edit</p>
      {selectedField ? (
        <FieldInspector
          steps={steps}
          field={selectedField}
          updateSelectedField={updateSelectedField}
          updateServiceOption={updateServiceOption}
          addOption={addOption}
          removeOption={removeOption}
          moveOption={moveOption}
        />
      ) : (
        <StepInspector step={activeStep} updateStep={updateStep} />
      )}
      <div className="mt-6 border-t border-[var(--fp-border)] pt-5">
        <p className="fp-kicker">Checks</p>
        <div className="mt-3 space-y-3">
          {warnings.length ? warnings.slice(0, 4).map((warning) => <div className="fp-warning" key={warning}>{warning}</div>) : <div className="fp-success">This structure is ready for the frontend.</div>}
          {warnings.length > 4 ? <p className="m-0 text-sm text-[var(--fp-muted)]">{warnings.length - 4} more check(s) on the dashboard.</p> : null}
        </div>
      </div>
    </aside>
  );
}

function StepInspector({ step, updateStep }) {
  if (!step) return null;
  return (
    <div>
      <h2 className="fp-heading">Slide</h2>
      <FieldLabel label="Slide title">
        <input className="fp-input" value={step.title} onChange={(event) => updateStep(step.id, { title: event.target.value })} />
      </FieldLabel>
      <FieldLabel label="Subtitle">
        <textarea className="fp-input min-h-24" value={step.subtitle} onChange={(event) => updateStep(step.id, { subtitle: event.target.value })} />
      </FieldLabel>
      <label className="mt-4 flex items-start gap-3 rounded-3xl border border-[var(--fp-border)] bg-[var(--fp-surface)] p-4">
        <input type="checkbox" checked={Boolean(step.is_conditional)} onChange={(event) => updateStep(step.id, { is_conditional: event.target.checked })} />
        <span>
          <strong className="block text-sm text-[var(--fp-heading)]">Conditional slide</strong>
          <span className="block text-sm leading-6 text-[var(--fp-muted)]">Hide this slide unless a service option routes to it.</span>
        </span>
      </label>
    </div>
  );
}

function FieldInspector({ steps, field, updateSelectedField, updateServiceOption, addOption, removeOption, moveOption }) {
  return (
    <div>
      <h2 className="fp-heading">Question</h2>
      <FieldLabel label="Question label">
        <input className="fp-input" value={field.label} onChange={(event) => updateSelectedField({ label: event.target.value })} />
      </FieldLabel>
      <FieldLabel label="Helper text">
        <textarea className="fp-input min-h-20" value={field.helper || ''} onChange={(event) => updateSelectedField({ helper: event.target.value })} />
      </FieldLabel>
      {['text_input', 'rich_text'].includes(field.type) ? (
        <FieldLabel label="Placeholder">
          <input className="fp-input" value={field.placeholder || ''} onChange={(event) => updateSelectedField({ placeholder: event.target.value })} />
        </FieldLabel>
      ) : null}
      {['section_title', 'description'].includes(field.type) ? (
        <FieldLabel label="Display text">
          <textarea className="fp-input min-h-24" value={field.text || ''} onChange={(event) => updateSelectedField({ text: event.target.value })} />
        </FieldLabel>
      ) : null}
      {!['section_title', 'description', 'divider'].includes(field.type) ? (
        <label className="mt-4 flex items-center gap-3 text-sm font-bold text-[var(--fp-heading)]">
          <input type="checkbox" checked={Boolean(field.required)} onChange={(event) => updateSelectedField({ required: event.target.checked })} />
          Required
        </label>
      ) : null}

      {field.type === 'service_card' ? (
        <ServiceInspector
          steps={steps}
          field={field}
          updateSelectedField={updateSelectedField}
          updateServiceOption={updateServiceOption}
          addOption={addOption}
          removeOption={removeOption}
          moveOption={moveOption}
        />
      ) : null}
      {field.type === 'range_slider' ? <RangeInspector field={field} updateSelectedField={updateSelectedField} /> : null}
      {field.type === 'toggle' ? <ToggleInspector field={field} updateSelectedField={updateSelectedField} /> : null}
      {field.type === 'file_upload' ? <UploadInspector field={field} updateSelectedField={updateSelectedField} /> : null}
    </div>
  );
}

function ServiceInspector({ steps, field, updateSelectedField, updateServiceOption, addOption, removeOption, moveOption }) {
  return (
    <div className="mt-6 border-t border-[var(--fp-border)] pt-5">
      <p className="fp-kicker">Choices</p>
      <div className="grid gap-3 md:grid-cols-2">
        <FieldLabel label="Card group">
          <select className="fp-input" value={field.variant || 'services'} onChange={(event) => updateSelectedField({ variant: event.target.value })}>
            {variantOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
          </select>
        </FieldLabel>
        <FieldLabel label="Frontend role">
          <select className="fp-input" value={field.role || ''} onChange={(event) => updateSelectedField({ role: event.target.value })}>
            {roleOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
          </select>
        </FieldLabel>
      </div>
      <div className="mt-5 flex items-center justify-between gap-3">
        <h3 className="m-0 text-sm font-black tracking-[0.04em] text-[var(--fp-muted)]">Options</h3>
        <button className="fp-button fp-button-ghost" type="button" onClick={addOption}>Add option</button>
      </div>
      <div className="mt-3 space-y-3">
        {(field.options || []).map((option, index) => (
          <div className="rounded-3xl border border-[var(--fp-border)] bg-[var(--fp-surface)] p-4" key={`${field.id}-${index}`}>
            <div className="grid gap-3">
              <FieldLabel label={`Option ${index + 1} label`}>
                <input className="fp-input" value={option.label || ''} onChange={(event) => updateServiceOption(index, { label: event.target.value })} />
              </FieldLabel>
              <div className="grid gap-3 md:grid-cols-2">
                <FieldLabel label="Price add-on">
                  <input className="fp-input" type="number" step="0.01" value={option.price ?? 0} onChange={(event) => updateServiceOption(index, { price: parseNumber(event.target.value, 0) })} />
                </FieldLabel>
                <FieldLabel label="Route to slide">
                  <select className="fp-input" value={option.route_step_id || ''} onChange={(event) => updateServiceOption(index, { route_step_id: event.target.value })}>
                    <option value="">No route</option>
                    {steps.map((step) => <option key={step.id} value={step.id}>{step.title || step.id}</option>)}
                  </select>
                </FieldLabel>
              </div>
              <div className="flex flex-wrap gap-2">
                <button className="fp-mini-action" type="button" onClick={() => moveOption(index, -1)}>Up</button>
                <button className="fp-mini-action" type="button" onClick={() => moveOption(index, 1)}>Down</button>
                <button className="fp-mini-action danger" type="button" onClick={() => removeOption(index)}>Remove option</button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function RangeInspector({ field, updateSelectedField }) {
  return (
    <div className="mt-6 grid gap-3 border-t border-[var(--fp-border)] pt-5 md:grid-cols-2">
      <FieldLabel label="Minimum"><input className="fp-input" type="number" value={field.min ?? 1} onChange={(event) => updateSelectedField({ min: parseNumber(event.target.value, 1) })} /></FieldLabel>
      <FieldLabel label="Maximum"><input className="fp-input" type="number" value={field.max ?? 10} onChange={(event) => updateSelectedField({ max: parseNumber(event.target.value, 10) })} /></FieldLabel>
      <FieldLabel label="Step"><input className="fp-input" type="number" value={field.step ?? 1} onChange={(event) => updateSelectedField({ step: parseNumber(event.target.value, 1) })} /></FieldLabel>
      <FieldLabel label="Unit"><input className="fp-input" value={field.unit || ''} onChange={(event) => updateSelectedField({ unit: event.target.value })} /></FieldLabel>
      <FieldLabel label="Price per unit"><input className="fp-input" type="number" step="0.01" value={field.price_per_unit ?? 0} onChange={(event) => updateSelectedField({ price_per_unit: parseNumber(event.target.value, 0) })} /></FieldLabel>
    </div>
  );
}

function ToggleInspector({ field, updateSelectedField }) {
  return (
    <div className="mt-6 grid gap-3 border-t border-[var(--fp-border)] pt-5 md:grid-cols-2">
      <FieldLabel label="Yes label"><input className="fp-input" value={field.yes_label || ''} onChange={(event) => updateSelectedField({ yes_label: event.target.value })} /></FieldLabel>
      <FieldLabel label="No label"><input className="fp-input" value={field.no_label || ''} onChange={(event) => updateSelectedField({ no_label: event.target.value })} /></FieldLabel>
      <FieldLabel label="Yes price add-on"><input className="fp-input" type="number" step="0.01" value={field.price ?? 0} onChange={(event) => updateSelectedField({ price: parseNumber(event.target.value, 0) })} /></FieldLabel>
    </div>
  );
}

function UploadInspector({ field, updateSelectedField }) {
  return (
    <div className="mt-6 grid gap-3 border-t border-[var(--fp-border)] pt-5 md:grid-cols-2">
      <FieldLabel label="Allowed extensions"><input className="fp-input" value={field.accept || ''} onChange={(event) => updateSelectedField({ accept: event.target.value })} /></FieldLabel>
      <FieldLabel label="Max files"><input className="fp-input" type="number" value={field.max_files ?? 5} onChange={(event) => updateSelectedField({ max_files: parseNumber(event.target.value, 5) })} /></FieldLabel>
      <FieldLabel label="Max size per file MB"><input className="fp-input" type="number" value={field.max_file_size_mb ?? 10} onChange={(event) => updateSelectedField({ max_file_size_mb: parseNumber(event.target.value, 10) })} /></FieldLabel>
    </div>
  );
}

function FieldLabel({ label, children }) {
  return (
    <label className="mt-4 block">
      <span className="mb-2 block text-sm font-black text-[var(--fp-heading)]">{label}</span>
      {children}
    </label>
  );
}

function boot() {
  const root = document.getElementById('foundation-admin-app');
  if (!root) return;
  createRoot(root).render(<App />);
}

boot();
