<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Block\Adminhtml\Review\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Retrieve button configuration data.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        $reviewId = $this->getReviewId();
        if ($reviewId) {
            $data = [
                'label' => __('Excluir Avaliação'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Tem certeza que deseja excluir esta avaliação?'
                ) . '\', \'' . $this->getDeleteUrl() . '\', {"data": {}})',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * URL to send delete requests to.
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['review_id' => $this->getReviewId()]);
    }
}
