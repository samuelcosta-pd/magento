<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Api\Data\ReviewSearchResultsInterface;
use Webjump\ProductReview\Api\Data\ReviewSearchResultsInterfaceFactory;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;
use Webjump\ProductReview\Model\ResourceModel\Review as ReviewResourceModel;
use Webjump\ProductReview\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;

/**
 * Class ReviewRepository
 * Concrete repository implementation for Product Review service contract.
 */
class ReviewRepository implements ReviewRepositoryInterface
{
    /**
     * @param ReviewResourceModel $resource
     * @param ReviewFactory $reviewFactory
     * @param ReviewCollectionFactory $collectionFactory
     * @param ReviewSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly ReviewResourceModel $resource,
        private readonly ReviewFactory $reviewFactory,
        private readonly ReviewCollectionFactory $collectionFactory,
        private readonly ReviewSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ReviewInterface $review): ReviewInterface
    {
        if (!$review instanceof AbstractModel) {
            throw new CouldNotSaveException(
                __('Review entity must extend AbstractModel to be persisted by the ResourceModel.')
            );
        }

        $this->validateReview($review);

        try {
            $this->resource->save($review);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save review: %1', $exception->getMessage()),
                $exception
            );
        }

        return $review;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $reviewId): ReviewInterface
    {
        $review = $this->reviewFactory->create();
        $this->resource->load($review, $reviewId);

        if (!$review->getId()) {
            throw new NoSuchEntityException(
                __('The review with ID "%1" does not exist.', $reviewId)
            );
        }

        return $review;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(ReviewInterface $review): bool
    {
        if (!$review instanceof AbstractModel) {
            throw new CouldNotDeleteException(
                __('Review entity must extend AbstractModel to be deleted by the ResourceModel.')
            );
        }

        try {
            $this->resource->delete($review);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete review: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    /**
     * Validate review business rules before persistence.
     *
     * @param ReviewInterface $review
     * @return void
     * @throws CouldNotSaveException
     */
    private function validateReview(ReviewInterface $review): void
    {
        $rating = $review->getRating();
        if ($rating === null || $rating < 1 || $rating > 5) {
            throw new CouldNotSaveException(
                __('Rating must be an integer between 1 and 5. Got: %1', (string)$rating)
            );
        }

        if (empty(trim((string)$review->getAuthorName()))) {
            throw new CouldNotSaveException(__('Author name is required and cannot be empty.'));
        }

        if (empty(trim((string)$review->getComment()))) {
            throw new CouldNotSaveException(__('Comment is required and cannot be empty.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $reviewId): bool
    {
        return $this->delete($this->getById($reviewId));
    }
}
