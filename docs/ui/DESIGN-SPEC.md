# LTL AutoBlog Cloud Portal — UI/UX Design Specification

> **Status**: Phase 1 – Design Specification
> **Version**: 1.0
> **Date**: 2025-12-18
> **Target**: Premium Agency-Level UI/UX

---

## Executive Summary

This specification defines a comprehensive UI/UX redesign for the LTL AutoBlog Cloud Portal WordPress plugin, transforming it from a functional but basic interface into a premium, agency-level product. The redesign focuses on:

- **Clear Information Architecture**: Logical navigation, grouped settings, progressive disclosure
- **Modern WP-Admin UX**: Native WordPress patterns with premium polish
- **Consistent Microcopy**: Clear, helpful, jargon-free text throughout
- **State Management**: Proper Empty/Error/Success/Loading states everywhere
- **Accessibility**: WCAG 2.1 AA compliance, keyboard navigation, screen reader support
- **Performance**: Conditional asset loading, optimized queries, no bloat

**Core Principle**: Feel like a "Top-Tier" product without breaking backward compatibility or WordPress standards.

---

## Current State Analysis

### UI Inventory Map

#### Admin Screens
1. **Main Settings Page** (`ltl-saas-portal`)
   - File: `includes/Admin/class-admin.php`
   - Menu: Top-level menu "LTL AutoBlog Cloud"
   - Content: Single form with flat settings layout
   - Issues:
     - ❌ No tabs/sections for logical grouping
     - ❌ Inline styles everywhere
     - ❌ Secrets displayed in plain form inputs
     - ❌ No help/contextual info
     - ❌ German-only hardcoded labels
     - ❌ Generic success/error notices
     - ❌ No empty states for unset values
     - ❌ Marketing section mixed with API settings

#### Frontend Shortcodes
1. **Customer Dashboard** (`[ltl_saas_dashboard]`)
   - File: `includes/class-ltl-saas-portal.php` (line 180-600)
   - Purpose: Customer-facing connection/settings management
   - Issues:
     - ❌ Inline styles mixed with logic
     - ❌ No template separation
     - ❌ Inconsistent spacing/typography
     - ❌ No dark mode consideration
     - ❌ Limited accessibility (no aria labels)
     - ⚠️ Has progress tracker (good!) but styling is inline
     - ⚠️ Has test buttons (good!) but no proper loading states

2. **Pricing Page** (`[ltl_saas_pricing]`)
   - File: `includes/class-ltl-saas-portal.php` (line 650-890)
   - Purpose: Public-facing pricing display
   - Issues:
     - ❌ All CSS in PHP output
     - ❌ No template structure
     - ❌ Hardcoded grid layout (no flexibility)
     - ⚠️ Bilingual support exists (good!)
     - ⚠️ Responsive CSS exists (good!)

#### Assets
- **CSS**: None found (all inline)
- **JS**: None found (all inline `<script>` tags)
- **Templates**: None (all rendered directly in PHP)

### Pain Points Identified

1. **Navigation & Architecture**
   - Single flat settings page with 10+ fields
   - No logical grouping by user journey
   - Mixed concerns (API secrets, billing, marketing, integrations)

2. **Visual Design**
   - Inline styles prevent consistent theming
   - No design tokens (colors, spacing, typography)
   - Inconsistent button styles
   - No visual hierarchy

3. **Copywriting**
   - German/English mix inconsistently
   - Field labels lack context
   - No tooltips or help text for complex fields
   - Error messages too generic

4. **State Management**
   - No loading indicators for async actions
   - No empty states for new users
   - No confirmation for destructive actions (token regeneration)
   - Success/error notices use raw `<div>` instead of WP Admin Notice API

5. **Accessibility**
   - No ARIA labels
   - Inline click handlers (no keyboard support)
   - Color contrast issues (greys on whites)
   - No focus indicators

6. **Technical Debt**
   - 700+ lines in single method (`shortcode_dashboard`)
   - No template system
   - No asset enqueue system
   - No i18n (hardcoded German)

---

## Target State: Information Architecture

### Admin Navigation Structure

