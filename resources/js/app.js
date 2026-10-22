/**
 * Skooline UI behaviour — small, dependency-free helpers wired with data attributes.
 */

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------ Drawers
 * <button data-drawer-open="#menu">  opens <div id="menu" class="drawer drawer-right">
 * Anything with [data-drawer-close] inside the drawer closes it, as do Escape,
 * the backdrop and any link inside it.
 */
let lastOpener = null;

const openDrawer = (drawer, opener) => {
    drawer.setAttribute('data-open', '');
    drawer.removeAttribute('aria-hidden');
    document.body.classList.add('overflow-hidden');
    document.querySelectorAll(`[data-drawer-open="#${drawer.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'true'));
    lastOpener = opener;
    setTimeout(() => drawer.querySelector('[data-drawer-close], a, button')?.focus({ preventScroll: true }), 60);
};

const closeDrawer = (drawer) => {
    if (!drawer.hasAttribute('data-open')) return;
    drawer.removeAttribute('data-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    document.querySelectorAll(`[data-drawer-open="#${drawer.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'false'));
    lastOpener?.focus({ preventScroll: true });
    lastOpener = null;
};

document.querySelectorAll('.drawer').forEach((d) => d.setAttribute('aria-hidden', 'true'));

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-drawer-open]');
    if (opener) {
        const drawer = document.querySelector(opener.dataset.drawerOpen);
        if (drawer) {
            drawer.hasAttribute('data-open') ? closeDrawer(drawer) : openDrawer(drawer, opener);
        }
        return;
    }

    const insideDrawer = event.target.closest('.drawer[data-open]');
    if (insideDrawer && (event.target.closest('[data-drawer-close]') || event.target.closest('a[href]'))) {
        closeDrawer(insideDrawer);
    }
});

// Close an open drawer when the screen grows past the breakpoint where it is hidden.
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) document.querySelectorAll('.drawer[data-open]').forEach(closeDrawer);
});

/* ------------------------------------------------------------------ Toggles & menus */
document.addEventListener('click', (event) => {
    // Toggle any element: <button data-toggle="#target"> (adds/removes [hidden])
    const toggle = event.target.closest('[data-toggle]');
    if (toggle) {
        const target = document.querySelector(toggle.dataset.toggle);
        if (target) {
            const open = target.hasAttribute('hidden');
            target.toggleAttribute('hidden', !open);
            toggle.setAttribute('aria-expanded', String(open));
            if (open) target.querySelector('input, select, textarea')?.focus();
        }
    }

    // Dropdown menus: <button data-menu="#list">; clicking elsewhere closes them.
    const menuButton = event.target.closest('[data-menu]');
    document.querySelectorAll('[data-menu]').forEach((button) => {
        const list = document.querySelector(button.dataset.menu);
        if (!list) return;
        const shouldOpen = button === menuButton && list.hasAttribute('hidden');
        if (button !== menuButton && list.contains(event.target)) return;
        list.toggleAttribute('hidden', !shouldOpen);
        button.setAttribute('aria-expanded', String(shouldOpen));
    });

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

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.drawer[data-open]').forEach(closeDrawer);
    document.querySelectorAll('[data-menu]').forEach((button) => {
        document.querySelector(button.dataset.menu)?.setAttribute('hidden', '');
        button.setAttribute('aria-expanded', 'false');
    });
});

// Confirm before submitting: <form data-confirm="Are you sure?">
document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

/* ------------------------------------------------------------------ Effects */

// Header gets a shadow once the page is scrolled: <header data-scroll-header>
const scrollHeader = document.querySelector('[data-scroll-header]');
if (scrollHeader) {
    const update = () => scrollHeader.toggleAttribute('data-scrolled', window.scrollY > 8);
    window.addEventListener('scroll', update, { passive: true });
    update();
}

