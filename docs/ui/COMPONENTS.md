# LTL AutoBlog Cloud — Component Library

> **Version**: 1.0
> **Purpose**: Developer reference for building consistent UI components
> **Audience**: Plugin developers implementing the design spec

---

## Overview

This document defines reusable UI components with code examples. All components follow:

- **WordPress Standards**: Sanitize, escape, validate all data
- **i18n**: All text wrapped in translation functions
- **Accessibility**: ARIA labels, semantic HTML, keyboard navigation
- **Consistency**: Use CSS variables for colors, spacing, typography

---

## Component Catalog

### 0. Color Customizer Component (Admin Only)

**Purpose**: Allow users to customize brand colors with live preview

**Usage**:
```php
// In class-admin.php render_tab_design() method
$colors = [
    'primary' => __( 'Primary Color', 'ltl-saas-portal' ),
    'success' => __( 'Success Color', 'ltl-saas-portal' ),
    'error' => __( 'Error Color', 'ltl-saas-portal' ),
    'warning' => __( 'Warning Color', 'ltl-saas-portal' ),
];

$saved_colors = get_option( 'ltl_saas_custom_colors', [] );

echo '<div class="ltlb-color-customizer">';
echo '<div class="ltlb-color-customizer__controls">';

foreach ( $colors as $key => $label ) {
    $default = $this->get_default_color( $key );
    $value = $saved_colors[ $key ] ?? $default;

    echo '<div class="ltlb-color-field">';
    echo '<label for="color_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
    echo '<div class="ltlb-color-field__input-group">';
    echo '<input type="color" id="color_' . esc_attr( $key ) . '" name="ltl_saas_custom_colors[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="ltlb-color-picker" data-color-key="' . esc_attr( $key ) . '" />';
    echo '<input type="text" value="' . esc_attr( $value ) . '" class="ltlb-color-hex" readonly />';
    echo '<button type="button" class="button ltlb-reset-color" data-default="' . esc_attr( $default ) . '" data-target="color_' . esc_attr( $key ) . '">';
    echo '↺ ' . esc_html__( 'Reset', 'ltl-saas-portal' );
    echo '</button>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '<div class="ltlb-color-customizer__preview">';
echo '<h4>' . esc_html__( 'Live Preview', 'ltl-saas-portal' ) . '</h4>';
echo '<div class="ltlb-preview-samples"><!-- Sample components here --></div>';
echo '</div>';
echo '</div>';
```

**CSS** (in `assets/admin.css`):
```css
.ltlb-color-customizer {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--ltlb-gap-lg);
}

.ltlb-color-customizer__controls {
    /* Left side: Color pickers */
}

.ltlb-color-customizer__preview {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-md);
    padding: var(--ltlb-gap-lg);
    position: sticky;
    top: 32px; /* Sticky preview */
}

.ltlb-color-field {
    margin-bottom: var(--ltlb-gap-md);
}

.ltlb-color-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 0.875rem;
}

.ltlb-color-field__input-group {
    display: flex;
    align-items: center;
    gap: var(--ltlb-gap-sm);
}

.ltlb-color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-sm);
    cursor: pointer;
    transition: var(--ltlb-transition);
}

.ltlb-color-picker:hover {
    border-color: var(--ltlb-color-primary);
}

.ltlb-color-picker:focus {
    outline: 2px solid var(--ltlb-color-primary);
    outline-offset: 2px;
}

.ltlb-color-hex {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-sm);
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    font-size: 0.875rem;
}

.ltlb-reset-color {
    flex-shrink: 0;
}

.ltlb-preview-samples {
    display: flex;
    flex-direction: column;
    gap: var(--ltlb-gap-md);
    align-items: flex-start;
}

@media (max-width: 1200px) {
    .ltlb-color-customizer {
        grid-template-columns: 1fr;
    }

    .ltlb-color-customizer__preview {
        position: static;
    }
}
```

