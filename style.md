@import 'tailwindcss';

@import 'tw-animate-css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';

@custom-variant dark (&:is(.dark *));

@theme {
    --font-sans:
        'Instrument Sans', ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
        'Noto Color Emoji';

    --radius-lg: var(--radius);
    --radius-md: calc(var(--radius) - 2px);
    --radius-sm: calc(var(--radius) - 4px);

    --color-background: var(--background);
    --color-foreground: var(--foreground);

    --color-card: var(--card);
    --color-card-foreground: var(--card-foreground);

    --color-popover: var(--popover);
    --color-popover-foreground: var(--popover-foreground);

    --color-primary: var(--primary);
    --color-primary-foreground: var(--primary-foreground);
    --color-support-action: var(--support-action);
    --color-support-action-foreground: var(--support-action-foreground);

    --color-secondary: var(--secondary);
    --color-secondary-foreground: var(--secondary-foreground);

    --color-muted: var(--muted);
    --color-muted-foreground: var(--muted-foreground);

    --color-accent: var(--accent);
    --color-accent-foreground: var(--accent-foreground);

    --color-destructive: var(--destructive);
    --color-destructive-foreground: var(--destructive-foreground);

    --color-border: var(--border);
    --color-input: var(--input);
    --color-ring: var(--ring);

    --color-chart-1: var(--chart-1);
    --color-chart-2: var(--chart-2);
    --color-chart-3: var(--chart-3);
    --color-chart-4: var(--chart-4);
    --color-chart-5: var(--chart-5);

    --color-sidebar: var(--sidebar);
    --color-sidebar-foreground: var(--sidebar-foreground);
    --color-sidebar-primary: var(--sidebar-primary);
    --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
    --color-sidebar-accent: var(--sidebar-accent);
    --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
    --color-sidebar-border: var(--sidebar-border);
    --color-sidebar-ring: var(--sidebar-ring);
}

:root {
    --background: #f0f1f7;
    --foreground: #333335;
    --card: oklch(1 0 0);
    --card-foreground: #333335;
    --popover: oklch(1 0 0);
    --popover-foreground: oklch(0.4355 0.043 279.325);
    --primary: #845adf;
    --primary-foreground: #ffffff;
    --support-action: oklch(0.546 0.245 262.881);
    --support-action-foreground: oklch(1 0 0);
    --secondary: #f3f0fb;
    --secondary-foreground: #6f4ac5;
    --muted: #f7f8fa;
    --muted-foreground: #8c9097;
    --accent: #f3effc;
    --accent-foreground: #6f4ac5;
    --destructive: oklch(0.5505 0.2155 19.8095);
    --destructive-foreground: oklch(1 0 0);
    --border: #e9edf4;
    --input: #dfe4ee;
    --ring: #845adf;
    --chart-1: #845adf;
    --chart-2: #23b7e5;
    --chart-3: #26bf94;
    --chart-4: #f5b849;
    --chart-5: #e6533c;
    --radius: 0.5rem;
    --sidebar: #111c43;
    --sidebar-foreground: #b2bbd4;
    --sidebar-primary: #845adf;
    --sidebar-primary-foreground: #ffffff;
    --sidebar-accent: #202b50;
    --sidebar-accent-foreground: #ffffff;
    --sidebar-border: #263153;
    --sidebar-ring: #a98ae9;
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    --font-serif: Georgia, serif;
    --font-mono: Fira Code, monospace;
    --shadow-color: hsl(240 30% 25%);
    --shadow-opacity: 0.12;
    --shadow-blur: 6px;
    --shadow-spread: 0px;
    --shadow-offset-x: 0px;
    --shadow-offset-y: 4px;
    --letter-spacing: 0em;
    --spacing: 0.25rem;
    --shadow-2xs: 0px 4px 6px 0px hsl(240 30% 25% / 0.06);
    --shadow-xs: 0px 4px 6px 0px hsl(240 30% 25% / 0.06);
    --shadow-sm:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 1px 2px -1px hsl(240 30% 25% / 0.12);
    --shadow:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 1px 2px -1px hsl(240 30% 25% / 0.12);
    --shadow-md:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 2px 4px -1px hsl(240 30% 25% / 0.12);
    --shadow-lg:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 4px 6px -1px hsl(240 30% 25% / 0.12);
    --shadow-xl:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 8px 10px -1px hsl(240 30% 25% / 0.12);
    --shadow-2xl: 0px 4px 6px 0px hsl(240 30% 25% / 0.3);
    --tracking-normal: 0em;
}