```
LTL AutoBlog Cloud (Main Menu)
└── Dashboard (landing page)
    ├── Quick Stats (connections, active plans, last runs)
    └── Quick Actions (docs link, support)

└── Settings (submenu)
    ├── Tab: API & Integrations
    │   ├── Section: Make.com Setup
    │   │   └── Make Token, API Key
    │   └── Section: REST Endpoints
    │       └── Documentation links
    │
    ├── Tab: Billing
    │   ├── Section: Gumroad
    │   │   └── Secret, Product Mapping, Webhook URL
    │   └── Section: Stripe (future)
    │       └── Secret, Product Mapping, Webhook URL
    │
    └── Tab: Marketing
        └── Section: Pricing Page
            └── Checkout URLs (Free, Basic, Pro, Studio)

└── Customers (submenu, future)
    └── List table of all tenants with search/filter

└── Activity Log (submenu, future)
    └── Recent runs, errors, notifications

└── Help & Docs (submenu)
    └── Links to GitHub docs, support, onboarding guide
```

### Frontend (Shortcode) Structures

#### Dashboard Shortcode: Customer Journey
```
[Login Check]
└── Active Status Check
    ├── If Inactive → Upgrade CTA (locked state)
    └── If Active → Full Dashboard
        ├── Progress Tracker (4 steps: visual checklist)
        ├── WordPress Connection (card)
        │   ├── Form (URL, user, app password)
        │   └── Test Connection Button
        ├── Settings (card)
        │   ├── RSS URL (with test)
        │   ├── Language, Tone, Frequency, Publish Mode
        │   └── Save Button
        ├── Plan Status (card)
        │   └── Usage meter (X/Y posts used)
        └── Recent Activity (card, expandable)
            └── Last 5 runs with status
```

#### Pricing Shortcode: Layout
```
Hero Section
└── Headline, subtitle

Plans Grid (4 columns → responsive)
├── Free (secondary style)
├── Basic
├── Pro (featured/highlighted)
└── Studio (contact style)
```

---

## Theming System (Color Customizer)

**Purpose**: Allow customers to customize brand colors for white-label use cases.

**Requirements**:
- Admin tab "Design" with color pickers for: Primary, Success, Error, Warning
- Live preview panel showing all components with selected colors
- Reset button per color (restore default)
- Saved colors apply globally (admin + frontend)
- Auto-generate light/hover variants from base color

**Implementation**:
- Store colors in `wp_options` table: `ltl_saas_custom_colors`
- Output as inline CSS in `<head>` (override CSS variables)
- JavaScript live preview (no page reload)
- PHP helper to adjust brightness (for variants)

**User Flow**:
1. Admin → LTL AutoBlog Cloud → Design tab
2. Change primary color → see button preview update instantly
3. Click "Reset" → restore default color
4. Click "Save Changes" → colors persist
5. View frontend → see custom colors applied

---

## Component Catalog

### Admin Components

#### 0. Color Customizer
**Purpose**: Brand color picker with live preview (Admin only)

