# Task 01: Design System & Foundation Setup

## Context
You are working on **Lucky Boss Portal** — an AI-powered recruitment platform (Laravel 12 + Tailwind CSS 4 + MySQL). The project is at `c:\Luckyboss\luckyboss-app`.

The current codebase has Tailwind CSS 4 installed but almost completely unused. Instead, views use massive inline `<style>` blocks with inconsistent fonts (Arial, Georgia, Times New Roman, Instrument Sans). We need to establish a professional design system that ALL views will use.

## Brand Identity
- **Primary Color**: `#031f49` (dark navy)
- **Secondary Color**: `#18a66a` (emerald green)
- **Accent Blue**: `#2563eb`
- **Body Font**: Inter (clean, modern — used by GitHub, Figma, Linear)
- **Heading Font**: Plus Jakarta Sans (premium, modern)
- **Tagline**: "Growth Partner in Your Hiring Journey"

---

## Step 1: Update `package.json`

**File**: `c:\Luckyboss\luckyboss-app\package.json`

Replace the entire file with:

```json
{
    "$schema": "https://www.schemastore.org/package.json",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "autoprefixer": "^10.4.21",
        "axios": "^1.11.0",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^2.0.0",
        "tailwindcss": "^4.0.0",
        "vite": "^7.0.7"
    },
    "dependencies": {
        "alpinejs": "^3.14.9"
    }
}
```

After editing this file, run:
```bash
cd c:\Luckyboss\luckyboss-app && npm install
```

---

## Step 2: Update `resources/css/app.css`

**File**: `c:\Luckyboss\luckyboss-app\resources\css\app.css`

Replace the ENTIRE file with:

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

/* ─── Google Fonts ────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

/* ─── Tailwind Theme Configuration ────────────────────────── */
@theme {
    /* Font Families */
    --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
    --font-heading: 'Plus Jakarta Sans', 'Inter', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;

    /* Brand Colors */
    --color-primary-50: #eef3ff;
    --color-primary-100: #d9e4ff;
    --color-primary-200: #b3c9ff;
    --color-primary-300: #7aa3ff;
    --color-primary-400: #4a7cf5;
    --color-primary-500: #2563eb;
    --color-primary-600: #1d4ed8;
    --color-primary-700: #1e40af;
    --color-primary-800: #1e3a8a;
    --color-primary-900: #031f49;
    --color-primary-950: #021736;

    --color-secondary-50: #ecfdf5;
    --color-secondary-100: #d1fae5;
    --color-secondary-200: #a7f3d0;
    --color-secondary-300: #6ee7b7;
    --color-secondary-400: #34d399;
    --color-secondary-500: #18a66a;
    --color-secondary-600: #159557;
    --color-secondary-700: #047857;
    --color-secondary-800: #065f46;
    --color-secondary-900: #064e3b;
    --color-secondary-950: #022c22;

    --color-navy: #031f49;
    --color-green: #18a66a;
    --color-accent: #2563eb;

    /* Semantic Colors */
    --color-success: #16a34a;
    --color-warning: #f59e0b;
    --color-danger: #dc2626;
    --color-info: #0ea5e9;

    /* Neutral / Surface Colors */
    --color-surface: #f8fafc;
    --color-surface-raised: #ffffff;
    --color-surface-sunken: #f1f5f9;
    --color-border: #e2e8f0;
    --color-border-strong: #cbd5e1;
    --color-text-primary: #0f172a;
    --color-text-secondary: #475569;
    --color-text-muted: #94a3b8;
    --color-text-inverse: #ffffff;

    /* Spacing */
    --spacing-section: 5rem;
    --spacing-section-sm: 3rem;

    /* Border Radius */
    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
    --radius-xl: 1rem;
    --radius-2xl: 1.5rem;
    --radius-full: 9999px;

    /* Shadows */
    --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
    --shadow-card-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
    --shadow-dropdown: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    --shadow-modal: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --shadow-nav: 0 1px 3px 0 rgba(0, 0, 0, 0.05);

    /* Transitions */
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-normal: 200ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);

    /* Container */
    --container-max: 1280px;
    --container-narrow: 768px;
    --container-wide: 1440px;
}

