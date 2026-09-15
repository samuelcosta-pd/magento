<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\CatalogBehavior\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;

class StockStatus implements ArgumentInterface
{
    /**
     * Limite máximo (inclusive) para considerar estoque baixo.
     */
    public const LOW_STOCK_THRESHOLD = 3;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param StockByWebsiteIdResolverInterface|null $stockByWebsiteId
     * @param GetProductSalableQtyInterface|null $getProductSalableQty
     */
    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly ?StockByWebsiteIdResolverInterface $stockByWebsiteId = null,
        private readonly ?GetProductSalableQtyInterface $getProductSalableQty = null
    ) {
    }

    /**
     * Verifica se o produto possui estoque baixo (entre 1 e o limite definido).
     *
     * @param Product|null $product
     * @return bool
     */
    public function isLowStock(?Product $product): bool
    {
        if (!$product || !$product->isAvailable()) {
            return false;
        }

        $qty = $this->getStockQty($product);

        return $qty > 0 && $qty <= self::LOW_STOCK_THRESHOLD;
    }

    /**
     * Retorna o texto da mensagem de estoque baixo internacionalizada.
     *
     * @return Phrase
     */
    public function getLowStockMessage(): Phrase
    {
        return __('Últimas unidades!');
    }

    /**
     * Obtém a quantidade de estoque salável do produto (compatível com MSI e legado).
     *
     * @param Product $product
     * @return float
     */
    public function getStockQty(Product $product): float
    {
        // 1. Tenta obter via Multi-Source Inventory (MSI)
        if ($this->stockByWebsiteId !== null && $this->getProductSalableQty !== null) {
            try {
                $websiteId = (int) $product->getStore()->getWebsiteId();
                $stockId = (int) $this->stockByWebsiteId->execute($websiteId)->getStockId();
                return (float) $this->getProductSalableQty->execute($product->getSku(), $stockId);
            } catch (\Throwable $e) {
                // Fallback silencioso para CatalogInventory
            }
        }

        // 2. Fallback para StockRegistry padrão
        try {
            $stockItem = $this->stockRegistry->getStockItem((int) $product->getId());
            return (float) $stockItem->getQty();
        } catch (\Throwable $e) {
            return 0.0;
        }
    }
}
