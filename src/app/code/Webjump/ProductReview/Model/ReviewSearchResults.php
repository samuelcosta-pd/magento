<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model;

use Magento\Framework\Api\SearchResults;
use Webjump\ProductReview\Api\Data\ReviewSearchResultsInterface;

/**
 * Class ReviewSearchResults
 * Service Data Object representing review search results.
 */
class ReviewSearchResults extends SearchResults implements ReviewSearchResultsInterface
{
}