/* ─── Base Styles ─────────────────────────────────────────── */
@layer base {
    html {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        scroll-behavior: smooth;
    }

    body {
        font-family: var(--font-sans);
        color: var(--color-text-primary);
        background-color: var(--color-surface);
        line-height: 1.6;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-heading);
        font-weight: 700;
        line-height: 1.25;
        color: var(--color-navy);
        letter-spacing: -0.025em;
    }

    h1 { font-size: 2.5rem; }
    h2 { font-size: 2rem; }
    h3 { font-size: 1.5rem; }
    h4 { font-size: 1.25rem; }
    h5 { font-size: 1.125rem; }

    @media (min-width: 768px) {
        h1 { font-size: 3.5rem; }
        h2 { font-size: 2.5rem; }
        h3 { font-size: 1.75rem; }
    }

    a {
        color: inherit;
        text-decoration: none;
        transition: color var(--transition-fast);
    }

    p {
        color: var(--color-text-secondary);
        line-height: 1.7;
    }

    ::selection {
        background-color: var(--color-primary-100);
        color: var(--color-primary-900);
    }

    /* Focus ring for accessibility */
    :focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
        border-radius: var(--radius-sm);
    }

    /* Scrollbar styling */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: var(--color-surface-sunken);
    }
    ::-webkit-scrollbar-thumb {
        background: var(--color-border-strong);
        border-radius: var(--radius-full);
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--color-text-muted);
    }
}

