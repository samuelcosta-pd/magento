<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Api\Data;

/**
 * Interface ReviewInterface
 * Service contract representing a product review entity.
 *
 * @api
 */
interface ReviewInterface
{
    public const REVIEW_ID = 'review_id';
    public const PRODUCT_ID = 'product_id';
    public const AUTHOR_NAME = 'author_name';
    public const COMMENT = 'comment';
    public const RATING = 'rating';
    public const IS_APPROVED = 'is_approved';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get review ID.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set review ID.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get product ID.
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Set product ID.
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Get author name.
     *
     * @return string|null
     */
    public function getAuthorName(): ?string;

    /**
     * Set author name.
     *
     * @param string $authorName
     * @return $this
     */
    public function setAuthorName(string $authorName): self;

    /**
     * Get review comment.
     *
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * Set review comment.
     *
     * @param string $comment
     * @return $this
     */
    public function setComment(string $comment): self;

    /**
     * Get rating score.
     *
     * @return int|null
     */
    public function getRating(): ?int;

    /**
     * Set rating score.
     *
     * @param int $rating
     * @return $this
     */
    public function setRating(int $rating): self;

    /**
     * Check if review is approved.
     *
     * @return bool
     */
    public function isApproved(): bool;

    /**
     * Set review approval status.
     *
     * @param bool $isApproved
     * @return $this
     */
    public function setIsApproved(bool $isApproved): self;

    /**
     * Get creation timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set creation timestamp.
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get modification timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set modification timestamp.
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}
