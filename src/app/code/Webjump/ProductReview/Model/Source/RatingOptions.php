<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RatingOptions implements OptionSourceInterface
{
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 1,
                'label' => __('1 Estrela')
            ],
            [
                'value' => 2,
                'label' => __('2 Estrelas')
            ],
            [
                'value' => 3,
                'label' => __('3 Estrelas')
            ],
            [
                'value' => 4,
                'label' => __('4 Estrelas')
            ],
            [
                'value' => 5,
                'label' => __('5 Estrelas')
            ]
        ];
    }
}
