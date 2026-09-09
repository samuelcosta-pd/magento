<?php
/**
 * Copyright © Webjump. All rights reserved.
 *
 * Plugin after sobre Magento\Catalog\Block\Product\View\Type\Simple::toHtml().
 *
 * Substitui diretamente o texto "In stock" por "Últimas unidades" quando o produto
 * tiver estoque baixo (1 a 3 unidades disponíveis), mantendo o comportamento
 * padrão para produtos com estoque normal ou esgotados.
 *
 * O plugin não altera:
 * - Quantidade real de estoque
 * - Salabilidade do produto
 * - Carrinho ou checkout
 * - Nenhum arquivo do vendor/
 */

declare(strict_types=1);

namespace Webjump\CatalogBehavior\Plugin\Block\Product\View\Type;

use Magento\Catalog\Block\Product\View\Type\Simple;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Psr\Log\LoggerInterface;

class SimpleProductViewPlugin
{
    /**
     * Limite máximo (inclusive) para considerar estoque baixo.
     */
    private const LOW_STOCK_THRESHOLD = 3;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param StockByWebsiteIdResolverInterface|null $stockByWebsiteId
     * @param GetProductSalableQtyInterface|null $getProductSalableQty
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly StockRegistryInterface $stockRegistry,
        private readonly ?StockByWebsiteIdResolverInterface $stockByWebsiteId = null,
        private readonly ?GetProductSalableQtyInterface $getProductSalableQty = null,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Plugin after em toHtml().
     *
     * @param Simple $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(Simple $subject, string $result): string
    {
        $product = $subject->getProduct();
        if (!$product || !$product->isAvailable()) {
            return $result;
        }

        $stockQty = $this->getStockQty($product);

        if ($stockQty > 0 && $stockQty <= self::LOW_STOCK_THRESHOLD) {
            $lowStockText = __('Últimas unidades!');
            $search = '<span>' . __('In stock') . '</span>';
            $replace = '<span class="webjump-low-stock" style="color: #e02b27; font-weight: 700;">&#9888; ' . $lowStockText . '</span>';

            $result = str_replace('<div class="stock available"', '<div class="stock available low-stock"', $result);
            return str_replace($search, $replace, $result);
        }

        return $result;
    }

    /**
     * Obtém a quantidade de estoque salável do produto (compatível com MSI e legado).
     *
     * @param Product $product
     * @return float
     */
    private function getStockQty(Product $product): float
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
