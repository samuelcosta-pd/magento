/**
 * Copyright © Webjump. All rights reserved.
 * Custom Knockout Binding: spookyShake (Diferencial 17.5)
 * Adiciona animação de tremor/shake temático parametrizado para elementos da loja.
 */
define([
    'ko'
], function (ko) {
    'use strict';

    if (!ko.bindingHandlers.spookyShake) {
        ko.bindingHandlers.spookyShake = {
            init: function (element, valueAccessor) {
                var options = ko.unwrap(valueAccessor()) || {};
                var intensity = options.intensity || 'medium';

                element.classList.add('spooky-shakeable');
                element.classList.add('spooky-shake-' + intensity);

                if (options.onHover) {
                    element.addEventListener('mouseenter', function () {
                        element.classList.add('spooky-shaking');
                    });
                    element.addEventListener('mouseleave', function () {
                        element.classList.remove('spooky-shaking');
                    });
                }
            },
            update: function (element, valueAccessor) {
                var options = ko.unwrap(valueAccessor()) || {};
                var trigger = ko.unwrap(options.trigger);

                if (trigger) {
                    element.classList.add('spooky-shaking');
                    setTimeout(function () {
                        element.classList.remove('spooky-shaking');
                    }, 800);
                }
            }
        };
    }

    return ko.bindingHandlers.spookyShake;
});
