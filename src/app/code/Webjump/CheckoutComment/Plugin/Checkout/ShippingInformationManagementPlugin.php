<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\CheckoutComment\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class ShippingInformationManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Save spooky_order_comment from extension attributes into quote
     *
     * @param ShippingInformationManagement $subject
     * @param int|string $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return array
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): array {
        $extAttributes = $addressInformation->getExtensionAttributes();
        if ($extAttributes) {
            $comment = $extAttributes->getSpookyOrderComment();
            if ($comment !== null) {
                $comment = mb_substr(trim((string)$comment), 0, 250);
                try {
                    $quote = $this->quoteRepository->getActive($cartId);
                    $quote->setData('spooky_order_comment', $comment);
                } catch (\Exception $e) {
                    $this->logger->error('Error saving spooky_order_comment to quote: ' . $e->getMessage());
                }
            }
        }

        return [$cartId, $addressInformation];
    }
}
