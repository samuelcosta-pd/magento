<?php
/**
 * Copyright © Webjump. All rights reserved.
 * AJAX Controller to trigger a scare action and invalidate spooky-session (Desafio 17.6)
 */

declare(strict_types=1);

namespace Webjump\Samuel\Controller\Scare;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Trigger implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly CustomerSession $customerSession
    ) {
    }

    /**
     * Execute scare trigger action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $currentCount = (int) $this->customerSession->getData('spooky_scares_count');
        $newCount = $currentCount + 1;
        $this->customerSession->setData('spooky_scares_count', $newCount);

        $result = $this->resultJsonFactory->create();
        return $result->setData([
            'success' => true,
            'scares_count' => $newCount,
            'message' => __('Você tomou um susto arrepiante na sessão!')
        ]);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
