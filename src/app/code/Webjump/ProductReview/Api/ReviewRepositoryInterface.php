<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Api\Data\ReviewSearchResultsInterface;

/**
 * Interface ReviewRepositoryInterface
 * Repository service contract for Product Review CRUD and search operations.
 *
 * @api
 */
interface ReviewRepositoryInterface
{
    /**
     * Save review entity.
     *
     * @param ReviewInterface $review
     * @return ReviewInterface
     * @throws CouldNotSaveException
     */
    public function save(ReviewInterface $review): ReviewInterface;

    /**
     * Retrieve review by ID.
     *
     * @param int $reviewId
     * @return ReviewInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $reviewId): ReviewInterface;

    /**
     * Retrieve reviews matching the specified search criteria (filters, pagination, sort).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return ReviewSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface;

    /**
     * Delete review entity.
     *
     * @param ReviewInterface $review
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ReviewInterface $review): bool;

    /**
     * Delete review by ID.
     *
     * @param int $reviewId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $reviewId): bool;
}