**JavaScript** (in `assets/admin.js`):
```js
// Live preview for color changes
$('.ltlb-color-picker').on('input change', function() {
    const $picker = $(this);
    const color = $picker.val();
    const key = $picker.data('color-key');

    // Update hex display
    $picker.siblings('.ltlb-color-hex').val(color);

    // Update CSS variable in preview
    updatePreviewColor(key, color);
});

// Reset button
$('.ltlb-reset-color').on('click', function() {
    const $btn = $(this);
    const defaultColor = $btn.data('default');
    const targetId = $btn.data('target');
    const $picker = $('#' + targetId);
    const key = $picker.data('color-key');

    $picker.val(defaultColor).trigger('change');
});

function updatePreviewColor(key, color) {
    const previewArea = $('.ltlb-color-customizer__preview')[0];
    if (!previewArea) return;

    switch(key) {
        case 'primary':
            previewArea.style.setProperty('--ltlb-color-primary', color);
            previewArea.style.setProperty('--ltlb-color-primary-hover', adjustColorBrightness(color, -10));
            break;
        case 'success':
            previewArea.style.setProperty('--ltlb-color-success', color);
            previewArea.style.setProperty('--ltlb-color-success-light', adjustColorBrightness(color, 80));
            break;
        case 'error':
            previewArea.style.setProperty('--ltlb-color-error', color);
            previewArea.style.setProperty('--ltlb-color-error-light', adjustColorBrightness(color, 80));
            break;
        case 'warning':
            previewArea.style.setProperty('--ltlb-color-warning', color);
            previewArea.style.setProperty('--ltlb-color-warning-light', adjustColorBrightness(color, 80));
            break;
    }
}

function adjustColorBrightness(hex, percent) {
    hex = hex.replace('#', '');
    let r = parseInt(hex.substring(0, 2), 16);
    let g = parseInt(hex.substring(2, 4), 16);
    let b = parseInt(hex.substring(4, 6), 16);

    r = Math.min(255, Math.max(0, r + (r * percent / 100)));
    g = Math.min(255, Math.max(0, g + (g * percent / 100)));
    b = Math.min(255, Math.max(0, b + (b * percent / 100)));

    return '#' +
        Math.round(r).toString(16).padStart(2, '0') +
        Math.round(g).toString(16).padStart(2, '0') +
        Math.round(b).toString(16).padStart(2, '0');
}
```

**PHP Helper Methods**:
```php
/**
 * Get default color value
 */
private function get_default_color( $key ) {
    $defaults = [
        'primary' => '#667eea',
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107',
    ];
    return $defaults[ $key ] ?? '#667eea';
}

/**
 * Adjust color brightness (for auto-generating light/hover variants)
 */
private function adjust_color_brightness( $hex, $percent ) {
    $hex = str_replace( '#', '', $hex );
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );

    $r = min( 255, max( 0, $r + ( $r * $percent / 100 ) ) );
    $g = min( 255, max( 0, $g + ( $g * $percent / 100 ) ) );
    $b = min( 255, max( 0, $b + ( $b * $percent / 100 ) ) );

    return sprintf( '#%02x%02x%02x', round( $r ), round( $g ), round( $b ) );
}

/**
 * Output custom colors as inline CSS
 */
public function output_custom_colors() {
    $custom_colors = get_option( 'ltl_saas_custom_colors', [] );
    if ( empty( $custom_colors ) ) {
        return;
    }

    echo '<style id="ltlb-custom-colors">';
    echo ':root {';

    foreach ( $custom_colors as $key => $color ) {
        echo '--ltlb-color-' . esc_attr( $key ) . ': ' . esc_attr( $color ) . ';';

        // Auto-generate light variants
        if ( in_array( $key, [ 'success', 'error', 'warning' ] ) ) {
            $light = $this->adjust_color_brightness( $color, 80 );
            echo '--ltlb-color-' . esc_attr( $key ) . '-light: ' . esc_attr( $light ) . ';';
        }

        // Auto-generate hover variant for primary
        if ( $key === 'primary' ) {
            $hover = $this->adjust_color_brightness( $color, -10 );
            echo '--ltlb-color-primary-hover: ' . esc_attr( $hover ) . ';';
        }
    }

    echo '}';
    echo '</style>';
}
```

**Integration**: Hook `output_custom_colors()` into both admin and frontend:
```php
add_action( 'admin_head', array( $this, 'output_custom_colors' ) );
add_action( 'wp_head', array( $this, 'output_custom_colors' ) );
```

**Features**:
- ✅ Live preview updates instantly (no page reload)
- ✅ Reset button for each color
- ✅ Hex value display (read-only)
- ✅ Auto-generates light variants (for badges, alerts)
- ✅ Auto-generates hover variants (for buttons)
- ✅ Sticky preview panel (stays visible while scrolling)
- ✅ Responsive layout (stacks on mobile)

---

### 1. Card Component

**Purpose**: Container for related content with optional header/footer

**Usage**:
```php
<div class="ltlb-card">
    <div class="ltlb-card__header">
        <h3 class="ltlb-card__title"><?php esc_html_e( 'Card Title', 'ltl-saas-portal' ); ?></h3>
        <span class="ltlb-badge ltlb-badge--success">Active</span>
    </div>
    <div class="ltlb-card__body">
        <p><?php esc_html_e( 'Card content goes here.', 'ltl-saas-portal' ); ?></p>
    </div>
    <div class="ltlb-card__footer">
        <button class="ltlb-btn ltlb-btn--primary"><?php esc_html_e( 'Primary Action', 'ltl-saas-portal' ); ?></button>
        <button class="ltlb-btn ltlb-btn--secondary"><?php esc_html_e( 'Secondary', 'ltl-saas-portal' ); ?></button>
    </div>
</div>
```

