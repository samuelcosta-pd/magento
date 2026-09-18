<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Model\ResourceModel\Review as ReviewResourceModel;

/**
 * Class Review
 * Product review model entity.
 */
class Review extends AbstractModel implements ReviewInterface, IdentityInterface
{
    public const CACHE_TAG = 'webjump_product_review';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'webjump_product_review';

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ReviewResourceModel::class);
    }

    /**
     * Return unique ID(s) for each object in system.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): ?int
    {
        $productId = $this->getData(self::PRODUCT_ID);
        return $productId !== null ? (int)$productId : null;
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): ReviewInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getAuthorName(): ?string
    {
        return $this->getData(self::AUTHOR_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setAuthorName(string $authorName): ReviewInterface
    {
        return $this->setData(self::AUTHOR_NAME, $authorName);
    }

    /**
     * @inheritdoc
     */
    public function getComment(): ?string
    {
        return $this->getData(self::COMMENT);
    }

    /**
     * @inheritdoc
     */
    public function setComment(string $comment): ReviewInterface
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * @inheritdoc
     */
    public function getRating(): ?int
    {
        $rating = $this->getData(self::RATING);
        return $rating !== null ? (int)$rating : null;
    }

    /**
     * @inheritdoc
     */
    public function setRating(int $rating): ReviewInterface
    {
        return $this->setData(self::RATING, $rating);
    }

    /**
     * @inheritdoc
     */
    public function isApproved(): bool
    {
        return (bool)$this->getData(self::IS_APPROVED);
    }

    /**
     * @inheritdoc
     */
    public function setIsApproved(bool $isApproved): ReviewInterface
    {
        return $this->setData(self::IS_APPROVED, $isApproved ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): ReviewInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): ReviewInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
