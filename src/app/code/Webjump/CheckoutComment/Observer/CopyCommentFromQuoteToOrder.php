<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\CheckoutComment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class CopyCommentFromQuoteToOrder implements ObserverInterface
{
    /**
     * Copy spooky order comment from quote to order on quote submission
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order|null $order */
        $order = $observer->getEvent()->getData('order');
        /** @var Quote|null $quote */
        $quote = $observer->getEvent()->getData('quote');

        if ($order && $quote) {
            $comment = $quote->getData('spooky_order_comment');
            if ($comment !== null && $comment !== '') {
                $order->setData('spooky_order_comment', $comment);
            }
        }
    }
}
