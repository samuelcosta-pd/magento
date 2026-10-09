<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\CheckoutComment\Block\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;

class Comment extends AbstractOrder
{
    /**
     * Get spooky order comment from current order
     *
     * @return string|null
     */
    public function getSpookyOrderComment(): ?string
    {
        try {
            $order = $this->getOrder();
            $comment = $order ? $order->getData('spooky_order_comment') : null;
            return $comment !== null && $comment !== '' ? (string)$comment : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
