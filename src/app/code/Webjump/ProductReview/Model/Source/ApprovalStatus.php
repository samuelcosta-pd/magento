<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApprovalStatus implements OptionSourceInterface
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::STATUS_PENDING,
                'label' => __('Pendente')
            ],
            [
                'value' => self::STATUS_APPROVED,
                'label' => __('Aprovado')
            ]
        ];
    }
}
