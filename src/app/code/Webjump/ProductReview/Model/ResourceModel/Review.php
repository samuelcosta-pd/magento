<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Review
 * Product review entity database resource model.
 */
class Review extends AbstractDb
{
    /**
     * Main table and primary key initialization.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('webjump_product_review', 'review_id');
    }
}
