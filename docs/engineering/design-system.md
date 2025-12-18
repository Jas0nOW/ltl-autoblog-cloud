# LTL AutoBlog Cloud - Design System v2.0

> **Professional Agency Design System for WordPress Plugin UI**

---

## 🎨 Overview

Das LTL AutoBlog Cloud Design System definiert alle visuellen Komponenten, Farben, Abstände und Interaktionen für das gesamte Plugin – sowohl im Admin-Backend als auch im Frontend (Customer Dashboard & Pricing Page).

### Design-Philosophie
- **Professional Agency Look** – Sauberes, modernes Design mit Gradient-Akzenten
- **Konsistenz** – Einheitliche Design-Tokens über alle Komponenten
- **Accessibility** – WCAG 2.1 konformes Kontrastverhältnis
- **Responsive** – Mobile-first Design für alle Bildschirmgrößen

---

## 🎨 Color Palette

### Primary Colors
```css
--ltlb-color-primary: #667eea           /* Brand Primary */
--ltlb-color-primary-hover: #5568d3     /* Hover State */
--ltlb-color-primary-light: #e8ebff     /* Light Background */
--ltlb-color-primary-gradient: linear-gradient(135deg, #667eea 0%, #5568d3 100%)
```

### Status Colors
```css
--ltlb-color-success: #28a745           /* Success Green */
--ltlb-color-success-light: #d4edda
--ltlb-color-error: #dc3545             /* Error Red */
--ltlb-color-error-light: #f8d7da
--ltlb-color-warning: #ffc107           /* Warning Yellow */
--ltlb-color-warning-light: #fff3cd
--ltlb-color-info: #17a2b8              /* Info Blue */
--ltlb-color-info-light: #cce5ff
```

### Neutral Colors
```css
--ltlb-color-text: #1a1a2e              /* Primary Text */
--ltlb-color-text-muted: #6c757d        /* Secondary Text */
--ltlb-color-bg: #ffffff                /* Background White */
--ltlb-color-bg-light: #f8f9fc          /* Light Background */
--ltlb-color-border: #e4e6f0            /* Border Color */
--ltlb-color-form-bg: #f8f9fa           /* Form Background */
```

---

## 📐 Spacing System

Konsistentes 4px Grid-basiertes Spacing:

```css
--ltlb-gap-xs: 4px
--ltlb-gap-sm: 8px
--ltlb-gap-md: 16px
--ltlb-gap-lg: 24px
--ltlb-gap-xl: 32px
--ltlb-gap-2xl: 48px
```

---

## 🔲 Border Radius

```css
--ltlb-radius-sm: 6px      /* Inputs, kleine Elemente */
--ltlb-radius-md: 10px     /* Cards, Container */
--ltlb-radius-lg: 16px     /* Größere Sections */
--ltlb-radius-xl: 24px     /* Hero Sections */
--ltlb-radius-full: 9999px /* Pills, Badges */
```

---

## 🌟 Shadows

```css
--ltlb-shadow-sm: 0 2px 4px rgba(0,0,0,0.05)
--ltlb-shadow-md: 0 4px 12px rgba(0,0,0,0.1)
--ltlb-shadow-lg: 0 8px 30px rgba(0,0,0,0.12)
--ltlb-shadow-xl: 0 16px 50px rgba(0,0,0,0.15)
--ltlb-shadow-glow: 0 4px 20px rgba(102, 126, 234, 0.25)
```

---

## ⚡ Transitions

```css
--ltlb-transition-fast: 0.15s ease    /* Schnelle Hover-States */
--ltlb-transition: 0.25s ease         /* Standard Transitions */
--ltlb-transition-slow: 0.4s ease     /* Komplexe Animationen */
```

---

## 🔤 Typography

### Font Families
```css
--ltlb-font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif
--ltlb-font-mono: 'SF Mono', Monaco, 'Cascadia Code', 'Consolas', monospace
```

### Font Sizes
- **Hero Title**: 2.5rem (40px) – Font Weight 800
- **Section Title**: 1.5rem (24px) – Font Weight 700
- **Card Title**: 1.25rem (20px) – Font Weight 600
- **Body Text**: 1rem (16px) – Font Weight 400
- **Small Text**: 0.875rem (14px) – Font Weight 400
- **Badge/Label**: 0.75rem (12px) – Font Weight 700

---

## 🧩 Core Components

### Layout & Container Sizing

- Dashboard wrapper `.ltlb-dashboard` now uses `width: 100%` and inherits the parent/container width. Place the shortcode inside any Elementor container to control the overall form size.
- Avoid fixed widths on parent containers; prefer percentages or Elementor's container width controls for responsive behavior.
- Locked and login-required boxes intentionally use small `max-width` values for readability; they do not affect the main form sizing.

### 1. Buttons (`.ltlb-btn`)

```html
<button class="ltlb-btn ltlb-btn-primary">Primary Button</button>
<button class="ltlb-btn ltlb-btn-secondary">Secondary Button</button>
<button class="ltlb-btn ltlb-btn-sm">Small Button</button>
<button class="ltlb-btn ltlb-btn-lg">Large Button</button>
```

**States:**
- Default: Primary gradient background
- Hover: Transform -2px, shadow glow
- Active: Transform +1px
- Disabled: Opacity 0.6, cursor not-allowed

### 2. Cards (`.ltlb-card`)

