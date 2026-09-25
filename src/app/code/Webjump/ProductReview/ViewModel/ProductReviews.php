<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;
use Webjump\ProductReview\Model\Config;

class ProductReviews implements ArgumentInterface
{
    /**
     * @param Config $config
     * @param ReviewRepositoryInterface $reviewRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        private readonly Config $config,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * Verifica se o módulo de avaliações está ativo na loja.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Retorna a nota mínima configurada.
     *
     * @return int
     */
    public function getMinRating(): int
    {
        return $this->config->getMinRating();
    }

    /**
     * Retorna as avaliações aprovadas do produto que atendem aos critérios de configuração.
     *
     * @param Product|null $product
     * @return ReviewInterface[]
     */
    public function getReviews(?Product $product): array
    {
        if (!$this->isEnabled() || !$product || !$product->getId()) {
            return [];
        }

        $sortOrder = $this->sortOrderBuilder
            ->setField(ReviewInterface::CREATED_AT)
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ReviewInterface::PRODUCT_ID, (int) $product->getId(), 'eq')
            ->addFilter(ReviewInterface::IS_APPROVED, 1, 'eq')
            ->addFilter(ReviewInterface::RATING, $this->getMinRating(), 'gteq')
            ->addSortOrder($sortOrder)
            ->create();

        return (array) $this->reviewRepository->getList($searchCriteria)->getItems();
    }
}