See [COMPONENTS.md](COMPONENTS.md#0-color-customizer-component-admin-only) for full implementation.

**Features**:
- 4 color pickers (Primary, Success, Error, Warning)
- Live preview panel with sample components
- Reset button per color
- Auto-generates light/hover variants
- Sticky preview (stays visible while scrolling)

---

#### 1. Page Header
```html
<div class="ltlb-admin-header">
  <div class="ltlb-admin-header__title">
    <h1>Settings</h1>
    <p class="ltlb-admin-header__description">Configure your API integrations and billing providers</p>
  </div>
  <div class="ltlb-admin-header__actions">
    <a href="#" class="button button-secondary">View Docs</a>
  </div>
</div>
```

**Usage**: Top of every admin page, provides context and quick actions.

#### 2. Tab Navigation
Uses WordPress core `<h2 class="nav-tab-wrapper">` pattern:

```html
<h2 class="nav-tab-wrapper ltlb-nav-tabs">
  <a href="?page=ltl-saas-portal&tab=api" class="nav-tab nav-tab-active">API & Integrations</a>
  <a href="?page=ltl-saas-portal&tab=billing" class="nav-tab">Billing</a>
  <a href="?page=ltl-saas-portal&tab=marketing" class="nav-tab">Marketing</a>
  <a href="?page=ltl-saas-portal&tab=design" class="nav-tab">Design</a>
</h2>
```

#### 3. Settings Section
```html
<div class="ltlb-section">
  <div class="ltlb-section__header">
    <h3 class="ltlb-section__title">Make.com Setup</h3>
    <p class="ltlb-section__description">
      Generate and manage your Make.com integration tokens.
      <a href="#">Learn more →</a>
    </p>
  </div>
  <table class="form-table ltlb-form-table">
    <!-- WP Settings API fields -->
  </table>
</div>
```

#### 4. Secret Field with Regenerate
```html
<tr>
  <th scope="row">
    <label>Make Token</label>
    <p class="description">Keep this secret. Used for `/make/tenants` authentication.</p>
  </th>
  <td>
    <div class="ltlb-secret-field">
      <span class="ltlb-secret-field__status ltlb-secret-field__status--set">
        ✓ Token set
      </span>
      <code class="ltlb-secret-field__hint">••••••a7f2</code>
      <button type="button" class="button ltlb-secret-regenerate" data-secret="make_token">
        🔄 Regenerate
      </button>
    </div>
    <p class="description">
      ⚠️ Regenerating will invalidate the old token. Update Make.com immediately after.
    </p>
  </td>
</tr>
```

**States**:
- Set: green checkmark, shows last 4 chars
- Unset: grey warning, "Not configured"
- Regenerating: loading spinner, button disabled

#### 5. Admin Notice (Standardized)
Use WordPress core notice classes with custom prefix:

```php
// Success
echo '<div class="notice notice-success is-dismissible ltlb-notice">
  <p><strong>Success!</strong> Make Token regenerated.</p>
</div>';

// Error
echo '<div class="notice notice-error ltlb-notice">
  <p><strong>Error:</strong> Invalid JSON in product mapping.</p>
</div>';

// Warning
echo '<div class="notice notice-warning ltlb-notice">
  <p><strong>Warning:</strong> Gumroad secret not set. Webhooks will fail.</p>
</div>';

// Info
echo '<div class="notice notice-info ltlb-notice">
  <p><strong>Tip:</strong> Check our <a href="#">onboarding guide</a> for step-by-step setup.</p>
</div>';
```

### Frontend (Shortcode) Components

#### 6. Dashboard Card
```html
<div class="ltlb-card">
  <div class="ltlb-card__header">
    <h3 class="ltlb-card__title">WordPress Connection</h3>
    <span class="ltlb-badge ltlb-badge--success">Connected</span>
  </div>
  <div class="ltlb-card__body">
    <!-- Form or content -->
  </div>
  <div class="ltlb-card__footer">
    <button class="ltlb-btn ltlb-btn--primary">Save Changes</button>
    <button class="ltlb-btn ltlb-btn--secondary">Test Connection</button>
  </div>
</div>
```

**Variants**:
- Default (white bg)
- Highlighted (subtle blue bg for featured content)
- Locked (greyed out with overlay for inactive accounts)

#### 7. Progress Tracker
```html
<div class="ltlb-progress">
  <div class="ltlb-progress__item ltlb-progress__item--complete">
    <div class="ltlb-progress__icon">✅</div>
    <div class="ltlb-progress__content">
      <h4 class="ltlb-progress__title">WordPress Connected</h4>
      <p class="ltlb-progress__desc">Site: meinblog.de</p>
    </div>
    <a href="#" class="ltlb-progress__action">Edit</a>
  </div>
  <div class="ltlb-progress__item ltlb-progress__item--incomplete">
    <div class="ltlb-progress__icon">⚠️</div>
    <div class="ltlb-progress__content">
      <h4 class="ltlb-progress__title">RSS Feed</h4>
      <p class="ltlb-progress__desc">Not configured</p>
    </div>
    <a href="#" class="ltlb-progress__action ltlb-btn ltlb-btn--primary">Configure Now</a>
  </div>
</div>
```

**States**:
- Complete: green checkmark, "Edit" link
- Incomplete: orange warning, "Configure Now" button
- Loading: spinner icon, "Processing..."

#### 8. Badge
```html
<span class="ltlb-badge ltlb-badge--success">Active</span>
<span class="ltlb-badge ltlb-badge--warning">Pending</span>
<span class="ltlb-badge ltlb-badge--error">Inactive</span>
<span class="ltlb-badge ltlb-badge--neutral">Free</span>
```

#### 9. Empty State
```html
<div class="ltlb-empty">
  <div class="ltlb-empty__icon">📭</div>
  <h3 class="ltlb-empty__title">No Activity Yet</h3>
  <p class="ltlb-empty__desc">
    Your first automated run will appear here once your setup is complete.
  </p>
  <a href="#" class="ltlb-btn ltlb-btn--primary">Complete Setup</a>
</div>
```

#### 10. Loading State
```html
<div class="ltlb-loading">
  <div class="ltlb-spinner"></div>
  <p class="ltlb-loading__text">Testing connection...</p>
</div>
```

#### 11. Form Field (Enhanced)
```html
<div class="ltlb-field">
  <label for="rss_url" class="ltlb-field__label">
    RSS Feed URL
    <span class="ltlb-field__required">*</span>
  </label>
  <div class="ltlb-field__input-wrapper">
    <input
      type="url"
      id="rss_url"
      name="rss_url"
      class="ltlb-field__input"
      placeholder="https://example.com/feed"
      aria-describedby="rss_url_help"
      required
    />
    <button type="button" class="ltlb-field__action" aria-label="Test RSS Feed">
      🧪 Test
    </button>
  </div>
  <p id="rss_url_help" class="ltlb-field__help">
    Enter your RSS or Atom feed URL. <a href="#">Need help finding it?</a>
  </p>
  <div id="rss_url_feedback" class="ltlb-field__feedback" role="alert"></div>
</div>
```

**States**:
- Default
- Focus (blue outline)
- Error (red border + message)
- Success (green border + checkmark)
- Disabled (greyed out)

---

## Copywriting Guidelines

### Tone & Voice
- **Friendly but Professional**: "You're all set!" not "Configuration successful."
- **Clear over Clever**: "WordPress URL" not "WP Endpoint"
- **Action-Oriented**: "Connect WordPress" not "WordPress Connection Management"
- **Jargon-Free**: "RSS feed URL" not "RSS endpoint resource locator"

### Field Labels
**Bad**: "Token"
**Good**: "Make Token" + description "Keep this secret. Used for Make.com authentication."

**Bad**: "URL"
**Good**: "WordPress Site URL" + example "https://meinblog.de"

### Help Text Patterns
```
[Icon] [Brief explanation] [Optional link]

✅ Example: "This password is encrypted and never shown in plain text."
✅ Example: "Your plan allows 30 posts/month. 12 used this month."
✅ Example: "Need help? Check our <a href='#'>onboarding guide</a>."
```

### Empty States
```
[Icon] [What's missing] [Why it matters] [CTA]

📭 No Runs Yet
You haven't completed your setup. Once configured, automated runs will appear here.
[Complete Setup →]
```

### Error Messages
**Bad**: "Invalid input"
**Good**: "WordPress URL must start with https:// (e.g., https://yoursite.com)"

**Bad**: "Failed"
**Good**: "Connection test failed. Check your Application Password is correct."

### Success Messages
**Bad**: "Saved"
**Good**: "✓ Settings saved! Your changes will apply on the next run."

---

## Accessibility Checklist

### WCAG 2.1 AA Requirements

#### Perceivable
- [ ] Color contrast ≥ 4.5:1 for text, 3:1 for UI components
- [ ] All form inputs have associated `<label>` elements
- [ ] Images/icons have alt text or aria-label
- [ ] Error messages use both color and text/icon

#### Operable
- [ ] All functionality keyboard-accessible (no mouse-only)
- [ ] Visible focus indicators on all interactive elements
- [ ] Skip links for main content (admin pages)
- [ ] No keyboard traps

#### Understandable
- [ ] Labels and instructions clear and concise
- [ ] Error messages specific and actionable
- [ ] Form validation on blur + submit (not just submit)
- [ ] Consistent navigation structure

#### Robust
- [ ] Valid HTML (no unclosed tags, proper nesting)
- [ ] ARIA roles where needed (alert, status, progressbar)
- [ ] Works with screen readers (NVDA, JAWS, VoiceOver)

### Implementation Details

#### Focus Management
```css
.ltlb-btn:focus,
.ltlb-field__input:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}
```

#### Screen Reader Announcements
```html
<!-- Loading state -->
<div role="status" aria-live="polite" aria-atomic="true">
  Testing connection...
</div>

<!-- Success -->
<div role="alert" aria-live="assertive">
  Connection successful!
</div>

<!-- Error -->
<div role="alert" aria-live="assertive">
  Error: Invalid URL format
</div>
```

#### Keyboard Navigation
- Tab: Navigate between fields/buttons
- Enter: Submit forms, activate buttons
- Escape: Close modals/dropdowns
- Arrow keys: Navigate tabs (optional enhancement)

---

## Design Tokens (CSS Variables)

### Color Palette
```css
:root {
  /* Primary (Purple) */
  --ltlb-color-primary: #667eea;
  --ltlb-color-primary-hover: #5568d3;
  --ltlb-color-primary-light: #f0f4ff;

  /* Semantic Colors */
  --ltlb-color-success: #28a745;
  --ltlb-color-success-light: #d4edda;
  --ltlb-color-error: #dc3545;
  --ltlb-color-error-light: #f8d7da;
  --ltlb-color-warning: #ffc107;
  --ltlb-color-warning-light: #fff3cd;
  --ltlb-color-info: #17a2b8;
  --ltlb-color-info-light: #d1ecf1;

  /* Neutrals */
  --ltlb-color-text: #1a1a1a;
  --ltlb-color-text-muted: #666;
  --ltlb-color-text-disabled: #999;
  --ltlb-color-border: #ddd;
  --ltlb-color-bg: #f8f9fa;
  --ltlb-color-bg-white: #fff;
  --ltlb-color-bg-hover: #f0f0f0;

  /* Spacing */
  --ltlb-gap-xs: 4px;
  --ltlb-gap-sm: 8px;
  --ltlb-gap-md: 16px;
  --ltlb-gap-lg: 24px;
  --ltlb-gap-xl: 32px;

  /* Border Radius */
  --ltlb-radius-sm: 4px;
  --ltlb-radius-md: 8px;
  --ltlb-radius-lg: 12px;

  /* Shadows */
  --ltlb-shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
  --ltlb-shadow-md: 0 4px 12px rgba(0,0,0,0.15);
  --ltlb-shadow-lg: 0 8px 24px rgba(0,0,0,0.2);

  /* Typography */
  --ltlb-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --ltlb-font-size-sm: 0.875rem; /* 14px */
  --ltlb-font-size-base: 1rem; /* 16px */
  --ltlb-font-size-lg: 1.125rem; /* 18px */
  --ltlb-font-size-xl: 1.5rem; /* 24px */

  /* Transitions */
  --ltlb-transition: 0.2s ease-in-out;
}
```

---

## Risk Analysis & Mitigation

### Risk 1: Breaking Existing Shortcodes
**Impact**: High (customer-facing)
**Likelihood**: Medium

**Mitigation**:
- Keep shortcode names unchanged: `[ltl_saas_dashboard]`, `[ltl_saas_pricing]`
- Keep all shortcode attributes (e.g., `lang="en"`) working identically
- Add CSS classes, don't remove old ones (additive changes only)
- Test with existing pages before/after

**Test Plan**:
1. Create test page with `[ltl_saas_dashboard]` before changes
2. Screenshot current output
3. Apply changes
4. Compare output: should be visually different but functionally identical
5. Test all interactive elements (buttons, forms)

### Risk 2: Breaking Admin Options
**Impact**: High (admin lockout possible)
**Likelihood**: Low

**Mitigation**:
- Use Settings API properly (no direct $_POST manipulation)
- Keep all option keys unchanged (e.g., `ltl_saas_checkout_url_starter`)
- Add new options, don't rename/delete existing
- Provide migration path if restructuring needed

**Test Plan**:
1. Export existing options before changes
2. Apply changes
3. Verify all options still accessible
4. Test regenerate buttons don't corrupt data

### Risk 3: CSS/JS Conflicts
**Impact**: Medium (visual glitches)
**Likelihood**: Medium

**Mitigation**:
- Prefix all classes with `ltlb-`
- Use specific selectors (no bare `.button` overrides)
- Enqueue assets only on plugin pages (conditional)
- Test with popular themes (Twenty Twenty-Three, Astra, etc.)

**Test Plan**:
1. Install plugin on fresh WP with default theme
2. Check no visual breaks
3. Install popular theme + test again
4. Install Elementor/other page builders + test

### Risk 4: Performance Degradation
**Impact**: Low (slow admin)
**Likelihood**: Low

**Mitigation**:
- No heavy CSS frameworks (use minimal custom CSS)
- Conditional asset loading (only on plugin pages)
- Optimize DB queries (use prepared statements, avoid N+1)
- Lazy-load run history (paginate if >50 runs)

**Test Plan**:
1. Profile page load time before/after
2. Check number of DB queries (WP Query Monitor)
3. Test with 1000+ customer records

### Risk 5: Accessibility Regression
**Impact**: High (legal/compliance)
**Likelihood**: Low

**Mitigation**:
- Follow WCAG 2.1 AA from day 1
- Use semantic HTML (`<label>`, `<button>`, proper headings)
- Test with keyboard only (no mouse)
- Test with screen reader (NVDA/JAWS)

**Test Plan**:
1. Run aXe DevTools audit (0 violations target)
2. Navigate entire admin with keyboard only
3. Test one screen with screen reader
4. Check color contrast (Contrast Checker browser extension)

---

## Acceptance Criteria (Definition of Done)

### Admin Redesign Complete When:
- [ ] Tab navigation implemented (API, Billing, Marketing)
- [ ] All sections use card/section components (no raw `<table>`)
- [ ] All secrets use standardized secret field UI
- [ ] All notices use WP Admin Notice API + custom classes
- [ ] All text uses i18n functions (`__`, `_e`, etc.)
- [ ] No inline styles (all moved to `assets/admin.css`)
- [ ] Help text on every field (tooltip or description)
- [ ] Regenerate buttons have confirmation modal
- [ ] Page loads <1s on staging (WP Query Monitor check)
- [ ] Zero console errors
- [ ] Zero aXe accessibility violations

### Dashboard Shortcode Complete When:
- [ ] Progress tracker uses card component
- [ ] Forms use field component (with validation states)
- [ ] Test buttons show loading/success/error states
- [ ] Empty state when no connection exists
- [ ] Locked state when account inactive
- [ ] All CSS in `assets/frontend.css` (no inline)
- [ ] All JS in `assets/frontend.js` (no inline `<script>`)
- [ ] Responsive (mobile/tablet/desktop tested)
- [ ] Keyboard accessible (tab through everything)
- [ ] Works with screen reader (tested with NVDA)

### Pricing Shortcode Complete When:
- [ ] Uses consistent card grid system
- [ ] Featured plan visually distinct (border/scale)
- [ ] All prices/labels from shortcode attributes (no hardcode)
- [ ] Responsive grid (1 column mobile → 4 desktop)
- [ ] Hover effects smooth (0.2s transition)
- [ ] CTA buttons consistent with dashboard buttons
- [ ] All CSS in `assets/frontend.css`
- [ ] No theme conflicts (tested with 3 themes)

### Documentation Complete When:
- [ ] `DESIGN-SPEC.md` (this file) approved
- [ ] `IMPLEMENTATION-PLAN.md` created with step-by-step tasks
- [ ] `COMPONENTS.md` created with code examples
- [ ] Screenshots before/after captured
- [ ] Changelog updated with "UI/UX Redesign" entry

---

## Visual Hierarchy Guidelines

### Admin Pages
```
Page Header (h1, 2rem, bold)
  └─ Description (1rem, muted)

Tab Navigation (nav-tab, 1rem, bold when active)

Section Header (h3, 1.25rem, bold)
  └─ Section Description (0.875rem, muted)

Form Label (0.875rem, bold)
  └─ Help Text (0.875rem, muted, italic)

Button Primary (0.875rem, bold, white on primary)
Button Secondary (0.875rem, normal, primary text on white border)
```

### Frontend Shortcodes
```
Hero Headline (h1, 2.5rem, bold)
  └─ Subheadline (1.25rem, muted)

Card Title (h3, 1.5rem, bold)
  └─ Card Body (1rem, normal)

Button (1rem, bold)

Help Text (0.875rem, muted)
```

---

## Next Steps

1. **Review & Approve**: Stakeholder review of this spec (1-2 days)
2. **Create Implementation Plan**: Break down into 10-15 small, safe steps
3. **Create Component Library**: Build reusable PHP/CSS/JS components
4. **Implement Phase 1**: Admin backend redesign (5-7 days)
5. **Implement Phase 2**: Dashboard shortcode (3-4 days)
6. **Implement Phase 3**: Pricing shortcode (2-3 days)
7. **QA & Polish**: Accessibility audit, cross-browser testing (2-3 days)
8. **Documentation**: Update user-facing docs with new screenshots

**Total Estimated Timeline**: 15-20 business days

---

## References
- [WordPress Admin UI Best Practices](https://developer.wordpress.org/plugins/administration-menus/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [Material Design Elevation](https://material.io/design/environment/elevation.html) (for shadow inspiration)

---

**End of Design Specification**
