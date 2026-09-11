<?php
/**
 * Copyright © Webjump. All rights reserved.
 *
 * Observer para o evento catalog_product_save_after.
 *
 * Registra no log sempre que um produto for salvo via admin ou programaticamente.
 * Demonstra o uso de observer como mecanismo de reação a eventos da plataforma
 * sem interceptar diretamente o método de salvamento.
 */

declare(strict_types=1);

namespace Webjump\CatalogBehavior\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ProductSaveAfter implements ObserverInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Registra uma mensagem no log quando um produto é salvo.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        $this->logger->info(
            sprintf(
                '[Webjump_CatalogBehavior] Produto salvo — ID: %s | SKU: %s | Nome: %s | Status: %s',
                $product->getId(),
                $product->getSku(),
                $product->getName(),
                $product->getStatus() == 1 ? 'Habilitado' : 'Desabilitado'
            )
        );
    }
}
