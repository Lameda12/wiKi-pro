// Reading preferences and table-of-contents scrollspy. No dependencies.

const STORAGE_KEY = 'wiki:prefs';

const OPTIONS = {
  theme: ['auto', 'light', 'dark', 'sepia', 'contrast'],
  size: ['small', 'medium', 'large', 'xlarge'],
  width: ['narrow', 'wide'],
};

const DEFAULTS = { theme: 'auto', size: 'medium', width: 'narrow' };

function loadPrefs() {
  try {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}');
    const prefs = { ...DEFAULTS };
    for (const key of Object.keys(OPTIONS)) {
      if (OPTIONS[key].includes(stored?.[key])) prefs[key] = stored[key];
    }
    return prefs;
  } catch {
    return { ...DEFAULTS };
  }
}

function savePrefs(prefs) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
  } catch {
    // Private mode or storage disabled: the choice still applies for this page view.
  }
}

function applyPrefs(prefs) {
  Object.assign(document.documentElement.dataset, prefs);
}

function syncButtons(prefs) {
  for (const button of document.querySelectorAll('[data-rc]')) {
    button.setAttribute('aria-pressed', String(prefs[button.dataset.rc] === button.dataset.value));
  }
}

function initReadingControls() {
  const trigger = document.getElementById('rc-trigger');
  const dialog = document.getElementById('reading-controls');
  const prefs = loadPrefs();

  applyPrefs(prefs);
  syncButtons(prefs);

  if (!trigger || !dialog || typeof dialog.showModal !== 'function') return;

  trigger.addEventListener('click', () => {
    dialog.showModal();
    trigger.setAttribute('aria-expanded', 'true');
  });

  // Fires for Escape, the Done button, and backdrop clicks alike.
  dialog.addEventListener('close', () => {
    trigger.setAttribute('aria-expanded', 'false');
    trigger.focus();
  });

  // A click whose target is the dialog itself but lands outside its box hit the backdrop.
  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const box = dialog.getBoundingClientRect();
    const inside =
      event.clientX >= box.left && event.clientX <= box.right &&
      event.clientY >= box.top && event.clientY <= box.bottom;
    if (!inside) dialog.close();
  });

  for (const button of dialog.querySelectorAll('[data-rc]')) {
    button.addEventListener('click', () => {
      prefs[button.dataset.rc] = button.dataset.value;
      savePrefs(prefs);
      applyPrefs(prefs);
      syncButtons(prefs);
    });
  }
}

function initScrollspy() {
  const links = new Map(
    [...document.querySelectorAll('.toc a[href^="#"]')].map((a) => [a.getAttribute('href').slice(1), a]),
  );
  const headings = [...document.querySelectorAll('.prose h2[id], .prose h3[id]')];

  if (links.size === 0 || headings.length === 0 || !('IntersectionObserver' in window)) return;

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        for (const link of links.values()) link.classList.remove('is-active');
        links.get(entry.target.id)?.classList.add('is-active');
      }
    },
    { rootMargin: '0px 0px -70% 0px' },
  );

  for (const heading of headings) observer.observe(heading);
}

// Footnote popovers: intentionally skipped for v1.

initReadingControls();
initScrollspy();
