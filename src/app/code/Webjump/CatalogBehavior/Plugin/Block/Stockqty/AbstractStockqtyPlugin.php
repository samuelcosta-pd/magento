<?php
/**
 * Copyright © Webjump. All rights reserved.
 *
 * Plugin after sobre AbstractStockqty::isMsgVisible().
 *
 * Objetivo: exibir a mensagem "Últimas unidades!" quando o produto
 * tiver poucas unidades disponíveis, independentemente do threshold
 * configurado em Stores > Config > Catalog > Inventory > "Only X left Threshold".
 *
 * O plugin não altera:
 * - quantidade real de estoque
 * - salabilidade do produto
 * - comportamento de carrinho ou checkout
 */

declare(strict_types=1);

namespace Webjump\CatalogBehavior\Plugin\Block\Stockqty;

use Magento\CatalogInventory\Block\Stockqty\AbstractStockqty;

class AbstractStockqtyPlugin
{
    /**
     * Quantidade máxima (inclusive) para considerar estoque baixo.
     */
    private const LOW_STOCK_THRESHOLD = 3;

    /**
     * Plugin after em isMsgVisible().
     *
     * - Se o resultado original já for true (threshold do admin ativado e qty <= threshold),
     *   mantemos true sem modificação.
     * - Se o resultado original for false, verificamos nossa própria regra:
     *   quantidade disponível entre 1 e LOW_STOCK_THRESHOLD → retorna true.
     *
     * @param AbstractStockqty $subject
     * @param bool             $result  Resultado retornado pelo método/MSI
     * @return bool
     */
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