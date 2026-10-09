<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\CheckoutComment\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

class LayoutProcessorPlugin
{
    /**
     * Add spooky_order_comment field to shipping step in checkout
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        $stepPath = ['components', 'checkout', 'children', 'steps', 'children',
            'shipping-step', 'children', 'shippingAddress', 'children'];

        $shippingChildren = &$jsLayout;
        foreach ($stepPath as $segment) {
            if (!isset($shippingChildren[$segment])) {
                return $jsLayout;
            }
            $shippingChildren = &$shippingChildren[$segment];
        }

        // Register in shippingAdditional displayArea (rendered directly below shipping methods in shipping step)
        $shippingChildren['spooky-order-comment-fieldset'] = [
            'component' => 'uiComponent',
            'displayArea' => 'shippingAdditional',
            'children' => [
                'spooky_order_comment' => [
                    'component' => 'Magento_Ui/js/form/element/textarea',
                    'config' => [
                        'customScope' => 'shippingAddress.custom_attributes',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/textarea',
                        'id' => 'spooky-order-comment',
                        'cols' => 15,
                        'rows' => 3
                    ],
                    'dataScope' => 'shippingAddress.custom_attributes.spooky_order_comment',
                    'label' => __('Mensagem Assombrada'),
                    'provider' => 'checkoutProvider',
                    'visible' => true,
                    'validation' => [
                        'max_text_length' => 250
                    ],
                    'notice' => __(
                        'Deixe uma mensagem assombrosa ou feitiço para acompanhar o seu pedido (máximo 250 caracteres).'
                    ),
                    'sortOrder' => 250,
                    'id' => 'spooky-order-comment'
                ]
            ]
        ];

        return $jsLayout;
    }
}
