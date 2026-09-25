<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Controller\Adminhtml\Review;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;
use Webjump\ProductReview\Model\ResourceModel\Review\CollectionFactory;

class MassDelete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReviewRepositoryInterface $reviewRepository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Exclui as avaliações selecionadas em massa.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deletedCount = 0;

            /** @var ReviewInterface $review */
            foreach ($collection as $review) {
                $this->reviewRepository->delete($review);
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('Um total de %1 avaliação(ões) foi excluída(s) com sucesso.', $deletedCount)
                );
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Erro ao excluir as avaliações selecionadas: %1', $e->getMessage())
            );
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