**CSS** (in `assets/frontend.css` or `assets/admin.css`):
```css
.ltlb-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-md);
    overflow: hidden;
    margin-bottom: var(--ltlb-gap-lg);
}

.ltlb-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--ltlb-gap-md);
    border-bottom: 1px solid #eee;
}

.ltlb-card__title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.ltlb-card__body {
    padding: var(--ltlb-gap-md);
}

.ltlb-card__footer {
    padding: var(--ltlb-gap-md);
    border-top: 1px solid #eee;
    background: #f8f9fa;
    display: flex;
    gap: var(--ltlb-gap-sm);
}

@media (max-width: 768px) {
    .ltlb-card__header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--ltlb-gap-sm);
    }

    .ltlb-card__footer {
        flex-direction: column;
    }
}
```

**Variants**:
- Without header: Omit `.ltlb-card__header`
- Without footer: Omit `.ltlb-card__footer`
- Highlight: Add `.ltlb-card--highlight` for primary color border

---

### 2. Button Component

**Purpose**: Primary/secondary actions

**Usage**:
```php
<!-- Primary action -->
<button type="submit" class="ltlb-btn ltlb-btn--primary" aria-label="<?php esc_attr_e( 'Save settings', 'ltl-saas-portal' ); ?>">
    <?php esc_html_e( 'Save Changes', 'ltl-saas-portal' ); ?>
</button>

<!-- Secondary action -->
<a href="<?php echo esc_url( admin_url( 'admin.php?page=ltl-saas-portal&tab=api' ) ); ?>" class="ltlb-btn ltlb-btn--secondary">
    <?php esc_html_e( 'Cancel', 'ltl-saas-portal' ); ?>
</a>

<!-- Danger action (delete) -->
<button type="button" class="ltlb-btn ltlb-btn--danger" onclick="confirmDelete()">
    <?php esc_html_e( 'Delete', 'ltl-saas-portal' ); ?>
</button>

<!-- Loading state -->
<button type="submit" class="ltlb-btn ltlb-btn--primary" disabled>
    <span class="ltlb-spinner"></span>
    <?php esc_html_e( 'Saving...', 'ltl-saas-portal' ); ?>
</button>
```

**CSS**:
```css
.ltlb-btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: var(--ltlb-radius-sm);
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: var(--ltlb-transition);
    font-size: 0.875rem;
    line-height: 1.5;
}

.ltlb-btn--primary {
    background: var(--ltlb-color-primary);
    color: white;
}

.ltlb-btn--primary:hover:not(:disabled) {
    background: #5568d3;
}

.ltlb-btn--secondary {
    background: white;
    color: var(--ltlb-color-primary);
    border: 1px solid var(--ltlb-color-primary);
}

.ltlb-btn--secondary:hover:not(:disabled) {
    background: #f0f4ff;
}

.ltlb-btn--danger {
    background: var(--ltlb-color-error);
    color: white;
}

.ltlb-btn--danger:hover:not(:disabled) {
    background: #c82333;
}

.ltlb-btn:focus {
    outline: 2px solid var(--ltlb-color-primary);
    outline-offset: 2px;
}

.ltlb-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ltlb-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: ltlb-spin 0.6s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}

@keyframes ltlb-spin {
    to { transform: rotate(360deg); }
}
```

---

### 3. Badge Component

**Purpose**: Status indicators (plan, active/inactive, counts)

**Usage**:
```php
<!-- Success badge -->
<span class="ltlb-badge ltlb-badge--success">
    <?php esc_html_e( 'Active', 'ltl-saas-portal' ); ?>
</span>

<!-- Warning badge -->
<span class="ltlb-badge ltlb-badge--warning">
    <?php esc_html_e( 'Trial', 'ltl-saas-portal' ); ?>
</span>

<!-- Error badge -->
<span class="ltlb-badge ltlb-badge--error">
    <?php esc_html_e( 'Expired', 'ltl-saas-portal' ); ?>
</span>

<!-- Count badge -->
<span class="ltlb-badge ltlb-badge--count">
    <?php echo esc_html( $post_count ); ?>/<?php echo esc_html( $limit ); ?>
</span>
```

**CSS**:
```css
.ltlb-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--ltlb-radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
}

.ltlb-badge--success {
    background: #d4edda;
    color: var(--ltlb-color-success);
}

.ltlb-badge--error {
    background: #f8d7da;
    color: var(--ltlb-color-error);
}

.ltlb-badge--warning {
    background: #fff3cd;
    color: #856404;
}

.ltlb-badge--count {
    background: #e3f2fd;
    color: #1976d2;
}
```