/* ─── Utility Classes ─────────────────────────────────────── */
@utility container-app {
    width: 100%;
    max-width: var(--container-max);
    margin-left: auto;
    margin-right: auto;
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

@utility section-padding {
    padding-top: var(--spacing-section-sm);
    padding-bottom: var(--spacing-section-sm);
}

@media (min-width: 768px) {
    @utility section-padding {
        padding-top: var(--spacing-section);
        padding-bottom: var(--spacing-section);
    }
}

@utility font-heading {
    font-family: var(--font-heading);
}

@utility text-gradient {
    background: linear-gradient(135deg, var(--color-secondary-500), var(--color-primary-500));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

@utility eyebrow {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--color-secondary-500);
}

/* ─── Component Base Styles ───────────────────────────────── */
@layer components {
    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-family: var(--font-sans);
        font-weight: 600;
        font-size: 0.875rem;
        line-height: 1.25rem;
        padding: 0.625rem 1.25rem;
        border-radius: var(--radius-lg);
        border: 1px solid transparent;
        cursor: pointer;
        transition: all var(--transition-normal);
        white-space: nowrap;
        user-select: none;
    }
    .btn:focus-visible {
        outline: 2px solid var(--color-accent);
        outline-offset: 2px;
    }
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-primary {
        background: var(--color-navy);
        color: white;
    }
    .btn-primary:hover:not(:disabled) {
        background: #042a5e;
        box-shadow: 0 4px 12px rgba(3, 31, 73, 0.3);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: var(--color-green);
        color: white;
    }
    .btn-secondary:hover:not(:disabled) {
        background: #159557;
        box-shadow: 0 4px 12px rgba(24, 166, 106, 0.3);
        transform: translateY(-1px);
    }

    .btn-accent {
        background: var(--color-accent);
        color: white;
    }
    .btn-accent:hover:not(:disabled) {
        background: #1d4ed8;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        transform: translateY(-1px);
    }

    .btn-outline {
        background: transparent;
        color: var(--color-navy);
        border-color: var(--color-border-strong);
    }
    .btn-outline:hover:not(:disabled) {
        background: var(--color-surface-sunken);
        border-color: var(--color-navy);
    }

    .btn-ghost {
        background: transparent;
        color: var(--color-text-secondary);
    }
    .btn-ghost:hover:not(:disabled) {
        background: var(--color-surface-sunken);
        color: var(--color-text-primary);
    }

    .btn-danger {
        background: var(--color-danger);
        color: white;
    }
    .btn-danger:hover:not(:disabled) {
        background: #b91c1c;
    }

    .btn-sm {
        font-size: 0.8125rem;
        padding: 0.375rem 0.875rem;
    }
    .btn-lg {
        font-size: 1rem;
        padding: 0.75rem 1.75rem;
    }
    .btn-xl {
        font-size: 1.125rem;
        padding: 1rem 2rem;
        border-radius: var(--radius-xl);
    }

    /* Cards */
    .card {
        background: var(--color-surface-raised);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-card);
        transition: all var(--transition-normal);
    }
    .card:hover {
        box-shadow: var(--shadow-card-hover);
    }
    .card-body {
        padding: 1.5rem;
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--color-border);
        font-family: var(--font-heading);
        font-weight: 600;
    }
    .card-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--color-border);
        background: var(--color-surface);
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
    }

    /* Form Inputs */
    .form-input {
        display: block;
        width: 100%;
        padding: 0.625rem 0.875rem;
        font-size: 0.9375rem;
        line-height: 1.5;
        color: var(--color-text-primary);
        background-color: var(--color-surface-raised);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        transition: all var(--transition-fast);
    }
    .form-input::placeholder {
        color: var(--color-text-muted);
    }
    .form-input:focus {
        outline: none;
        border-color: var(--color-accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .form-input.error {
        border-color: var(--color-danger);
    }
    .form-input.error:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--color-text-primary);
        margin-bottom: 0.375rem;
    }

    .form-error {
        font-size: 0.8125rem;
        color: var(--color-danger);
        margin-top: 0.25rem;
    }

    .form-help {
        font-size: 0.8125rem;
        color: var(--color-text-muted);
        margin-top: 0.25rem;
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: var(--radius-full);
        white-space: nowrap;
    }
    .badge-success { background: #dcfce7; color: #15803d; }
    .badge-warning { background: #fef3c7; color: #b45309; }
    .badge-danger  { background: #fee2e2; color: #dc2626; }
    .badge-info    { background: #dbeafe; color: #2563eb; }
    .badge-neutral { background: #f1f5f9; color: #475569; }
    .badge-primary { background: #eef3ff; color: var(--color-navy); }

    /* Tables */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .data-table thead th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--color-text-muted);
        border-bottom: 2px solid var(--color-border);
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid var(--color-border);
        vertical-align: middle;
    }
    .data-table tbody tr:hover {
        background: var(--color-surface);
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Stats Cards */
    .stat-card {
        background: var(--color-surface-raised);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    .stat-card-icon {
        width: 3rem;
        height: 3rem;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-card-value {
        font-family: var(--font-heading);
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--color-text-primary);
        line-height: 1;
    }
    .stat-card-label {
        font-size: 0.8125rem;
        color: var(--color-text-muted);
        margin-top: 0.25rem;
    }

    /* Sidebar Navigation */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--color-text-secondary);
        border-radius: var(--radius-lg);
        transition: all var(--transition-fast);
    }
    .sidebar-link:hover {
        background: var(--color-surface-sunken);
        color: var(--color-text-primary);
    }
    .sidebar-link.active {
        background: var(--color-primary-50);
        color: var(--color-accent);
        font-weight: 600;
    }
    .sidebar-link svg {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }
}

/* ─── Animations ──────────────────────────────────────────── */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse-soft {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

@keyframes skeleton {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
.animate-slide-down {
    animation: slideDown 0.3s ease-out;
}
.animate-slide-up {
    animation: slideUp 0.3s ease-out;
}

/* Skeleton loading */
.skeleton {
    background: linear-gradient(90deg, var(--color-surface-sunken) 25%, var(--color-border) 50%, var(--color-surface-sunken) 75%);
    background-size: 400px 100%;
    animation: skeleton 1.5s ease-in-out infinite;
    border-radius: var(--radius-md);
}
```

---

## Step 3: Update `resources/js/app.js`

**File**: `c:\Luckyboss\luckyboss-app\resources\js\app.js`

Replace the ENTIRE file with:

```javascript
import './bootstrap';
import Alpine from 'alpinejs';

// ─── Alpine.js Global Data ──────────────────────────────────
Alpine.data('dropdown', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

Alpine.data('modal', () => ({
    show: false,
    open() { this.show = true; document.body.style.overflow = 'hidden'; },
    close() { this.show = false; document.body.style.overflow = ''; },
}));

Alpine.data('tabs', (defaultTab = '') => ({
    activeTab: defaultTab,
    switchTab(tab) { this.activeTab = tab; },
    isActive(tab) { return this.activeTab === tab; },
}));

Alpine.data('toast', () => ({
    toasts: [],
    add(message, type = 'success', duration = 5000) {
        const id = Date.now();
        this.toasts.push({ id, message, type });
        setTimeout(() => this.remove(id), duration);
    },
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    },
}));

Alpine.data('mobileMenu', () => ({
    open: false,
    toggle() { this.open = !this.open; },
}));

// ─── Start Alpine ───────────────────────────────────────────
Alpine.start();
window.Alpine = Alpine;
```

---

## Step 4: Check `resources/js/bootstrap.js`

**File**: `c:\Luckyboss\luckyboss-app\resources\js\bootstrap.js`

Read it first. It should have the Axios setup. If it exists, leave it as-is. If it doesn't exist, create it with:

```javascript
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

---

## Step 5: Verify `vite.config.js`

**File**: `c:\Luckyboss\luckyboss-app\vite.config.js`

The current file is fine. Do NOT modify it — it already has Tailwind CSS plugin and Laravel plugin configured correctly.

---

## Verification

After completing all steps, run these commands:

```bash
cd c:\Luckyboss\luckyboss-app
npm install
npm run build
```

`npm run build` must complete without errors. If there are errors, fix them before marking this task complete.

---

## IMPORTANT RULES
1. Do NOT modify any other files besides the ones listed above
2. Do NOT delete any files
3. Keep ALL existing Blade templates working — we're only changing the CSS/JS foundation
4. The `@import url(...)` for Google Fonts is deliberate — it ensures fonts load on all pages
