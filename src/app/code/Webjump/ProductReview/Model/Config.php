<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'webjump_productreview/general/enabled';
    public const XML_PATH_MIN_RATING = 'webjump_productreview/general/min_rating';
    public const XML_PATH_REQUIRE_APPROVAL = 'webjump_productreview/general/require_approval';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Verifica se o módulo de avaliações está habilitado na loja.
     *
     * @param string|int|null $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Retorna a nota mínima configurada para exibição no frontend.
     *
     * @param string|int|null $store
     * @return int
     */
    public function getMinRating($store = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_MIN_RATING,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $intVal = (int) $value;
        return $intVal > 0 ? $intVal : 1;
    }

    /**
     * Verifica se novas avaliações exigem aprovação prévia.
     *
     * @param string|int|null $store
     * @return bool
     */
    public function isApprovalRequired($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_APPROVAL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
