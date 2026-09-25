<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Exibe o grid de avaliações de produtos.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webjump_ProductReview::reviews');
        $resultPage->getConfig()->getTitle()->prepend(__('Avaliações de Produtos'));

        return $resultPage;
    }

    /**
     * Valida chaves de URL e intercepta tentativas de acesso não autorizadas exibindo mensagem amigável.
     *
     * @return bool
     */
    public function _processUrlKeys()
    {
        if (!$this->_isAllowed()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);
            $this->_redirect('admin/dashboard/index');
            return false;
        }

        return parent::_processUrlKeys();
    }

    /**
     * Verifica permissão de acesso e adiciona mensagem explicativa se negado.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        $isAllowed = parent::_isAllowed();
        if (!$isAllowed) {
            $this->messageManager->addErrorMessage(
                __('Você não possui permissão para acessar as Avaliações de Produtos.')
            );
        }

        return $isAllowed;
    }
}