---

### 4. Section Component (Admin Only)

**Purpose**: Group related settings with collapsible header

**Usage**:
```php
<div class="ltlb-section">
    <div class="ltlb-section__header">
        <h3 class="ltlb-section__title"><?php esc_html_e( 'Make.com Setup', 'ltl-saas-portal' ); ?></h3>
        <p class="ltlb-section__description">
            <?php esc_html_e( 'Configure your Make.com integration tokens.', 'ltl-saas-portal' ); ?>
            <a href="<?php echo esc_url( 'https://github.com/user/repo/blob/main/docs/reference/api.md' ); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Learn more →', 'ltl-saas-portal' ); ?>
            </a>
        </p>
    </div>
    <table class="form-table">
        <!-- Settings fields go here -->
    </table>
</div>
```

**PHP Helper** (in `class-admin.php`):
```php
/**
 * Render section header
 *
 * @param string $title Section title
 * @param string $description Optional description
 * @param string $docs_link Optional documentation link
 */
private function render_section_header( $title, $description = '', $docs_link = '' ) {
    echo '<div class="ltlb-section">';
    echo '<div class="ltlb-section__header">';
    echo '<h3 class="ltlb-section__title">' . esc_html( $title ) . '</h3>';

    if ( $description || $docs_link ) {
        echo '<p class="ltlb-section__description">';
        if ( $description ) {
            echo esc_html( $description );
        }
        if ( $docs_link ) {
            echo ' <a href="' . esc_url( $docs_link ) . '" target="_blank" rel="noopener noreferrer">';
            echo esc_html__( 'Learn more →', 'ltl-saas-portal' );
            echo '</a>';
        }
        echo '</p>';
    }

    echo '</div>';
}

private function render_section_footer() {
    echo '</div>'; // .ltlb-section
}

// Usage:
$this->render_section_header(
    __( 'Make.com Setup', 'ltl-saas-portal' ),
    __( 'Configure your Make.com integration tokens.', 'ltl-saas-portal' ),
    'https://github.com/user/repo/blob/main/docs/reference/api.md'
);
echo '<table class="form-table">...</table>';
$this->render_section_footer();
```

**CSS**:
```css
.ltlb-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-md);
    padding: var(--ltlb-gap-lg);
    margin-bottom: var(--ltlb-gap-lg);
}

.ltlb-section__header {
    margin-bottom: var(--ltlb-gap-md);
    padding-bottom: var(--ltlb-gap-md);
    border-bottom: 1px solid #eee;
}

.ltlb-section__title {
    margin: 0 0 8px 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.ltlb-section__description {
    margin: 0;
    color: #666;
    font-size: 0.875rem;
}

.ltlb-section__description a {
    color: var(--ltlb-color-primary);
    text-decoration: none;
}

.ltlb-section__description a:hover {
    text-decoration: underline;
}
```

---

### 5. Secret Field Component (Admin Only)

**Purpose**: Display sensitive tokens with regenerate button

**Usage**:
```php
$this->render_secret_field( [
    'label' => __( 'Make Token', 'ltl-saas-portal' ),
    'description' => __( 'Keep this secret. Used for Make.com authentication.', 'ltl-saas-portal' ),
    'value_hint' => $token_set ? substr( $token, -4 ) : '',
    'is_set' => $token_set,
    'action' => 'ltl_saas_generate_token',
    'warning' => __( 'Regenerating will invalidate the old token. Update Make.com immediately after.', 'ltl-saas-portal' ),
] );
```

**PHP Helper**:
```php
/**
 * Render secret field (token/API key display + regenerate button)
 *
 * @param array $args {
 *     @type string $label Field label
 *     @type string $description Help text
 *     @type string $value_hint Last 4 chars (or empty)
 *     @type bool $is_set Whether secret is configured
 *     @type string $action Button action name (for form submit)
 *     @type string $warning Warning text shown below button
 * }
 */
private function render_secret_field( $args ) {
    $defaults = [
        'label' => '',
        'description' => '',
        'value_hint' => '',
        'is_set' => false,
        'action' => '',
        'warning' => '',
    ];
    $args = wp_parse_args( $args, $defaults );

    echo '<tr>';
    echo '<th scope="row">';
    echo '<label>' . esc_html( $args['label'] ) . '</label>';
    if ( $args['description'] ) {
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
    echo '</th>';
    echo '<td>';
    echo '<div class="ltlb-secret-field">';

    if ( $args['is_set'] ) {
        echo '<span class="ltlb-secret-field__status ltlb-secret-field__status--set">';
        echo '✓ ' . esc_html__( 'Secret set', 'ltl-saas-portal' );
        echo '</span>';
        echo ' <code class="ltlb-secret-field__hint">••••••' . esc_html( $args['value_hint'] ) . '</code>';
    } else {
        echo '<span class="ltlb-secret-field__status ltlb-secret-field__status--unset">';
        echo '⚠️ ' . esc_html__( 'Not configured', 'ltl-saas-portal' );
        echo '</span>';
    }

    if ( $args['action'] ) {
        echo '<button type="submit" name="' . esc_attr( $args['action'] ) . '" class="button ltlb-secret-regenerate">';
        echo '🔄 ' . esc_html__( 'Regenerate', 'ltl-saas-portal' );
        echo '</button>';
    }

    echo '</div>';

    if ( $args['warning'] ) {
        echo '<p class="description ltlb-secret-field__warning">';
        echo '⚠️ ' . esc_html( $args['warning'] );
        echo '</p>';
    }

    echo '</td>';
    echo '</tr>';
}
```

