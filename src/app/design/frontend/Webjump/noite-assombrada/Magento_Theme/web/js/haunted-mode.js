/**
 * Copyright © Webjump. All rights reserved.
 * Desafio 17.2: Modo Assombrado (Tema Noite Assombrada)
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var STORAGE_KEY = 'noite_assombrada_mode',
        CLASS_NAME = 'haunted-mode',
        rootElement = document.documentElement;

    /**
     * Recupera o estado salvo no localStorage
     *
     * @returns {Boolean}
     */
    function getSavedMode() {
        try {
            return localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {
            return false;
        }
    }

    /**
     * Persiste o estado no localStorage
     *
     * @param {Boolean} isActive
     */
    function saveMode(isActive) {
        try {
            localStorage.setItem(STORAGE_KEY, isActive ? 'true' : 'false');
        } catch (e) {
            // Silencioso em caso de restrição do navegador
        }
    }

    /**
     * Atualiza o estado visual do botão interruptor
     *
     * @param {Boolean} isActive
     */
    function updateToggleButton(isActive) {
        var $btn = $('#haunted-mode-toggle, [data-role="haunted-mode-toggle"]');

        if ($btn.length) {
            $btn.attr('aria-pressed', isActive ? 'true' : 'false');
            $btn.toggleClass('is-active', isActive);
        }
    }

    /**
     * Aplica ou remove a classe do elemento raiz e do body
     *
     * @param {Boolean} isActive
     */
    function applyHauntedMode(isActive) {
        if (isActive) {
            rootElement.classList.add(CLASS_NAME);
            if (document.body) {
                document.body.classList.add(CLASS_NAME);
            }
        } else {
            rootElement.classList.remove(CLASS_NAME);
            if (document.body) {
                document.body.classList.remove(CLASS_NAME);
            }
        }

        updateToggleButton(isActive);
    }

    /**
     * Alterna o modo assombrado dinamicamente sem recarregar a página
     */
    function toggleHauntedMode() {
        var current = rootElement.classList.contains(CLASS_NAME),
            next = !current;

        applyHauntedMode(next);
        saveMode(next);
    }

    // Aplicação imediata para evitar FOUC (Flash of Unstyled Content)
    var initialMode = getSavedMode();
    applyHauntedMode(initialMode);

    // Inicialização e listeners no DOM ready
    $(function () {
        // Garante aplicação após o body estar completamente montado
        applyHauntedMode(getSavedMode());

        // Event delegation para capturar clique no botão interruptor
        $(document).on('click', '#haunted-mode-toggle, [data-role="haunted-mode-toggle"]', function (e) {
            e.preventDefault();
            toggleHauntedMode();
        });
    });

    return {
        toggle: toggleHauntedMode,
        isHaunted: function () {
            return rootElement.classList.contains(CLASS_NAME);
        }
    };
});
