/**
 * Copyright © Webjump. All rights reserved.
 */

define([
    'mage/utils/wrapper',
    'uiRegistry'
], function (wrapper, registry) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            payload = originalAction(payload);

            var commentValue = '';
            var el = document.getElementById('spooky-order-comment') || document.querySelector('[name*="spooky_order_comment"]');
            var commentComponent = registry.get('checkout.steps.shipping-step.shippingAddress.spooky-order-comment-fieldset.spooky_order_comment');

            if (el && typeof el.value === 'string' && el.value.length > 0) {
                commentValue = el.value;
            } else if (commentComponent && typeof commentComponent.value === 'function') {
                commentValue = commentComponent.value() || '';
            }

            if (!payload.addressInformation['extension_attributes']) {
                payload.addressInformation['extension_attributes'] = {};
            }

            payload.addressInformation['extension_attributes']['spooky_order_comment'] = commentValue;

            return payload;
        });
    };
});
