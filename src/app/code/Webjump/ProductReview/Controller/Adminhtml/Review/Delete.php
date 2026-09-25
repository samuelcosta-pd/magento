<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;

class Delete extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews';

    /**
     * @param Context $context
     * @param ReviewRepositoryInterface $reviewRepository
     */
    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Exclui uma avaliação utilizando estritamente o repositório.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('review_id');

        if ($id) {
            try {
                $this->reviewRepository->deleteById((int) $id);
                $this->messageManager->addSuccessMessage(__('A avaliação foi excluída com sucesso.'));
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['review_id' => $id]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Ocorreu um erro ao excluir a avaliação: %1', $e->getMessage())
                );
                return $resultRedirect->setPath('*/*/edit', ['review_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('Não foi possível identificar a avaliação para exclusão.'));
        return $resultRedirect->setPath('*/*/');
    }
}
