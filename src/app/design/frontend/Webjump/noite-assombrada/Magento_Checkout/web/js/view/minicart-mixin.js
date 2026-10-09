/**
 * Copyright © Webjump. All rights reserved.
 * RequireJS Mixin extending Magento_Checkout/js/view/minicart.
 * Adds dynamic Halloween campaign computed observable without modifying core files.
 * Challenge 17.2
 */
define([
    'ko',
    'mage/translate'
], function (ko, $t) {
    'use strict';

    return function (target) {
        return target.extend({
            /**
             * @inheritdoc
             */
            initialize: function () {
                var self = this;
                var res = this._super();

                /**
                 * Computed observable reacting to cart summary_count changes.
                 * Updates campaign cauldron message in real time without page reloads.
                 *
                 * @type {ko.computed}
                 */
                this.halloweenMessage = ko.computed(function () {
                    var count = self.getCartParam('summary_count');
                    var qty = parseInt(count, 10) || 0;

                    if (qty === 0) {
                        return $t('Seu caldeirão está vazio e frio... Adicione feitiços antes que a meia-noite chegue!');
                    } else if (qty === 1) {
                        return $t('1 poção mágica no caldeirão! O feitiço assombroso começou a borbulhar...');
                    } else if (qty < 4) {
                        return $t('O caldeirão está fervendo com %1 poções assombrosas! A névoa de Halloween se espalha.').replace('%1', qty);
                    } else {
                        return $t('Poder sobrenatural transbordando! %1 itens enfeitiçados prontos para o ritual!').replace('%1', qty);
                    }
                });

                return res;
            }
        });
    };
});
