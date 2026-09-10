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
    private const CONFIG_BLOCK_TEXT     = 'webjump_samuel/general/block_text';
    private const CONFIG_BLOCK_SUBTITLE = 'webjump_samuel/general/block_subtitle';
    private const CONFIG_PRODUCT_LIMIT  = 'webjump_samuel/general/product_limit';
    private const CONFIG_FEATURED_SKUS  = 'webjump_samuel/general/featured_skus';

    private const DEFAULT_TITLE    = 'Últimas Unidades em Estoque';
    private const DEFAULT_SUBTITLE = 'Produtos esgotando. Aproveite as ofertas antes que zerem os estoques!';
    private const DEFAULT_LIMIT    = 4;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ImageHelper $imageHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param ScopeConfigInterface $scopeConfig
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
     * Produtos com SKU em destaque aparecem primeiro; o restante mantém a ordem por qty ASC.
     *
     * @param int $threshold Quantidade máxima de estoque considerada "baixa"
     * @param int|null $limit Quantidade máxima de produtos a retornar (null = usa config do Admin)
     * @return array<int, array{id: int, name: string, sku: string, url: string, price: string, qty: int, image_url: string, badge_text: string}>
     */
    public function getLowStockProducts(int $threshold = 5, ?int $limit = null): array
    {
        $limit = $limit ?? $this->getProductLimit();

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

        return $this->prioritizeFeaturedSkus($items);
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
     * Título da seção (configurável pelo Admin).
     *
     * @return string
     */
    public function getSectionTitle(): string
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_BLOCK_TEXT);

        return trim((string) $value) ?: self::DEFAULT_TITLE;
    }

    /**
     * Subtítulo da seção (configurável pelo Admin).
     *
     * @return string
     */
    public function getSectionSubtitle(): string
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_BLOCK_SUBTITLE);

        return trim((string) $value) ?: self::DEFAULT_SUBTITLE;
    }

    /**
     * Quantidade de produtos configurada no Admin.
     * Retorna o valor do select (2, 4, 6, 8 ou 10).
     * Usa DEFAULT_LIMIT como fallback caso o valor seja inválido ou ausente.
     *
     * @return int
     */
    public function getProductLimit(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::CONFIG_PRODUCT_LIMIT);

        return $value > 0 ? $value : self::DEFAULT_LIMIT;
    }

    /**
     * Retorna o array de SKUs configurados como destaque no Admin.
     * SKUs vazios ou apenas com espaços são descartados.
     *
     * @return string[]
     */
    public function getFeaturedSkus(): array
    {
        $raw = $this->scopeConfig->getValue(self::CONFIG_FEATURED_SKUS);
        if (empty($raw)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', (string) $raw))
        ));
    }

    /**
     * Reordena a lista de produtos colocando os SKUs em destaque primeiro.
     * Preserva a ordem interna de cada grupo (destaque e restante).
     *
     * @param array<int, array{sku: string}> $items
     * @return array<int, array{sku: string}>
     */
    private function prioritizeFeaturedSkus(array $items): array
    {
        $featuredSkus = $this->getFeaturedSkus();
        if (empty($featuredSkus)) {
            return $items;
        }

        $featured = [];
        $rest     = [];

        foreach ($items as $item) {
            if (in_array($item['sku'], $featuredSkus, true)) {
                $featured[] = $item;
            } else {
                $rest[] = $item;
            }
        }

        // Reordena os destacados conforme a ordem definida no Admin
        usort($featured, static function (array $a, array $b) use ($featuredSkus): int {
            return array_search($a['sku'], $featuredSkus, true) <=> array_search($b['sku'], $featuredSkus, true);
        });

        return array_merge($featured, $rest);
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