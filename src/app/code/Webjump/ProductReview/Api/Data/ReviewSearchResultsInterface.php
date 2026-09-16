<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface ReviewSearchResultsInterface
 * Search results interface for Product Review service contract.
 *
 * @api
 */
interface ReviewSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get reviews list.
     *
     * @return \Webjump\ProductReview\Api\Data\ReviewInterface[]
     */
    public function getItems();

    /**
     * Set reviews list.
     *
     * @param \Webjump\ProductReview\Api\Data\ReviewInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