.dark {
    --background: oklch(0.2155 0.0254 284.0647);
    --foreground: oklch(0.8787 0.0426 272.2767);
    --card: oklch(0.2429 0.0304 283.911);
    --card-foreground: oklch(0.8787 0.0426 272.2767);
    --popover: oklch(0.4037 0.032 280.152);
    --popover-foreground: oklch(0.8787 0.0426 272.2767);
    --primary: #a78bfa;
    --primary-foreground: #151527;
    --support-action: oklch(0.623 0.214 259.815);
    --support-action-foreground: oklch(1 0 0);
    --secondary: oklch(0.4765 0.034 278.643);
    --secondary-foreground: oklch(0.8787 0.0426 272.2767);
    --muted: oklch(0.2973 0.0294 276.2144);
    --muted-foreground: oklch(0.751 0.0396 273.932);
    --accent: #34304a;
    --accent-foreground: #ede8fa;
    --destructive: oklch(0.7556 0.1297 2.7642);
    --destructive-foreground: oklch(0.2429 0.0304 283.911);
    --border: oklch(0.324 0.0319 281.9784);
    --input: oklch(0.324 0.0319 281.9784);
    --ring: #a78bfa;
    --chart-1: oklch(0.7871 0.1187 304.7693);
    --chart-2: oklch(0.8467 0.0833 210.2545);
    --chart-3: oklch(0.8577 0.1092 142.7153);
    --chart-4: oklch(0.8237 0.1015 52.6294);
    --chart-5: oklch(0.9226 0.0238 30.4919);
    --sidebar: #111c43;
    --sidebar-foreground: #b2bbd4;
    --sidebar-primary: var(--primary);
    --sidebar-primary-foreground: var(--primary-foreground);
    --sidebar-accent: var(--accent);
    --sidebar-accent-foreground: var(--accent-foreground);
    --sidebar-border: var(--border);
    --sidebar-ring: var(--ring);
    --radius: 0.5rem;
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    --font-serif: Georgia, serif;
    --font-mono: Fira Code, monospace;
    --shadow-color: hsl(240 30% 25%);
    --shadow-opacity: 0.12;
    --shadow-blur: 6px;
    --shadow-spread: 0px;
    --shadow-offset-x: 0px;
    --shadow-offset-y: 4px;
    --letter-spacing: 0em;
    --spacing: 0.25rem;
    --shadow-2xs: 0px 4px 6px 0px hsl(240 30% 25% / 0.06);
    --shadow-xs: 0px 4px 6px 0px hsl(240 30% 25% / 0.06);
    --shadow-sm:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 1px 2px -1px hsl(240 30% 25% / 0.12);
    --shadow:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 1px 2px -1px hsl(240 30% 25% / 0.12);
    --shadow-md:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 2px 4px -1px hsl(240 30% 25% / 0.12);
    --shadow-lg:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 4px 6px -1px hsl(240 30% 25% / 0.12);
    --shadow-xl:
        0px 4px 6px 0px hsl(240 30% 25% / 0.12),
        0px 8px 10px -1px hsl(240 30% 25% / 0.12);
    --shadow-2xl: 0px 4px 6px 0px hsl(240 30% 25% / 0.3);
}

@layer base {
    * {
        @apply border-border;
    }

    body {
        @apply bg-background text-foreground;
        letter-spacing: var(--tracking-normal);
    }
}

