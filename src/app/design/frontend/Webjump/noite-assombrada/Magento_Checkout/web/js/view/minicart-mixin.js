/**
 * Copyright © Webjump. All rights reserved.
 * Desafio 17.2: Mixin do Minicart com Mensagens Reativas do Caldeirão
 */
define([
    'ko',
    'mage/translate'
], function (ko, $t) {
    'use strict';

    return function (target) {
        return target.extend({
            /**
             * @override
             */
            initialize: function () {
                var self = this;
                this._super();

                /**
                 * Computed observable que atualiza a mensagem temática
                 * reativamente de acordo com a quantidade total de itens no carrinho
                 */
                this.hauntedCartMessage = ko.computed(function () {
                    var count = self.getCartParam('summary_count');
                    count = parseInt(count, 10);
                    if (isNaN(count)) {
                        count = 0;
                    }

                    if (count === 0) {
                        return $t('Seu caldeirão está vazio e frio... Nenhum feitiço adicionado ainda! 👻');
                    } else if (count === 1) {
                        return $t('1 item borbulhando no caldeirão... O ritual começou! 🎃');
                    } else if (count < 5) {
                        return $t('%1 itens encantados no caldeirão... A poção está ganhando força! 🧪').replace('%1', count);
                    } else {
                        return $t('%1 itens no caldeirão! Cuidado, a poção pode transbordar feitiços! 🧙‍♀️⚡').replace('%1', count);
                    }
                });

                return this;
            }
        });
    };
});