**CSS**:
```css
.ltlb-secret-field {
    display: flex;
    align-items: center;
    gap: var(--ltlb-gap-md);
}

.ltlb-secret-field__status {
    font-weight: 600;
}

.ltlb-secret-field__status--set {
    color: var(--ltlb-color-success);
}

.ltlb-secret-field__status--unset {
    color: var(--ltlb-color-warning);
}

.ltlb-secret-field__hint {
    background: #f0f0f0;
    padding: 4px 8px;
    border-radius: var(--ltlb-radius-sm);
    font-family: 'Courier New', monospace;
}

.ltlb-secret-regenerate {
    background: var(--ltlb-color-primary);
    color: white;
    border: none;
}

.ltlb-secret-regenerate:hover {
    background: #5568d3;
}

.ltlb-secret-field__warning {
    margin-top: var(--ltlb-gap-sm);
    color: var(--ltlb-color-warning);
}
```

---

### 6. Notice Component (Admin Only)

**Purpose**: Success/error/warning messages after actions

**Usage**:
```php
// Success
$this->show_notice( __( 'Settings saved successfully!', 'ltl-saas-portal' ), 'success' );

// Error
$this->show_notice( __( 'Invalid JSON format. Please check your input.', 'ltl-saas-portal' ), 'error' );

// Warning
$this->show_notice( __( 'Your trial expires in 3 days.', 'ltl-saas-portal' ), 'warning' );

// Info
$this->show_notice( __( 'New version available.', 'ltl-saas-portal' ), 'info' );
```

**PHP Helper**:
```php
/**
 * Display admin notice
 *
 * @param string $message Notice message
 * @param string $type success|error|warning|info
 * @param bool $dismissible Is dismissible
 */
private function show_notice( $message, $type = 'success', $dismissible = true ) {
    $class = 'notice notice-' . $type . ' ltlb-notice';
    if ( $dismissible ) {
        $class .= ' is-dismissible';
    }

    echo '<div class="' . esc_attr( $class ) . '">';
    echo '<p>' . wp_kses_post( $message ) . '</p>';
    echo '</div>';
}
```

**CSS**:
```css
.ltlb-notice {
    margin-top: var(--ltlb-gap-md);
}

.ltlb-notice p strong {
    font-weight: 600;
}
```

---

### 7. Progress Tracker Component (Frontend)

**Purpose**: Onboarding checklist for customers

**Usage**:
```php
<div class="ltlb-progress">
    <div class="ltlb-progress__header">
        <h2><?php esc_html_e( 'Your Setup Progress', 'ltl-saas-portal' ); ?></h2>
    </div>

    <!-- Step 1 -->
    <div class="ltlb-progress__item <?php echo $wp_connected ? 'ltlb-progress__item--complete' : 'ltlb-progress__item--incomplete'; ?>">
        <div class="ltlb-progress__icon">
            <?php echo $wp_connected ? '✅' : '⚠️'; ?>
        </div>
        <div class="ltlb-progress__content">
            <h4><?php esc_html_e( 'WordPress Connected', 'ltl-saas-portal' ); ?></h4>
            <p>
                <?php
                if ( $wp_connected ) {
                    esc_html_e( 'Connected', 'ltl-saas-portal' );
                } else {
                    esc_html_e( 'Not configured', 'ltl-saas-portal' );
                }
                ?>
            </p>
        </div>
        <div class="ltlb-progress__action">
            <a href="#wp-connection" class="ltlb-btn <?php echo $wp_connected ? 'ltlb-btn--secondary' : 'ltlb-btn--primary'; ?>">
                <?php echo $wp_connected ? esc_html__( 'Edit', 'ltl-saas-portal' ) : esc_html__( 'Configure Now', 'ltl-saas-portal' ); ?>
            </a>
        </div>
    </div>

    <!-- Additional steps... -->
</div>
```