@theme inline {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    --font-mono: Fira Code, monospace;
    --font-serif: Georgia, serif;
    --radius: 0.375rem;
    --tracking-tighter: calc(var(--tracking-normal) - 0.05em);
    --tracking-tight: calc(var(--tracking-normal) - 0.025em);
    --tracking-wide: calc(var(--tracking-normal) + 0.025em);
    --tracking-wider: calc(var(--tracking-normal) + 0.05em);
    --tracking-widest: calc(var(--tracking-normal) + 0.1em);
    --tracking-normal: var(--tracking-normal);
    --shadow-2xl: var(--shadow-2xl);
    --shadow-xl: var(--shadow-xl);
    --shadow-lg: var(--shadow-lg);
    --shadow-md: var(--shadow-md);
    --shadow: var(--shadow);
    --shadow-sm: var(--shadow-sm);
    --shadow-xs: var(--shadow-xs);
    --shadow-2xs: var(--shadow-2xs);
    --spacing: var(--spacing);
    --letter-spacing: var(--letter-spacing);
    --shadow-offset-y: var(--shadow-offset-y);
    --shadow-offset-x: var(--shadow-offset-x);
    --shadow-spread: var(--shadow-spread);
    --shadow-blur: var(--shadow-blur);
    --shadow-opacity: var(--shadow-opacity);
    --color-shadow-color: var(--shadow-color);
    --color-sidebar-ring: var(--sidebar-ring);
    --color-sidebar-border: var(--sidebar-border);
    --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
    --color-sidebar-accent: var(--sidebar-accent);
    --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
    --color-sidebar-primary: var(--sidebar-primary);
    --color-sidebar-foreground: var(--sidebar-foreground);
    --color-sidebar: var(--sidebar);
    --color-chart-5: var(--chart-5);
    --color-chart-4: var(--chart-4);
    --color-chart-3: var(--chart-3);
    --color-chart-2: var(--chart-2);
    --color-chart-1: var(--chart-1);
    --color-ring: var(--ring);
    --color-input: var(--input);
    --color-border: var(--border);
    --color-destructive-foreground: var(--destructive-foreground);
    --color-destructive: var(--destructive);
    --color-accent-foreground: var(--accent-foreground);
    --color-accent: var(--accent);
    --color-muted-foreground: var(--muted-foreground);
    --color-muted: var(--muted);
    --color-secondary-foreground: var(--secondary-foreground);
    --color-secondary: var(--secondary);
    --color-primary-foreground: var(--primary-foreground);
    --color-primary: var(--primary);
    --color-popover-foreground: var(--popover-foreground);
    --color-popover: var(--popover);
    --color-card-foreground: var(--card-foreground);
    --color-card: var(--card);
    --color-foreground: var(--foreground);
    --color-background: var(--background);
    --radius-sm: calc(var(--radius) * 0.6);
    --radius-md: calc(var(--radius) * 0.8);
    --radius-lg: var(--radius);
    --radius-xl: calc(var(--radius) * 1.4);
    --radius-2xl: calc(var(--radius) * 1.8);
    --radius-3xl: calc(var(--radius) * 2.2);
    --radius-4xl: calc(var(--radius) * 2.6);
}

@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    html,
    body {
        overflow: visible !important;
        background: white !important;
    }

    .print-document-page {
        min-height: auto !important;
        background: white !important;
    }

    .invoice-document,
    .receipt-document {
        width: 100% !important;
        color: black !important;
        font-size: 9.5pt;
        print-color-adjust: exact;
    }

    .invoice-document header,
    .invoice-document section,
    .receipt-document header,
    .receipt-document section {
        break-inside: avoid;
    }

    .invoice-lines-table {
        font-size: 8.5pt;
    }

    .invoice-lines-table thead {
        display: table-header-group;
    }

    .invoice-lines-table tr {
        break-inside: avoid;
    }
}

