<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ReviewRepositoryInterface $reviewRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Exibe a página de edição ou criação de avaliação.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $id = $this->getRequest()->getParam('review_id');
        $authorName = '';

        if ($id) {
            try {
                $review = $this->reviewRepository->getById((int) $id);
                $authorName = (string) $review->getAuthorName();
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('Esta avaliação não existe mais.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webjump_ProductReview::reviews');
        $resultPage->getConfig()->getTitle()->prepend(__('Avaliações de Produtos'));
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Editar Avaliação de "%1"', $authorName) : __('Nova Avaliação')
        );

        return $resultPage;
    }
}
