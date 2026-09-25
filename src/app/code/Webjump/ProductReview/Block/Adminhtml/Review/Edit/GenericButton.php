<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Block\Adminhtml\Review\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ReviewRepositoryInterface
     */
    protected $reviewRepository;

    /**
     * @param Context $context
     * @param ReviewRepositoryInterface $reviewRepository
     */
    public function __construct(
        Context $context,
        ReviewRepositoryInterface $reviewRepository
    ) {
        $this->context = $context;
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * Return Review ID
     *
     * @return int|null
     */
    public function getReviewId(): ?int
    {
        $reviewId = $this->context->getRequest()->getParam('review_id');
        if ($reviewId !== null) {
            try {
                return (int) $this->reviewRepository->getById((int) $reviewId)->getId();
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