@layer utilities {
    [data-slot='sidebar-menu'] {
        gap: 0.35rem;
    }
    [data-slot='sidebar-menu-button'][data-size='default'] {
        height: 2.65rem;
        padding-inline: 0.8rem;
        border-radius: 0.45rem;
    }
    [data-slot='sidebar-menu-sub'] {
        gap: 0.25rem;
        border-left-color: transparent;
        margin-inline: 0.75rem;
        padding-left: 0.5rem;
    }
    [data-slot='sidebar-menu-sub-button'] {
        height: 2.15rem;
        width: 100%;
        font-size: 0.8rem;
    }
    [data-slot='sidebar-menu-sub-button']::before {
        content: '';
        width: 0.25rem;
        height: 0.25rem;
        flex-shrink: 0;
        border: 1px solid currentColor;
        border-radius: 50%;
        margin-right: 0.35rem;
    }
    .erp-content [data-slot='card-header'] > [data-slot='card-title'] {
        border-left: 3px solid
            color-mix(in srgb, var(--primary) 45%, transparent);
        padding-left: 0.6rem;
        line-height: 1.25;
    }
    .erp-content table:not([data-slot='table']) {
        border-collapse: collapse;
        background: var(--card);
    }
    .erp-content table:not([data-slot='table']) th,
    .erp-content table:not([data-slot='table']) td {
        border: 1px solid var(--border);
    }
    .erp-content table thead {
        background: var(--card);
    }
}

@layer utilities {
    .inventory-form {
        --inventory-field-border: color-mix(
            in srgb,
            var(--primary) 12%,
            var(--border)
        );
    }
    .inventory-form
        :is(
            input:not([type='hidden']):not([type='checkbox']):not(
                    [type='radio']
                ),
            select,
            textarea,
            button[role='combobox']
        ) {
        border: 1px solid var(--inventory-field-border);
        border-radius: 0.3rem;
        background: var(--card);
        color: var(--card-foreground);
        box-shadow: none;
        font-size: 0.8125rem;
        min-height: 2.375rem;
        padding-inline: 0.8rem;
        transition:
            border-color 150ms,
            box-shadow 150ms;
    }
    .inventory-form :is(input, textarea)::placeholder {
        color: color-mix(in srgb, var(--muted-foreground) 70%, var(--card));
    }
    .inventory-form
        :is(input, select, textarea, button[role='combobox']):focus-visible {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px
            color-mix(in srgb, var(--primary) 12%, transparent);
    }
    .inventory-form
        :is(input, select, textarea, button[role='combobox']):disabled,
    .inventory-form input[readonly] {
        background: var(--muted);
        color: var(--muted-foreground);
        opacity: 1;
    }
    .inventory-form
        :is(
            input,
            select,
            textarea,
            button[role='combobox']
        )[aria-invalid='true'] {
        border-color: var(--destructive);
    }
    .inventory-form :is(label, [data-slot='label']) {
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.5;
    }
    .inventory-form input[type='file'] {
        padding: 0;
    }
    .inventory-form input[type='file']::file-selector-button {
        height: 2.375rem;
        padding-inline: 0.8rem;
        margin-right: 0.8rem;
        border: 0;
        border-right: 1px solid var(--inventory-field-border);
        background: var(--muted);
        color: var(--foreground);
    }
    .inventory-form-modal {
        gap: 1rem;
        border-radius: 0.5rem;
        background: var(--card);
        padding: 1.5rem;
    }
}

@layer utilities {
    .inventory-form [data-slot='input-group'] {
        min-height: 2.375rem;
        border-color: var(--inventory-field-border);
        border-radius: 0.3rem;
        background: var(--card);
        box-shadow: none;
    }
    .inventory-form [data-slot='input-group'] input {
        border: 0;
        background: transparent;
        box-shadow: none;
    }
    .inventory-form [data-slot='input-group']:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px
            color-mix(in srgb, var(--primary) 12%, transparent);
    }
}
