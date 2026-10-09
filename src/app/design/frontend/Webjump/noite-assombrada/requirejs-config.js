/**
 * Copyright © Webjump. All rights reserved.
 * RequireJS configuration for Noite Assombrada theme.
 * Challenge 17.2: Haunted Mode (deps) and Minicart Mixin (mixins).
 * Challenge 17.5: Spooky Shake Knockout custom binding (deps).
 */
var config = {
    deps: [
        'Magento_Theme/js/haunted-mode',
        'Magento_Theme/js/bindings/spooky-shake'
    ],
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Magento_Checkout/js/view/minicart-mixin': true
            }
        }
    }
};
