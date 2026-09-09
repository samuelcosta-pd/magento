<?php

namespace Webjump\CatalogBehavior\Plugin\Block\Stockqty;

use Magento\CatalogInventory\Block\Stockqty\AbstractStockqty;

class AbstractStockqtyPlugin
{
    private const LOW_STOCK_THRESHOLD = 3;

    public function afterIsMsgVisible(
        AbstractStockqty $subject,
        bool $result
    ): bool {
        if ($result) {
            return true;
        }

        $stockQtyLeft = (float) $subject->getStockQtyLeft();

        return $stockQtyLeft > 0 && $stockQtyLeft <= self::LOW_STOCK_THRESHOLD;
    }
}