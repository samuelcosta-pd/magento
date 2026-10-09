/**
 * Copyright © Webjump. All rights reserved.
 * Haunted Mode (Modo Assombrado - Ultra Dark) controller.
 * Loaded on every page via requirejs-config.js deps section.
 * Challenge 17.2
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var STORAGE_KEY = 'haunted_mode_enabled';
    var ROOT_CLASS = 'haunted-mode';
    var $html = $('html');

    /**
     * Reads saved user preference from localStorage.
     *
     * @return {Boolean}
     */
    function getStoredPreference() {
        try {
            return localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {
            return false;
        }
    }

    /**
     * Persists user preference in localStorage.
     *
     * @param {Boolean} isEnabled
     */
    function savePreference(isEnabled) {
        try {
            localStorage.setItem(STORAGE_KEY, isEnabled ? 'true' : 'false');
        } catch (e) {
            // Silently handle private browsing storage restrictions
        }
    }

    /**
     * Applies or removes the haunted mode root class and updates switch state.
     *
     * @param {Boolean} isEnabled
     */
    function applyHauntedMode(isEnabled) {
        if (isEnabled) {
            $html.addClass(ROOT_CLASS);
        } else {
            $html.removeClass(ROOT_CLASS);
        }

        var $switches = $('[data-action="toggle-haunted-mode"]');
        if ($switches.length) {
            $switches.attr('aria-checked', isEnabled ? 'true' : 'false');
            $switches.toggleClass('active', isEnabled);
        }
    }

    /**
     * Initializes the haunted mode on document ready.
     */
    function init() {
        var isEnabled = getStoredPreference();
        applyHauntedMode(isEnabled);

        $(document).on('click', '[data-action="toggle-haunted-mode"]', function (event) {
            event.preventDefault();
            var currentlyActive = $html.hasClass(ROOT_CLASS);
            var nextState = !currentlyActive;

            savePreference(nextState);
            applyHauntedMode(nextState);
        });
    }

    init();

    return {
        isEnabled: function () {
            return $html.hasClass(ROOT_CLASS);
        },
        toggle: function () {
            var nextState = !$html.hasClass(ROOT_CLASS);
            savePreference(nextState);
            applyHauntedMode(nextState);
            return nextState;
        }
    };
});