**CSS**:
```css
.ltlb-progress {
    background: white;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-md);
    padding: var(--ltlb-gap-lg);
    margin-bottom: var(--ltlb-gap-lg);
}

.ltlb-progress__header {
    margin-bottom: var(--ltlb-gap-md);
    padding-bottom: var(--ltlb-gap-md);
    border-bottom: 1px solid #eee;
}

.ltlb-progress__header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.ltlb-progress__item {
    display: flex;
    align-items: center;
    gap: var(--ltlb-gap-md);
    padding: var(--ltlb-gap-md) 0;
    border-bottom: 1px solid #eee;
}

.ltlb-progress__item:last-child {
    border-bottom: none;
}

.ltlb-progress__item--complete .ltlb-progress__icon {
    color: var(--ltlb-color-success);
}

.ltlb-progress__item--incomplete .ltlb-progress__icon {
    color: var(--ltlb-color-warning);
}

.ltlb-progress__icon {
    font-size: 1.5em;
    flex-shrink: 0;
}

.ltlb-progress__content {
    flex: 1;
}

.ltlb-progress__content h4 {
    margin: 0 0 4px 0;
    font-size: 1rem;
    font-weight: 600;
}

.ltlb-progress__content p {
    margin: 0;
    color: #666;
    font-size: 0.875rem;
}

.ltlb-progress__action {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .ltlb-progress__item {
        flex-direction: column;
        align-items: flex-start;
    }
}
```

---

### 8. Feedback Component (Frontend)

**Purpose**: Show AJAX response messages (test button results)

**Usage**:
```html
<!-- In template -->
<div id="ltl-saas-test-result" class="ltlb-feedback" role="alert" style="display:none;"></div>
```

**JavaScript**:
```js
// Helper function in assets/frontend.js
function showFeedback($el, type, message) {
    $el.removeClass('ltlb-feedback--success ltlb-feedback--error ltlb-feedback--loading')
       .addClass('ltlb-feedback--' + type)
       .html('<p>' + message + '</p>')
       .show()
       .attr('role', 'alert');

    // Auto-hide after 5s (success only)
    if (type === 'success') {
        setTimeout(function() {
            $el.fadeOut();
        }, 5000);
    }
}

// Usage:
showFeedback($('#ltl-saas-test-result'), 'success', '✅ Connection successful!');
showFeedback($('#ltl-saas-test-result'), 'error', '❌ Invalid credentials');
showFeedback($('#ltl-saas-test-result'), 'loading', '🔄 Testing...');
```

**CSS**:
```css
.ltlb-feedback {
    margin-top: 8px;
    padding: 8px 12px;
    border-radius: var(--ltlb-radius-sm);
    font-size: 0.875rem;
}

.ltlb-feedback--loading {
    background: #e3f2fd;
    color: #1976d2;
}

.ltlb-feedback--success {
    background: #d4edda;
    color: var(--ltlb-color-success);
}

.ltlb-feedback--error {
    background: #f8d7da;
    color: var(--ltlb-color-error);
}

.ltlb-feedback p {
    margin: 0;
}
```

---

### 9. Pricing Card Component (Frontend)

**Purpose**: Display pricing tiers on marketing pages

**Usage** (in `templates/pricing/card-grid.php`):
```php
<div class="ltlb-pricing-grid">
    <?php foreach ( $plans as $plan ) : ?>
    <div class="ltlb-pricing-card <?php echo $plan['highlight'] ? 'ltlb-pricing-card--highlight' : ''; ?>">
        <div class="ltlb-pricing-card__header">
            <h3 class="ltlb-pricing-card__title"><?php echo esc_html( $plan['name'] ); ?></h3>
            <?php if ( $plan['highlight'] ) : ?>
            <span class="ltlb-badge ltlb-badge--success"><?php esc_html_e( 'Popular', 'ltl-saas-portal' ); ?></span>
            <?php endif; ?>
        </div>
        <div class="ltlb-pricing-card__price">
            <span class="ltlb-pricing-card__amount"><?php echo esc_html( $plan['price'] ); ?></span>
            <span class="ltlb-pricing-card__period"><?php esc_html_e( '/month', 'ltl-saas-portal' ); ?></span>
        </div>
        <ul class="ltlb-pricing-card__features">
            <?php foreach ( $plan['features'] as $feature ) : ?>
            <li><?php echo esc_html( $feature ); ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="ltlb-pricing-card__footer">
            <a href="<?php echo esc_url( $plan['cta_url'] ); ?>" class="ltlb-btn <?php echo $plan['highlight'] ? 'ltlb-btn--primary' : 'ltlb-btn--secondary'; ?>">
                <?php echo esc_html( $plan['cta_text'] ); ?>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
```

