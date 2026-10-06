/**
 * Copyright © Webjump. All rights reserved.
 */

define([
    'uiComponent',
    'ko',
    'mage/translate'
], function (Component, ko, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Theme/countdown',
            targetDate: '2026-10-31T23:59:59',
            titleText: 'Ofertas da Noite Assombrada encerram em:',
            expiredText: 'Os feitiços acabaram! Campanha Noite Assombrada encerrada.'
        },

        /**
         * Inicializa o componente Knockout.
         */
        initialize: function () {
            this._super();

            this.days = ko.observable('00');
            this.hours = ko.observable('00');
            this.minutes = ko.observable('00');
            this.seconds = ko.observable('00');
            this.isExpired = ko.observable(false);

            // Computed formatando a mensagem dependendo do estado do contador
            this.formattedMessage = ko.computed(function () {
                if (this.isExpired()) {
                    return $t(this.expiredText);
                }
                return $t(this.titleText);
            }, this);

            this.startCountdown();

            return this;
        },

        /**
         * Inicia o intervalo de contagem regressiva ao vivo a cada segundo.
         */
        startCountdown: function () {
            this.updateTime();
            this.timer = setInterval(this.updateTime.bind(this), 1000);
        },

        /**
         * Calcula a diferença de tempo e atualiza os observables.
         */
        updateTime: function () {
            var targetTime = Date.parse(this.targetDate);
            if (isNaN(targetTime)) {
                targetTime = new Date(this.targetDate.replace(/-/g, '/')).getTime();
            }

            var currentTime = new Date().getTime();
            var diff = targetTime - currentTime;

            // Tratamento estrito: se a data já passou, não exibe números negativos
            if (diff <= 0) {
                this.isExpired(true);
                this.days('00');
                this.hours('00');
                this.minutes('00');
                this.seconds('00');

                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
                return;
            }

            this.isExpired(false);

            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            var minutes = Math.floor((diff / 1000 / 60) % 60);
            var seconds = Math.floor((diff / 1000) % 60);

            this.days(this.padZero(days));
            this.hours(this.padZero(hours));
            this.minutes(this.padZero(minutes));
            this.seconds(this.padZero(seconds));
        },

        /**
         * Formata o número com zero à esquerda.
         *
         * @param {number} num
         * @return {string}
         */
        padZero: function (num) {
            return num < 10 ? '0' + num : num.toString();
        }
    });
});