```html
<div class="ltlb-card">
    <div class="ltlb-card-header">Header</div>
    <div class="ltlb-card-body">Content</div>
</div>
```

### 3. Progress Card (`.ltlb-progress-card`)

Setup-Fortschritt mit Schritt-für-Schritt Anzeige:

```html
<div class="ltlb-progress-card">
    <div class="ltlb-progress-header">
        <h2>📋 Progress Title</h2>
    </div>
    <div class="ltlb-step completed">
        <div class="ltlb-step-icon">✅</div>
        <div class="ltlb-step-content">
            <strong>Step Title</strong>
            <p>Step description</p>
        </div>
        <div class="ltlb-step-action">
            <a href="#" class="ltlb-btn">Action</a>
        </div>
    </div>
</div>
```

### 4. Form Section (`.ltlb-form-section`)

```html
<div class="ltlb-form-section">
    <div class="ltlb-section-header">
        <h3 class="ltlb-section-title">Section Title</h3>
    </div>
    <form class="ltlb-form">
        <div class="ltlb-form-group">
            <label class="ltlb-label">Label</label>
            <input type="text" class="ltlb-input">
            <small class="ltlb-help-text">Help text</small>
        </div>
    </form>
</div>
```

### 5. Alerts (`.ltlb-alert`)

```html
<div class="ltlb-alert ltlb-alert-success">Success message</div>
<div class="ltlb-alert ltlb-alert-error">Error message</div>
<div class="ltlb-alert ltlb-alert-warning">Warning message</div>
<div class="ltlb-alert ltlb-alert-info">Info message</div>
```

### 6. Badges (`.ltlb-badge`)

```html
<span class="ltlb-badge ltlb-badge-success">Success</span>
<span class="ltlb-badge ltlb-badge-error">Error</span>
<span class="ltlb-badge ltlb-badge-warning">Warning</span>
```

### 7. Language Switcher (`.ltlb-lang-switcher`)

```html
<div class="ltlb-lang-switcher">
    <button class="ltlb-lang-btn active" data-lang="en">🇺🇸 EN</button>
    <button class="ltlb-lang-btn" data-lang="de">🇩🇪 DE</button>
</div>
```

### 8. Tables (`.ltlb-table`)

```html
<div class="ltlb-table-wrapper">
    <table class="ltlb-table">
        <thead>
            <tr><th>Column</th></tr>
        </thead>
        <tbody>
            <tr><td>Data</td></tr>
        </tbody>
    </table>
</div>
```

---

## 📄 Page Layouts

### Dashboard Page
```
┌─────────────────────────────────────────────┐
│  Header: Title + Language Switcher          │
├─────────────────────────────────────────────┤
│  Alerts (Success/Error)                     │
├─────────────────────────────────────────────┤
│  Progress Card (4 Steps)                    │
├─────────────────────────────────────────────┤
│  Form Section: WordPress Connection         │
├─────────────────────────────────────────────┤
│  Form Section: RSS Settings                 │
├─────────────────────────────────────────────┤
│  Runs Section: History Table                │
└─────────────────────────────────────────────┘
```

### Pricing Page
```
┌─────────────────────────────────────────────┐
│  Language Switcher (top right)              │
├─────────────────────────────────────────────┤
│  Hero: Gradient Background + Title          │
├─────────────────────────────────────────────┤
│  ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐       │
│  │Free │  │Basic│  │ Pro │  │Studio       │
│  │Card │  │Card │  │Card │  │Card │       │
│  └─────┘  └─────┘  └─────┘  └─────┘       │
└─────────────────────────────────────────────┘
```

---

## 📱 Responsive Breakpoints

```css
/* Mobile */
@media (max-width: 480px) { ... }

/* Tablet */
@media (max-width: 768px) { ... }

/* Desktop */
@media (min-width: 769px) { ... }
```

---

## 🔧 CSS Class Naming Convention

Alle Klassen verwenden das **BEM-ähnliche** Prefix `ltlb-`:

```
ltlb-{component}                    /* Block */
ltlb-{component}-{element}          /* Element */
ltlb-{component}-{modifier}         /* Modifier */
```

**Beispiele:**
- `ltlb-btn` – Button Block
- `ltlb-btn-primary` – Primary Modifier
- `ltlb-form-group` – Form Group Element
- `ltlb-card-header` – Card Header Element

---

## 📁 Datei-Struktur

```
assets/
├── admin.css          # Admin Backend Styles
├── admin.js           # Admin JavaScript
├── frontend.css       # Frontend Shortcode Styles
└── frontend.js        # Frontend JavaScript
```

---

## 🌐 i18n / Mehrsprachigkeit

Das Design System unterstützt EN und DE:
- Backend: `switch_to_locale()` via User Meta
- Frontend: Cookie `ltl_frontend_lang` via JavaScript

---

## ✅ Best Practices

1. **Immer CSS Variables verwenden** – Keine Hardcoded Values
2. **Transitions bei Hover-States** – Smooth User Experience
3. **Focus-States für Accessibility** – `:focus-visible` Outline
4. **Print Styles berücksichtigen** – Buttons verstecken
5. **Mobile-first entwickeln** – Responsive von klein nach groß

---

## 🚀 Version History

| Version | Datum | Changes |
|---------|-------|---------|
| 2.0.0 | 2024-12 | Complete Agency Design Overhaul |
| 1.0.0 | 2024-11 | Initial Design System |
