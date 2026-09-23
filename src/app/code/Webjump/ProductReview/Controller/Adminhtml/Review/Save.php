<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\ProductReview\Api\Data\ReviewInterfaceFactory;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews';

    /**
     * @param Context $context
     * @param ReviewRepositoryInterface $reviewRepository
     * @param ReviewInterfaceFactory $reviewFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly ReviewInterfaceFactory $reviewFactory,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * Salva a avaliação utilizando estritamente o repositório.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = !empty($data['review_id']) ? (int) $data['review_id'] : null;

        try {
            $this->validateData($data);

            if ($id) {
                try {
                    $review = $this->reviewRepository->getById($id);
                } catch (NoSuchEntityException $e) {
                    throw new LocalizedException(__('Esta avaliação não existe mais.'));
                }
            } else {
                $review = $this->reviewFactory->create();
            }

            $review->setProductId((int) $data['product_id']);
            $review->setAuthorName(trim((string) $data['author_name']));
            $review->setRating((int) $data['rating']);
            $review->setIsApproved((bool) ($data['is_approved'] ?? 0));
            $review->setComment(trim((string) $data['comment']));

            $this->reviewRepository->save($review);

            $this->messageManager->addSuccessMessage(__('A avaliação foi salva com sucesso.'));
            $this->dataPersistor->clear('webjump_productreview_review');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['review_id' => $review->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Ocorreu um erro ao tentar salvar a avaliação: %1', $e->getMessage())
            );
        }

        $this->dataPersistor->set('webjump_productreview_review', $data);

        if ($id) {
            return $resultRedirect->setPath('*/*/edit', ['review_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/new');
    }

    /**
     * Valida os campos obrigatórios e formatos de entrada.
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function validateData(array $data): void
    {
        if (empty($data['product_id']) || !is_numeric($data['product_id']) || (int) $data['product_id'] <= 0) {
            throw new LocalizedException(__('O campo "ID do Produto" deve ser um número inteiro maior que zero.'));
        }

        if (empty(trim((string) ($data['author_name'] ?? '')))) {
            throw new LocalizedException(__('O campo "Nome do Autor" é obrigatório.'));
        }

        if (empty(trim((string) ($data['comment'] ?? '')))) {
            throw new LocalizedException(__('O campo "Comentário" é obrigatório.'));
        }

        if (!isset($data['rating']) || !is_numeric($data['rating']) ||
            (int) $data['rating'] < 1 || (int) $data['rating'] > 5
        ) {
            throw new LocalizedException(__('O campo "Nota" deve ser um valor entre 1 e 5.'));
        }
    }
}
