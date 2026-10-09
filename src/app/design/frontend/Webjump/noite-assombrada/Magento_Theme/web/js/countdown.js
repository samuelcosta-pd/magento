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
            title: $t('Ofertas de Halloween terminam em:'),
            expiredTitle: $t('Campanha de Halloween Encerrada!'),
            expiredMessage: $t('A Noite Assombrada chegou ao fim! As ofertas foram encerradas.')
        },

        /**
         * Inicializa observables e computeds reativos
         */
        initObservable: function () {
            this._super();

            var self = this;

            // Permite simulação e testes de encerramento via query parameter (?expired=1)
            if (window.location.search.indexOf('expired=1') !== -1) {
                this.targetDate = '2020-10-31T23:59:59';
            }

            // Observable reativo com o timestamp presente (milissegundos)
            this.now = ko.observable(Date.now());

            // Computed avaliando o intervalo temporal restante em milissegundos
            this.distance = ko.computed(function () {
                var target = new Date(self.targetDate).getTime();
                return target - self.now();
            });

            // Flag reativa identificando se a data final já passou
            this.isExpired = ko.computed(function () {
                return self.distance() <= 0;
            });

            // Dias restantes formatados com zero à esquerda (ou 00 se expirado)
            this.days = ko.computed(function () {
                if (self.isExpired()) {
                    return '00';
                }
                var d = Math.floor(self.distance() / (1000 * 60 * 60 * 24));
                return d < 10 ? '0' + d : String(d);
            });

            // Horas restantes formatadas
            this.hours = ko.computed(function () {
                if (self.isExpired()) {
                    return '00';
                }
                var h = Math.floor((self.distance() % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                return h < 10 ? '0' + h : String(h);
            });

            // Minutos restantes formatados
            this.minutes = ko.computed(function () {
                if (self.isExpired()) {
                    return '00';
                }
                var m = Math.floor((self.distance() % (1000 * 60 * 60)) / (1000 * 60));
                return m < 10 ? '0' + m : String(m);
            });

            // Segundos restantes formatados
            this.seconds = ko.computed(function () {
                if (self.isExpired()) {
                    return '00';
                }
                var s = Math.floor((self.distance() % (1000 * 60)) / 1000);
                return s < 10 ? '0' + s : String(s);
            });

            // Computed formatando a mensagem completa (amigável quando expirado, sem números negativos)
            this.formattedMessage = ko.computed(function () {
                if (self.isExpired()) {
                    return self.expiredMessage;
                }
                return self.days() + ' ' + $t('Dias') + ' : ' +
                       self.hours() + ' ' + $t('Horas') + ' : ' +
                       self.minutes() + ' ' + $t('Minutos') + ' : ' +
                       self.seconds() + ' ' + $t('Segundos');
            });

            // Dispara o timer assíncrono para atualizar a cada segundo
            this.startTimer();

            return this;
        },

        /**
         * Atualiza o timestamp a cada 1000ms sem recarregar a página
         */
        startTimer: function () {
            var self = this;
            this.timer = setInterval(function () {
                self.now(Date.now());
            }, 1000);
        }
    });
});
