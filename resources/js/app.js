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

/* ------------------------------------------------------------------ Page loading feedback
 * A thin bar at the top as soon as a link is clicked or a form is sent, and a spinner on the
 * button that sent it (also stops double submits). Links that download a file: data-no-progress.
 */
const progress = document.createElement('div');
progress.className = 'page-progress';
progress.setAttribute('aria-hidden', 'true');
document.body.append(progress);
let progressTimer;

const startProgress = () => {
    progress.classList.add('is-loading');
    clearTimeout(progressTimer);
    progressTimer = setTimeout(stopProgress, 15000);
};
const stopProgress = () => {
    progress.classList.remove('is-loading');
    document.querySelectorAll('.btn.is-busy').forEach((b) => {
        b.classList.remove('is-busy');
        b.disabled = false;
    });
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if ((link.target && link.target !== '_self') || link.hasAttribute('download') || 'noProgress' in link.dataset) return;
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) return;
    if (url.pathname === window.location.pathname && url.search === window.location.search) return; // same page / #anchor
    startProgress();
});

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented || event.target.target === '_blank') return;
    startProgress();
    const button = event.submitter;
    if (button?.classList.contains('btn')) {
        // Disable after the browser has read the button's name/value.
        setTimeout(() => {
            button.classList.add('is-busy');
            button.disabled = true;
        }, 0);
    }
});

// Back/forward cache restores the old page: clear any loading state.
window.addEventListener('pageshow', stopProgress);

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
    // base: billing currency; currencies: [{code, name, rate, decimals}]; default: "auto" or a code
    const { base, currencies, default: preferred = 'auto' } = JSON.parse(currencyData.textContent);
    const byCode = Object.fromEntries(currencies.map((c) => [c.code, c]));
    const regionCurrency = {
        US: 'USD', GB: 'GBP', CA: 'CAD', AU: 'AUD', NZ: 'NZD', IN: 'INR', PK: 'PKR', BD: 'BDT', LK: 'LKR', NP: 'NPR',
        AE: 'AED', SA: 'SAR', QA: 'QAR', KW: 'KWD', OM: 'OMR', BH: 'BHD', TR: 'TRY', EG: 'EGP', NG: 'NGN', KE: 'KES',
        ZA: 'ZAR', GH: 'GHS', MY: 'MYR', SG: 'SGD', ID: 'IDR', PH: 'PHP', CN: 'CNY', JP: 'JPY', ZW: 'USD',
        DE: 'EUR', FR: 'EUR', IT: 'EUR', ES: 'EUR', NL: 'EUR', IE: 'EUR', BE: 'EUR', AT: 'EUR', PT: 'EUR', FI: 'EUR', GR: 'EUR',
    };

    const formatAmount = (value, code) => {
        const c = byCode[code];
        const digits = value > 0 && value < 10 && !Number.isInteger(value) ? Math.min(2, c.decimals) : 0;
        try {
            return new Intl.NumberFormat('en', { style: 'currency', currency: code, currencyDisplay: 'code', minimumFractionDigits: digits, maximumFractionDigits: digits })
                .format(value).replace(/\u00a0/g, ' ');
        } catch {
            return `${code} ${value.toFixed(digits)}`;
        }
    };

    // A plan's own price for that currency (data-price-usd="29") wins over converting with the rate.
    const priceFor = (el, code) => {
        const fixed = el.dataset[`price${code.charAt(0)}${code.slice(1).toLowerCase()}`];
        return fixed !== undefined && fixed !== '' ? parseFloat(fixed) : parseFloat(el.dataset.price) * byCode[code].rate;
    };

    const apply = (code) => {
        if (!byCode[code]) code = base;
        document.querySelectorAll('[data-price]').forEach((el) => (el.textContent = formatAmount(priceFor(el, code), code)));
        document.querySelectorAll('[data-currency-label]').forEach((el) => (el.textContent = code));
        document.querySelectorAll('[data-currency]').forEach((el) => el.setAttribute('aria-checked', String(el.dataset.currency === code)));
        document.querySelectorAll('[data-currency-note]').forEach((el) => el.toggleAttribute('hidden', code === base));
    };

    let saved = null;
    try { saved = localStorage.getItem('skooline.currency'); } catch {}
    if (!saved || !byCode[saved]) {
        if (preferred !== 'auto' && byCode[preferred]) {
            saved = preferred;
        } else {
            const region = (navigator.language || '').split('-')[1];
            saved = regionCurrency[region] && byCode[regionCurrency[region]] ? regionCurrency[region] : base;
        }
    }
    apply(saved);

    document.addEventListener('click', (event) => {
        const option = event.target.closest('[data-currency]');
        if (!option) return;
        apply(option.dataset.currency);
        try { localStorage.setItem('skooline.currency', option.dataset.currency); } catch {}
    });
}

/* ------------------------------------------------------------------ Saved secrets
 * <button data-reveal-secret="STRIPE_SECRET" data-url="..."> asks for the PIN and shows the full
 * value for 30 seconds next to it (<code data-secret-value="STRIPE_SECRET">).
 */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-reveal-secret]');
    if (!button) return;

    const name = button.dataset.revealSecret;
    const output = document.querySelector(`[data-secret-value="${name}"]`);
    const pin = window.prompt('Enter your PIN to show this value');
    if (!pin || !output) return;

    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ name, pin }),
        });
        const data = await response.json();
        if (!response.ok) return window.alert(data.error || data.message || 'Could not show the value.');

        const masked = output.textContent;
        output.textContent = data.value;
        output.classList.add('select-all');
        setTimeout(() => {
            output.textContent = masked;
            output.classList.remove('select-all');
        }, 30000);
    } catch {
        window.alert('Could not reach the server. Try again.');
    }
});

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

// Photo inputs: show the chosen image straight away: <input type="file" data-photo-input="name">
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-photo-input]');
    const file = input?.files?.[0];
    if (!file) return;
    const name = input.dataset.photoInput;
    const preview = document.querySelector(`[data-photo-preview="${name}"]`);
    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
    document.querySelector(`[data-photo-placeholder="${name}"]`)?.classList.add('hidden');
    const label = document.querySelector(`[data-photo-name="${name}"]`);
    if (label) label.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)} MB${file.size > 2 * 1024 * 1024 ? ' — too large (max 2 MB)' : ''}`;
});

// Portal address availability while typing: <input data-address-check="/register/check-address">
document.querySelectorAll('[data-address-check]').forEach((input) => {
    const status = input.closest('div').parentElement.querySelector('[data-address-status]');
    if (!status) return;
    let timer;
    const check = () => {
        clearTimeout(timer);
        const slug = input.value.trim();
        if (slug.length < 3) return;
        timer = setTimeout(async () => {
            try {
                const response = await fetch(`${input.dataset.addressCheck}?slug=${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' } });
                const { available } = await response.json();
                status.textContent = available ? `✓ ${slug} is available` : `✗ ${slug} is taken or not allowed — try another`;
                status.className = available ? 'hint font-medium text-success' : 'hint font-medium text-danger';
            } catch {
                /* offline: the server checks again on submit */
            }
        }, 350);
    };
    ['input', 'change'].forEach((e) => input.addEventListener(e, check));
});

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