**CSS**:
```css
.ltlb-pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--ltlb-gap-lg);
    margin-bottom: var(--ltlb-gap-lg);
}

.ltlb-pricing-card {
    background: white;
    border: 2px solid #ddd;
    border-radius: var(--ltlb-radius-md);
    padding: var(--ltlb-gap-lg);
    text-align: center;
    transition: var(--ltlb-transition);
}

.ltlb-pricing-card:hover {
    box-shadow: var(--ltlb-shadow-md);
    transform: translateY(-4px);
}

.ltlb-pricing-card--highlight {
    border-color: var(--ltlb-color-primary);
    position: relative;
}

.ltlb-pricing-card__header {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--ltlb-gap-sm);
    margin-bottom: var(--ltlb-gap-md);
}

.ltlb-pricing-card__title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.ltlb-pricing-card__price {
    margin-bottom: var(--ltlb-gap-md);
}

.ltlb-pricing-card__amount {
    font-size: 3rem;
    font-weight: 700;
    color: var(--ltlb-color-primary);
}

.ltlb-pricing-card__period {
    color: #666;
    font-size: 1rem;
}

.ltlb-pricing-card__features {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--ltlb-gap-lg) 0;
    text-align: left;
}

.ltlb-pricing-card__features li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.ltlb-pricing-card__features li:before {
    content: '✓ ';
    color: var(--ltlb-color-success);
    font-weight: 700;
    margin-right: 8px;
}

.ltlb-pricing-card__footer {
    margin-top: var(--ltlb-gap-md);
}

.ltlb-pricing-card__footer .ltlb-btn {
    width: 100%;
}

@media (max-width: 768px) {
    .ltlb-pricing-grid {
        grid-template-columns: 1fr;
    }
}
```

---

### 10. Form Field Helper

**Purpose**: Consistent form field rendering with validation states

**Usage**:
```php
$this->render_field( [
    'type' => 'text',
    'id' => 'wp_url',
    'name' => 'wp_url',
    'label' => __( 'WordPress URL', 'ltl-saas-portal' ),
    'description' => __( 'Your site URL, e.g., https://example.com', 'ltl-saas-portal' ),
    'value' => $current_value,
    'placeholder' => 'https://example.com',
    'required' => true,
    'error' => $error_message ?? '',
] );
```

**PHP Helper**:
```php
/**
 * Render form field with label and help text
 *
 * @param array $args Field configuration
 */
private function render_field( $args ) {
    $defaults = [
        'type' => 'text',
        'id' => '',
        'name' => '',
        'label' => '',
        'description' => '',
        'value' => '',
        'placeholder' => '',
        'required' => false,
        'error' => '',
        'options' => [], // For select/radio
    ];
    $args = wp_parse_args( $args, $defaults );

    $error_class = $args['error'] ? 'ltlb-field--error' : '';

    echo '<div class="ltlb-field ' . esc_attr( $error_class ) . '">';

    // Label
    if ( $args['label'] ) {
        echo '<label for="' . esc_attr( $args['id'] ) . '" class="ltlb-field__label">';
        echo esc_html( $args['label'] );
        if ( $args['required'] ) {
            echo ' <span class="ltlb-field__required">*</span>';
        }
        echo '</label>';
    }

    // Input
    if ( $args['type'] === 'textarea' ) {
        echo '<textarea id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '" class="ltlb-field__input" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( $args['required'] ? 'required' : '' ) . '>';
        echo esc_textarea( $args['value'] );
        echo '</textarea>';
    } elseif ( $args['type'] === 'select' ) {
        echo '<select id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '" class="ltlb-field__input">';
        foreach ( $args['options'] as $value => $label ) {
            echo '<option value="' . esc_attr( $value ) . '" ' . selected( $args['value'], $value, false ) . '>';
            echo esc_html( $label );
            echo '</option>';
        }
        echo '</select>';
    } else {
        echo '<input type="' . esc_attr( $args['type'] ) . '" id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '" class="ltlb-field__input" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( $args['required'] ? 'required' : '' ) . ' />';
    }

    // Description
    if ( $args['description'] ) {
        echo '<p class="ltlb-field__description">' . esc_html( $args['description'] ) . '</p>';
    }

    // Error
    if ( $args['error'] ) {
        echo '<p class="ltlb-field__error" role="alert">' . esc_html( $args['error'] ) . '</p>';
    }

    echo '</div>';
}
```

