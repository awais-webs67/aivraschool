/**
 * Skooline UI behaviour — small, dependency-free helpers wired with data attributes.
 */

// Toggle any element: <button data-toggle="#target"> (adds/removes [hidden])
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle]');
    if (toggle) {
        const target = document.querySelector(toggle.dataset.toggle);
        if (target) {
            const open = target.hasAttribute('hidden');
            target.toggleAttribute('hidden', !open);
            toggle.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('overflow-hidden', open && toggle.hasAttribute('data-lock-scroll'));
        }
    }

    // Close mobile drawers when a link inside them is clicked
    const drawerLink = event.target.closest('[data-drawer] a');
    if (drawerLink) {
        drawerLink.closest('[data-drawer]').setAttribute('hidden', '');
        document.body.classList.remove('overflow-hidden');
    }

    // Copy text: <button data-copy="text to copy">
    const copy = event.target.closest('[data-copy]');
    if (copy && navigator.clipboard) {
        navigator.clipboard.writeText(copy.dataset.copy).then(() => {
            const original = copy.innerHTML;
            copy.textContent = 'Copied';
            setTimeout(() => (copy.innerHTML = original), 1500);
        });
    }
});

// Confirm before submitting: <form data-confirm="Are you sure?">
document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('[data-drawer]').forEach((el) => el.setAttribute('hidden', ''));
        document.body.classList.remove('overflow-hidden');
    }
});

// Auto-fill a slug field from a name field: <input data-slug-from="#name">
document.querySelectorAll('[data-slug-from]').forEach((slugInput) => {
    const source = document.querySelector(slugInput.dataset.slugFrom);
    if (!source) return;
    let touched = slugInput.value !== '';
    slugInput.addEventListener('input', () => (touched = slugInput.value !== ''));
    source.addEventListener('input', () => {
        if (touched) return;
        slugInput.value = source.value
            .toLowerCase()
            .normalize('NFKD')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/[\s-]+/g, '-')
            .slice(0, 40)
            .replace(/-+$/, '');
        slugInput.dispatchEvent(new Event('change'));
    });
});

// Live preview text: <span data-preview-of="#subdomain" data-fallback="your-school">
document.querySelectorAll('[data-preview-of]').forEach((preview) => {
    const source = document.querySelector(preview.dataset.previewOf);
    if (!source) return;
    const render = () => (preview.textContent = source.value || preview.dataset.fallback || '');
    ['input', 'change'].forEach((e) => source.addEventListener(e, render));
    render();
});

// Show sections based on a radio/select value: <div data-show-when="billing_mode=paid">
const conditionalBlocks = document.querySelectorAll('[data-show-when]');
const refreshConditional = () => {
    conditionalBlocks.forEach((block) => {
        const [name, values] = block.dataset.showWhen.split('=');
        const field = document.querySelector(`[name="${name}"]:checked, select[name="${name}"]`);
        const visible = field && values.split('|').includes(field.value);
        block.toggleAttribute('hidden', !visible);
        block.querySelectorAll('input, select, textarea').forEach((el) => (el.disabled = !visible));
    });
};
if (conditionalBlocks.length) {
    document.addEventListener('change', refreshConditional);
    refreshConditional();
}

// Website "School login": send visitors to {subdomain}.{domain}/login
document.querySelectorAll('[data-school-finder]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = form.querySelector('input');
        const sub = input.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
        if (!sub) return input.focus();
        const base = new URL(form.dataset.base || window.location.origin);
        window.location.href = `${base.protocol}//${sub}.${form.dataset.domain}${base.port ? ':' + base.port : ''}/login`;
    });
});
