/**
 * WC Ultra Suite - Frontend Addons JavaScript
 * Handles dynamic price updates and validation
 */

(function ($) {
    'use strict';

    const FrontendAddons = {

        /**
         * Initialize
         */
        init() {
            this.updatePriceDisplay();
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents() {
            // Listen for addon field changes
            $('.wc-ultra-suite-addon-field input, .wc-ultra-suite-addon-field select, .wc-ultra-suite-addon-field textarea').on('change', () => {
                this.updatePriceDisplay();
            });
        },

        /**
         * Update price display
         */
        updatePriceDisplay() {
            let totalAddonPrice = 0;

            // Calculate total addon price
            $('.wc-ultra-suite-addon-field').each(function () {
                const $field = $(this).find('input, select, textarea');
                const price = parseFloat($field.data('price')) || 0;

                // Check if field is selected/filled
                if ($field.is(':checkbox') && $field.is(':checked')) {
                    totalAddonPrice += price;
                } else if ($field.is(':radio') && $field.is(':checked')) {
                    totalAddonPrice += price;
                } else if ($field.is('select') && $field.val()) {
                    totalAddonPrice += price;
                } else if (($field.is('input[type="text"]') || $field.is('textarea')) && $field.val()) {
                    totalAddonPrice += price;
                }
            });

            // Update the displayed price (if WooCommerce has a price element)
            if (totalAddonPrice > 0) {
                this.showAddonTotal(totalAddonPrice);
            } else {
                this.hideAddonTotal();
            }
        },

        /**
         * Show addon total
         */
        showAddonTotal(amount) {
            const symbol = wcUltraSuiteFrontend.currencySymbol || '$';
            const formatted = symbol + amount.toFixed(2);

            // Remove existing addon total if present
            $('.wc-ultra-addon-total').remove();

            // Add addon total display
            $('.wc-ultra-suite-addons').append(`
                <div class="wc-ultra-addon-total" style="
                    margin-top: 1.5rem;
                    padding: 1rem;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: white;
                    border-radius: 0.5rem;
                    font-weight: 700;
                    text-align: center;
                    font-size: 1.1rem;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                ">
                    ✨ Additional Cost: ${formatted}
                </div>
            `);
        },

        /**
         * Hide addon total
         */
        hideAddonTotal() {
            $('.wc-ultra-addon-total').remove();
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('.wc-ultra-suite-addons').length) {
            FrontendAddons.init();
        }
    });

})(jQuery);