**CSS**:
```css
.ltlb-field {
    margin-bottom: var(--ltlb-gap-md);
}

.ltlb-field__label {
    display: block;
    margin-bottom: 4px;
    font-weight: 600;
    font-size: 0.875rem;
}

.ltlb-field__required {
    color: var(--ltlb-color-error);
}

.ltlb-field__input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: var(--ltlb-radius-sm);
    font-size: 1rem;
    transition: var(--ltlb-transition);
}

.ltlb-field__input:focus {
    outline: none;
    border-color: var(--ltlb-color-primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.ltlb-field--error .ltlb-field__input {
    border-color: var(--ltlb-color-error);
}

.ltlb-field__description {
    margin: 4px 0 0 0;
    color: #666;
    font-size: 0.75rem;
}

.ltlb-field__error {
    margin: 4px 0 0 0;
    color: var(--ltlb-color-error);
    font-size: 0.75rem;
    font-weight: 600;
}
```

---

### 11. Tab Navigation (Admin Only)

**Purpose**: Split settings into logical tabs

**Usage**:
```php
// In render_admin_page()
$this->render_tabs();

$current_tab = $this->get_current_tab();

switch ( $current_tab ) {
    case 'api':
        $this->render_tab_api();
        break;
    case 'billing':
        $this->render_tab_billing();
        break;
    case 'marketing':
        $this->render_tab_marketing();
        break;
}
```

**PHP Helper**:
```php
private function get_current_tab() {
    return isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'api';
}

private function render_tabs() {
    $current = $this->get_current_tab();
    $tabs = [
        'api' => __( 'API & Integrations', 'ltl-saas-portal' ),
        'billing' => __( 'Billing', 'ltl-saas-portal' ),
        'marketing' => __( 'Marketing', 'ltl-saas-portal' ),
    ];

    echo '<h2 class="nav-tab-wrapper ltlb-nav-tabs">';
    foreach ( $tabs as $slug => $label ) {
        $active = $current === $slug ? 'nav-tab-active' : '';
        $url = admin_url( 'admin.php?page=ltl-saas-portal&tab=' . $slug );
        echo '<a href="' . esc_url( $url ) . '" class="nav-tab ' . $active . '">';
        echo esc_html( $label );
        echo '</a>';
    }
    echo '</h2>';
}
```

**CSS**:
```css
.ltlb-nav-tabs {
    margin-bottom: var(--ltlb-gap-lg);
}
```

---

## Design Tokens (CSS Variables)

All components use these variables defined in `:root`:

```css
:root {
    /* Colors */
    --ltlb-color-primary: #667eea;
    --ltlb-color-primary-hover: #5568d3;
    --ltlb-color-success: #28a745;
    --ltlb-color-success-light: #d4edda;
    --ltlb-color-error: #dc3545;
    --ltlb-color-error-light: #f8d7da;
    --ltlb-color-warning: #ffc107;
    --ltlb-color-warning-light: #fff3cd;
    --ltlb-color-info: #17a2b8;

    /* Spacing */
    --ltlb-gap-sm: 8px;
    --ltlb-gap-md: 16px;
    --ltlb-gap-lg: 24px;
    --ltlb-gap-xl: 32px;

    /* Border Radius */
    --ltlb-radius-sm: 4px;
    --ltlb-radius-md: 8px;
    --ltlb-radius-lg: 12px;

    /* Shadows */
    --ltlb-shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
    --ltlb-shadow-md: 0 4px 12px rgba(0,0,0,0.15);
    --ltlb-shadow-lg: 0 8px 24px rgba(0,0,0,0.2);

    /* Typography */
    --ltlb-font-size-sm: 0.75rem;
    --ltlb-font-size-md: 0.875rem;
    --ltlb-font-size-lg: 1rem;
    --ltlb-font-size-xl: 1.25rem;

    /* Transitions */
    --ltlb-transition: 0.2s ease-in-out;
}
```

---

## Component Usage Checklist

Before using a component, ensure:

- [ ] **Sanitize**: All dynamic data sanitized (`sanitize_text_field()`, `esc_html()`, etc.)
- [ ] **Escape**: All output escaped (`esc_html()`, `esc_attr()`, `esc_url()`)
- [ ] **Translate**: All text wrapped in `__()` or `esc_html_e()`
- [ ] **Nonce**: Forms include nonce field (`wp_nonce_field()`)
- [ ] **Validate**: User input validated before processing
- [ ] **ARIA**: Interactive elements have proper ARIA labels
- [ ] **Keyboard**: All interactions work with keyboard only
- [ ] **Responsive**: Test on mobile (< 768px) and desktop

---

## Testing Components

1. **Visual Regression**: Compare before/after screenshots
2. **Browser Testing**: Chrome, Firefox, Safari, Edge
3. **Accessibility**: Run aXe DevTools audit
4. **Keyboard Nav**: Tab through all interactive elements
5. **Screen Reader**: Test with NVDA (Windows) or VoiceOver (Mac)
6. **Theme Compat**: Test with default WordPress themes + popular themes

---

**End of Component Library**
