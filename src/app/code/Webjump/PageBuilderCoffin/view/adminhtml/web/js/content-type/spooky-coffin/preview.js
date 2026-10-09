define([
    'jquery',
    'knockout',
    'mage/translate',
    'Magento_PageBuilder/js/content-type-menu/hide-show-option',
    'Magento_PageBuilder/js/content-type/preview'
], function ($, ko, $t, HideShowOption, PreviewBase) {
    'use strict';

    /**
     * Preview component for Caixao de Ofertas content type
     *
     * @param {Object} contentType
     * @param {Object} config
     * @param {Object} pan
     * @param {String} stageId
     */
    function Preview(contentType, config, pan, stageId) {
        PreviewBase.apply(this, arguments);
    }

    Preview.prototype = Object.create(PreviewBase.prototype);
    Preview.prototype.constructor = Preview;

    /**
     * Retrieve options menu for the content type on the stage
     *
     * @returns {Object}
     */
    Preview.prototype.retrieveOptions = function () {
        var options = PreviewBase.prototype.retrieveOptions.call(this);

        options.hideShow = new HideShowOption({
            preview: this,
            icon: HideShowOption.showIcon,
            title: HideShowOption.showText,
            action: this.onOptionVisibilityToggle,
            classes: ['hide-show-content-type'],
            sort: 40
        });

        return options;
    };

    return Preview;
});
