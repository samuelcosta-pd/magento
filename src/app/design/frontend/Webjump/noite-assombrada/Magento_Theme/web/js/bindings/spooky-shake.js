/**
 * Copyright © Webjump. All rights reserved.
 * Custom Knockout Binding: spookyShake (Desafio 17.5 - Diferencial)
 *
 * Registra o binding handler customizado 'spookyShake' no Knockout.js.
 * Permite aplicar animações de tremor/shake temático a qualquer elemento do DOM
 * apenas declarando atributos no HTML, com suporte a parâmetros de intensidade e duração.
 */
define([
    'ko',
    'domReady!'
], function (ko) {
    'use strict';

    /**
     * Extrai e normaliza as opções passadas ao binding.
     *
     * @param {*} rawValue
     * @return {Object}
     */
    function parseOptions(rawValue) {
        var unwrapped = ko.unwrap(rawValue);

        if (!unwrapped) {
            return {
                intensity: 'medium',
                duration: 800,
                onHover: true
            };
        }

        if (typeof unwrapped === 'string') {
            return {
                intensity: unwrapped,
                duration: 800,
                onHover: true
            };
        }

        var intensity = ko.unwrap(unwrapped.intensity) || 'medium';
        var duration = ko.unwrap(unwrapped.duration);
        if (typeof duration === 'undefined' || duration === null) {
            duration = (intensity === 'high') ? 1200 : (intensity === 'low' ? 500 : 800);
        }

        var onHover = typeof unwrapped.onHover !== 'undefined' ? ko.unwrap(unwrapped.onHover) : true;
        var trigger = unwrapped.trigger;

        return {
            intensity: intensity,
            duration: duration,
            onHover: onHover,
            trigger: trigger
        };
    }

    /**
     * Aplica os estilos paramétricos de intensidade e duração no elemento.
     *
     * @param {HTMLElement} element
     * @param {Object} options
     */
    function applyStyles(element, options) {
        element.classList.add('spooky-shakeable');

        // Remove classes de intensidade anteriores
        element.classList.remove('spooky-shake-low', 'spooky-shake-medium', 'spooky-shake-high');
        element.classList.add('spooky-shake-' + options.intensity);

        // Aplica a duração dinâmica via estilo inline
        if (typeof options.duration === 'number') {
            element.style.animationDuration = options.duration + 'ms';
        } else if (typeof options.duration === 'string') {
            element.style.animationDuration = options.duration;
        }
    }

    if (!ko.bindingHandlers.spookyShake) {
        ko.bindingHandlers.spookyShake = {
            init: function (element, valueAccessor) {
                var options = parseOptions(valueAccessor());
                applyStyles(element, options);

                if (options.onHover) {
                    element.addEventListener('mouseenter', function () {
                        var currentOptions = parseOptions(valueAccessor());
                        applyStyles(element, currentOptions);
                        element.classList.add('spooky-shaking');
                    });

                    element.addEventListener('mouseleave', function () {
                        element.classList.remove('spooky-shaking');
                    });
                }

                // Cleanup ao remover elemento do DOM
                ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                    element.classList.remove('spooky-shaking', 'spooky-shakeable');
                });
            },

            update: function (element, valueAccessor) {
                var options = parseOptions(valueAccessor());
                applyStyles(element, options);

                if (options.trigger) {
                    var triggerVal = ko.unwrap(options.trigger);
                    if (triggerVal) {
                        element.classList.add('spooky-shaking');
                        var timeoutMs = typeof options.duration === 'number' ? options.duration : 800;
                        setTimeout(function () {
                            element.classList.remove('spooky-shaking');
                        }, timeoutMs);
                    }
                }
            }
        };
    }

    // Auto-aplicação de bindings para elementos estáticos em PHTML com data-bind="spookyShake"
    try {
        var staticShakeables = document.querySelectorAll('[data-bind*="spookyShake"]');
        for (var i = 0; i < staticShakeables.length; i++) {
            var el = staticShakeables[i];
            if (!ko.dataFor(el)) {
                ko.applyBindings({}, el);
            }
        }
    } catch (e) {
        // Ignora silenciosamente se o elemento já estiver em contexto Knockout
    }

    return ko.bindingHandlers.spookyShake;
});
