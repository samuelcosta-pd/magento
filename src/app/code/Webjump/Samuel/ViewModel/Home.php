<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\Samuel\ViewModel;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Home implements ArgumentInterface
{
    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ImageHelper $imageHelper
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ImageHelper $imageHelper,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Retorna a lista de produtos com estoque baixo formatados para exibição.
     *
     * @param int $threshold Quantidade máxima de estoque considerada "baixa"
     * @param int $limit Quantidade máxima de produtos a retornar
     * @return array<int, array{id: int, name: string, sku: string, url: string, price: string, qty: int, image_url: string, badge_text: string}>
     */
    public function getLowStockProducts(int $threshold = 5, int $limit = 4): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'small_image', 'thumbnail', 'status', 'visibility']);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['in' => [
            Visibility::VISIBILITY_BOTH,
            Visibility::VISIBILITY_IN_CATALOG
        ]]);

        $collection->joinField(
            'qty',
            'cataloginventory_stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'inner'
        );
        $collection->joinField(
            'is_in_stock',
            'cataloginventory_stock_item',
            'is_in_stock',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'inner'
        );

        $collection->addFieldToFilter('is_in_stock', 1);
        $collection->addFieldToFilter('qty', ['gt' => 0]);
        $collection->addFieldToFilter('qty', ['lteq' => $threshold]);
        $collection->setOrder('qty', 'ASC');
        $collection->setPageSize($limit);

        $items = [];
        foreach ($collection as $product) {
            $qty = (int) $product->getQty();
            $items[] = [
                'id'         => (int) $product->getId(),
                'name'       => (string) $product->getName(),
                'sku'        => (string) $product->getSku(),
                'url'        => (string) $product->getProductUrl(),
                'price'      => $this->priceCurrency->format((float) $product->getFinalPrice(), false),
                'qty'        => $qty,
                'image_url'  => $this->imageHelper->init($product, 'category_page_grid')->getUrl(),
                'badge_text' => $qty === 1
                    ? 'Apenas 1 unidade restante!'
                    : sprintf('Restam %d unidades!', $qty)
            ];
        }

        return $items;
    }

    /**
     * Verifica se existem produtos com estoque baixo.
     *
     * @param int $threshold
     * @return bool
     */
    public function hasLowStockProducts(int $threshold = 5): bool
    {
        return !empty($this->getLowStockProducts($threshold, 1));
    }

    /**
     * Título da seção.
     *
     * @return string
     */
    public function getSectionTitle(): string
    {
        $value = $this->scopeConfig->getValue(
            'webjump_samuel/general/block_text'
        );

        return trim((string) $value) ?: 'Últimas Unidades em Estoque';
    }

    /**
     * Subtítulo explicativo com call-to-action de urgência.
     *
     * @return string
     */
    public function getSectionSubtitle(): string
    {
        return 'Produtos esgotando. Aproveite as ofertas antes que zerem os estoques!';
    }

        /**
     * Retorna o texto configurado no admin.
     *
     * @return string
     */
    public function getConfiguredText(): string
    {
        $value = $this->scopeConfig->getValue(
            'webjump_samuel/general/block_text'
        );

        return trim((string) $value);
    }

    /**
     * Mensagem legada para retrocompatibilidade.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return 'Olá! Este bloco foi criado pelo módulo Webjump_Samuel.';
    }
}