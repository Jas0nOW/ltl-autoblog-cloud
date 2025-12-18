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

        // Update preview when color changes
        $('.ltlb-color-picker').on('input change', function() {
            const $picker = $(this);
            const color = $picker.val();
            const key = $picker.data('color-key');

            // Update hex display
            $picker.siblings('.ltlb-color-hex').val(color);

            // Update CSS variable in preview
            updatePreviewColor(key, color);
        });

        // Reset color button
        $('.ltlb-reset-color').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const defaultColor = $btn.data('default');
            const targetId = $btn.data('target');
            const $picker = $('#' + targetId);
            const key = $picker.data('color-key');

            $picker.val(defaultColor).trigger('change');
            updatePreviewColor(key, defaultColor);
        });

        /**
         * Update preview color in real-time
         *
         * @param {string} key Color key (primary, success, error, warning)
         * @param {string} color Hex color value
         */
        function updatePreviewColor(key, color) {
            const previewArea = $('.ltlb-color-customizer__preview')[0];
            if (!previewArea) return;

            // Update CSS variable in preview scope
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
