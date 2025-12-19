/**
 * LTL AutoBlog Cloud - Admin JavaScript
 *
 * @package LTL_SAAS_Portal
 * @version 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('LTL Admin JS loaded');

        // ============================================
        // Color Customizer - Live Preview
        // ============================================

        // Update preview when BACKEND color changes
        $('.ltlb-color-picker').on('input change', function() {
            const $picker = $(this);
            const color = $picker.val();
            const key = $picker.data('color-key');

            // Update hex display
            $picker.siblings('.ltlb-color-hex').val(color);

            // Update CSS variable in backend preview
            updatePreviewColor(key, color, 'backend');
        });

        // Update preview when FRONTEND color changes
        $('.ltlb-color-picker-frontend').on('input change', function() {
            const $picker = $(this);
            const color = $picker.val();
            const key = $picker.data('color-key');

            // Update hex display
            $picker.siblings('.ltlb-color-hex').val(color);

            // Update CSS variable in frontend preview
            updatePreviewColor(key, color, 'frontend');
        });

        // Reset color button
        $('.ltlb-reset-color').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const defaultColor = $btn.data('default');
            const targetId = $btn.data('target');
            const $picker = $('#' + targetId);
            const key = $picker.data('color-key');

            // Detect context from picker ID
            const context = targetId.startsWith('frontend_') ? 'frontend' : 'backend';

            $picker.val(defaultColor).trigger('change');
            updatePreviewColor(key, defaultColor, context);
        });

        /**
         * Update preview color in real-time
         *
         * @param {string} key Color key (primary, success, error, warning, form_bg)
         * @param {string} color Hex color value
         * @param {string} context 'backend' or 'frontend'
         */
        function updatePreviewColor(key, color, context) {
            let previewArea;

            if (context === 'frontend') {
                previewArea = document.getElementById('ltlb-frontend-preview');
            } else {
                previewArea = document.getElementById('ltlb-backend-preview');
            }

            if (!previewArea) {
                console.log('Preview not found:', context);
                return;
            }

            // For frontend, use -frontend suffix in variable names
            // For backend, use no suffix
            const suffix = context === 'frontend' ? '-frontend' : '';

            // Update CSS variable in preview scope
            switch(key) {
                case 'primary':
                    const hover = adjustColorBrightness(color, -10);
                    const light = adjustColorBrightness(color, 80);
                    previewArea.style.setProperty('--ltlb-color-primary' + suffix, color);
                    previewArea.style.setProperty('--ltlb-color-primary-hover' + suffix, hover);
                    previewArea.style.setProperty('--ltlb-color-primary-light' + suffix, light);
                    previewArea.style.setProperty('--ltlb-color-primary-gradient' + suffix, `linear-gradient(135deg, ${color} 0%, ${hover} 100%)`);
                    previewArea.style.setProperty('--ltlb-color-primary-rgb' + suffix, hexToRgbComponents(color));
                    break;
                case 'success':
                    previewArea.style.setProperty('--ltlb-color-success' + suffix, color);
                    previewArea.style.setProperty('--ltlb-color-success-light' + suffix, adjustColorBrightness(color, 80));
                    break;
                case 'error':
                    previewArea.style.setProperty('--ltlb-color-error' + suffix, color);
                    previewArea.style.setProperty('--ltlb-color-error-light' + suffix, adjustColorBrightness(color, 80));
                    break;
                case 'warning':
                    previewArea.style.setProperty('--ltlb-color-warning' + suffix, color);
                    previewArea.style.setProperty('--ltlb-color-warning-light' + suffix, adjustColorBrightness(color, 80));
                    break;
                case 'form_bg':
                    // Direkter inline style für background mit !important Gewalt
                    previewArea.style.setProperty('background-color', color, 'important');
                    // Auch CSS-Variable setzen für andere Elemente
                    previewArea.style.setProperty('--ltlb-color-form-bg' + suffix, color);
                    previewArea.style.setProperty('--ltlb-color-form-text' + suffix, getContrastingColor(color));
                    break;
            }
        }

        /**
         * Calculate contrasting text color based on background luminance
         *
         * @param {string} hex Hex color value
         * @return {string} Contrasting color (#ffffff or #1a1a1a)
         */
        function getContrastingColor(hex) {
            hex = hex.replace('#', '');

            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);

            // Calculate luminance
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

            return luminance > 0.5 ? '#1a1a1a' : '#ffffff';
        }

        /**
         * Adjust color brightness for auto-generating variants
         *
         * @param {string} hex Hex color value
         * @param {number} percent Brightness adjustment (-100 to 100)
         * @return {string} Adjusted hex color
         */
        function adjustColorBrightness(hex, percent) {
            // Remove # if present
            hex = hex.replace('#', '');

            // Convert to RGB
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);

            // Adjust brightness
            r = Math.min(255, Math.max(0, r + (r * percent / 100)));
            g = Math.min(255, Math.max(0, g + (g * percent / 100)));
            b = Math.min(255, Math.max(0, b + (b * percent / 100)));

            // Convert back to hex
            return '#' +
                Math.round(r).toString(16).padStart(2, '0') +
                Math.round(g).toString(16).padStart(2, '0') +
                Math.round(b).toString(16).padStart(2, '0');
        }

        /**
         * Convert hex color to RGB components for rgba()
         *
         * @param {string} hex Hex color value
         * @return {string} RGB components as "r, g, b"
         */
        function hexToRgbComponents(hex) {
            hex = hex.replace('#', '');

            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }

            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);

            return r + ', ' + g + ', ' + b;
        }

        // ============================================
        // Token/Secret Regeneration Confirmation
        // ============================================

        $('button[name="ltl_saas_generate_token"], button[name="ltl_saas_generate_api_key"]').on('click', function(e) {
            if (!confirm(ltlbAdmin.strings.confirm_regenerate)) {
                e.preventDefault();
                return false;
            }
        });
    });
})(jQuery);
