/**
 * Copyright © Webjump. All rights reserved.
 * Custom Knockout Binding: spookyShake (Desafio 17.5 - Diferencial)
 *
 * Registra um binding declarativo personalizado no Knockout.js que adiciona
 * efeitos temáticos de tremor/vibração paranormal a qualquer elemento HTML da loja.
 * Suporta parâmetros de intensidade, duração, disparo reativo por observable e hover.
 */
define([
    'ko'
], function (ko) {
    'use strict';

    if (!ko.bindingHandlers.spookyShake) {
        ko.bindingHandlers.spookyShake = {
            /**
             * Inicializa o binding no nó DOM
             *
             * @param {HTMLElement} element
             * @param {Function} valueAccessor
             */
            init: function (element, valueAccessor) {
                var rawValue = valueAccessor();
                var options = (typeof rawValue === 'object' && rawValue !== null)
                    ? ko.unwrap(rawValue)
                    : { intensity: 'medium' };

                var intensity = ko.unwrap(options.intensity) || 'medium';
                var duration = ko.unwrap(options.duration) || '700ms';
                var durationStr = typeof duration === 'number' ? duration + 'ms' : duration;

                element.classList.add('spooky-shakeable');
                element.classList.add('spooky-shake-' + intensity);
                element.style.setProperty('--spooky-shake-duration', durationStr);
                element.style.animationDuration = durationStr;

                if (options.continuous) {
                    element.classList.add('spooky-shaking');
                }

                if (options.onHover) {
                    var onMouseEnter = function () {
                        element.classList.add('spooky-shaking');
                    };
                    var onMouseLeave = function () {
                        if (!options.continuous) {
                            element.classList.remove('spooky-shaking');
                        }
                    };

                    element.addEventListener('mouseenter', onMouseEnter);
                    element.addEventListener('mouseleave', onMouseLeave);

                    ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                        element.removeEventListener('mouseenter', onMouseEnter);
                        element.removeEventListener('mouseleave', onMouseLeave);
                    });
                }
            },

            /**
             * Atualiza o binding quando propriedades reativas sofrem mutação
             *
             * @param {HTMLElement} element
             * @param {Function} valueAccessor
             */
            update: function (element, valueAccessor) {
                var rawValue = valueAccessor();
                var options = (typeof rawValue === 'object' && rawValue !== null)
                    ? ko.unwrap(rawValue)
                    : { intensity: 'medium' };

                var intensity = ko.unwrap(options.intensity) || 'medium';
                var duration = ko.unwrap(options.duration) || '700ms';
                var durationStr = typeof duration === 'number' ? duration + 'ms' : duration;
                var durationMs = typeof duration === 'number'
                    ? duration
                    : (parseFloat(duration) * (duration.indexOf('s') > -1 && duration.indexOf('ms') === -1 ? 1000 : 1)) || 700;

                // Atualizar classes de intensidade se alteradas
                ['low', 'medium', 'high'].forEach(function (level) {
                    element.classList.remove('spooky-shake-' + level);
                });
                element.classList.add('spooky-shake-' + intensity);
                element.style.setProperty('--spooky-shake-duration', durationStr);
                element.style.animationDuration = durationStr;

                // Trigger reativo via observable
                if (options.trigger !== undefined) {
                    var triggerVal = ko.unwrap(options.trigger);
                    if (triggerVal) {
                        element.classList.add('spooky-shaking');
                        setTimeout(function () {
                            if (!options.continuous) {
                                element.classList.remove('spooky-shaking');
                            }
                        }, durationMs);
                    }
                }
            }
        };
    }

    return ko.bindingHandlers.spookyShake;
});
