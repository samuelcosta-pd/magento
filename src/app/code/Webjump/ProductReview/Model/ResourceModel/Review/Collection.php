<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\ResourceModel\Review;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Webjump\ProductReview\Model\ResourceModel\Review as ReviewResourceModel;
use Webjump\ProductReview\Model\Review as ReviewModel;

/**
 * Class Collection
 * Product review entity collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'review_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'webjump_product_review_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'review_collection';

    /**
     * Define model and resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ReviewModel::class, ReviewResourceModel::class);
    }
}