// Count numbers up when they come into view: <span data-count="1248">1,248</span>
const animateCount = (el) => {
    const target = parseFloat(el.dataset.count);
    const suffix = el.dataset.countSuffix || '';
    if (Number.isNaN(target) || reducedMotion) return;
    const start = performance.now();
    const duration = 1200;
    const step = (now) => {
        const p = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased).toLocaleString('en') + suffix;
        if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
};

// Fade elements in as they scroll into view: <div data-reveal style="--delay: 100ms">
if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                entry.target.querySelectorAll('[data-count]').forEach(animateCount);
                if (entry.target.matches('[data-count]')) animateCount(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );
    document.querySelectorAll('[data-reveal], [data-count]').forEach((el) => observer.observe(el));
} else {
    document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible'));
}

/* ------------------------------------------------------------------ Currency picker
 * Prices: <span data-price="4500">PKR 4,500</span>
 * Options come from <script type="application/json" id="currency-data">.
 */
const currencyData = document.getElementById('currency-data');
if (currencyData) {
    const { base, currencies } = JSON.parse(currencyData.textContent);
    const byCode = Object.fromEntries(currencies.map((c) => [c.code, c]));
    const regionCurrency = {
        US: 'USD', GB: 'GBP', CA: 'CAD', AU: 'AUD', NZ: 'NZD', IN: 'INR', PK: 'PKR', BD: 'BDT', LK: 'LKR', NP: 'NPR',
        AE: 'AED', SA: 'SAR', QA: 'QAR', KW: 'KWD', OM: 'OMR', BH: 'BHD', TR: 'TRY', EG: 'EGP', NG: 'NGN', KE: 'KES',
        ZA: 'ZAR', GH: 'GHS', MY: 'MYR', SG: 'SGD', ID: 'IDR', PH: 'PHP', CN: 'CNY', JP: 'JPY', ZW: 'USD',
        DE: 'EUR', FR: 'EUR', IT: 'EUR', ES: 'EUR', NL: 'EUR', IE: 'EUR', BE: 'EUR', AT: 'EUR', PT: 'EUR', FI: 'EUR', GR: 'EUR',
    };

    const format = (amount, code) => {
        const c = byCode[code];
        const value = amount * c.rate;
        const digits = value > 0 && value < 10 ? Math.min(2, c.decimals) : 0;
        try {
            return new Intl.NumberFormat('en', { style: 'currency', currency: code, currencyDisplay: 'code', minimumFractionDigits: digits, maximumFractionDigits: digits })
                .format(value).replace(/ /g, ' ');
        } catch {
            return `${code} ${value.toFixed(digits)}`;
        }
    };

    const apply = (code) => {
        if (!byCode[code]) code = base;
        document.querySelectorAll('[data-price]').forEach((el) => (el.textContent = format(parseFloat(el.dataset.price), code)));
        document.querySelectorAll('[data-currency-label]').forEach((el) => (el.textContent = code));
        document.querySelectorAll('[data-currency]').forEach((el) => el.setAttribute('aria-checked', String(el.dataset.currency === code)));
        document.querySelectorAll('[data-currency-note]').forEach((el) => el.toggleAttribute('hidden', code === base));
    };

    let saved = null;
    try { saved = localStorage.getItem('skooline.currency'); } catch {}
    if (!saved) {
        const region = (navigator.language || '').split('-')[1];
        saved = regionCurrency[region] && byCode[regionCurrency[region]] ? regionCurrency[region] : base;
    }
    apply(saved);

    document.addEventListener('click', (event) => {
        const option = event.target.closest('[data-currency]');
        if (!option) return;
        apply(option.dataset.currency);
        try { localStorage.setItem('skooline.currency', option.dataset.currency); } catch {}
    });
}

/* ------------------------------------------------------------------ Forms */

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

// Live preview text: <span data-preview-of="#slug" data-fallback="your-school">
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

// Website "School login": send visitors to {APP_URL}/{school}/login
document.querySelectorAll('[data-school-finder]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = form.querySelector('input');
        const slug = input.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
        if (!slug) return input.focus();
        window.location.href = `${form.dataset.base || window.location.origin}/${slug}/login`;
    });
});
