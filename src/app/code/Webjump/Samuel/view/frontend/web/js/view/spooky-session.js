/**
 * Copyright © Webjump. All rights reserved.
 * Knockout UI Component for Spooky Session Scare Counter (Desafio 17.6)
 */
define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko',
    'jquery',
    'mage/url',
    'mage/cookies'
], function (Component, customerData, ko, $, urlBuilder) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Webjump_Samuel/spooky-session',
            triggerUrl: urlBuilder.build('spooky/scare/trigger')
        },

        initialize: function () {
            this._super();
            this.spookySession = customerData.get('spooky-session');
            this.isScaring = ko.observable(false);
            this.justScared = ko.observable(false);

            // Ensure section is initially populated if needed
            if (!this.spookySession() || typeof this.spookySession().scares_count === 'undefined') {
                customerData.reload(['spooky-session'], false);
            }
        },

        /**
         * Return observable scare count
         *
         * @return {Number}
         */
        scaresCount: function () {
            var data = this.spookySession();
            return (data && typeof data.scares_count !== 'undefined') ? data.scares_count : 0;
        },

        /**
         * Return user thematic level
         *
         * @return {String}
         */
        scareLevel: function () {
            var data = this.spookySession();
            return (data && data.scare_level) ? data.scare_level : 'Inocente Desavisado';
        },

        /**
         * Return halloween greeting
         *
         * @return {String}
         */
        halloweenGreeting: function () {
            var data = this.spookySession();
            return (data && data.halloween_greeting) ? data.halloween_greeting : '';
        },

        /**
         * Trigger scare action via AJAX POST
         * Validates section invalidation in sections.xml and updates UI reactively
         */
        triggerScare: function () {
            var self = this;

            if (this.isScaring()) {
                return;
            }

            this.isScaring(true);

            $.ajax({
                url: this.triggerUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: $.mage.cookies.get('form_key')
                }
            }).done(function () {
                self.justScared(true);
                // In case sections.xml invalidation processor is delayed, explicitly refresh customerData
                customerData.reload(['spooky-session'], true);
                setTimeout(function () {
                    self.justScared(false);
                }, 1500);
            }).fail(function () {
                // If CSRF or network error, fallback reload
                customerData.reload(['spooky-session'], true);
            }).always(function () {
                self.isScaring(false);
            });
        }
    });
});
