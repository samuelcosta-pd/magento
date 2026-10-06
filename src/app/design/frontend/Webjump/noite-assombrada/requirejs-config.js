var config = {
    deps: [
        'Magento_Theme/js/haunted-mode'
    ],
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Magento_Checkout/js/view/minicart-mixin': true
            }
        }
    }
};
