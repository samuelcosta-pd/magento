<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductBadge\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Webjump\ProductBadge\Setup\Patch\Data\AddProductBadgeAttribute;

class Badge implements ArgumentInterface
{
    /**
     * Retorna o rótulo amigável do selo atribuído ao produto, ou null se não houver selo.
     *
     * @param Product|null $product
     * @return string|null
     */
    public function getBadgeLabel(?Product $product): ?string
    {
        if (!$product) {
            return null;
        }

        $attributeCode = AddProductBadgeAttribute::ATTRIBUTE_CODE;

        try {
            $label = $product->getAttributeText($attributeCode);

            if ($label instanceof Phrase) {
                $label = (string) $label;
            } elseif (is_array($label)) {
                $label = implode(', ', $label);
            }

            if (is_string($label) && trim($label) !== '') {
                return trim($label);
            }

            // Fallback caso o dado tenha sido atribuído diretamente como texto ou booleano
            $rawVal = $product->getData($attributeCode);
            if (is_string($rawVal) && trim($rawVal) !== '' && !is_numeric($rawVal)) {
                return trim($rawVal);
            }
        } catch (\Throwable $e) {
            // Em caso de erro na resolução de fonte do atributo, falha silenciosa para não quebrar a PDP
            return null;
        }

        return null;
    }

    /**
     * Verifica se o produto possui um selo ativo preenchido.
     *
     * @param Product|null $product
     * @return bool
     */
    public function hasBadge(?Product $product): bool
    {
        return $this->getBadgeLabel($product) !== null;
    }

    /**
     * Retorna um modificador de classe CSS sanitizado baseado no texto do selo.
     *
     * Ex: "Sustentável" -> "sustentavel"
     *
     * @param Product|null $product
     * @return string
     */
    public function getBadgeCssClass(?Product $product): string
    {
        $label = $this->getBadgeLabel($product);
        if (!$label) {
            return '';
        }

        $slug = mb_strtolower($label, 'UTF-8');
        $slug = (string) preg_replace('/[áàãâä]/u', 'a', $slug);
        $slug = (string) preg_replace('/[éèêë]/u', 'e', $slug);
        $slug = (string) preg_replace('/[íìîï]/u', 'i', $slug);
        $slug = (string) preg_replace('/[óòõôö]/u', 'o', $slug);
        $slug = (string) preg_replace('/[úùûü]/u', 'u', $slug);
        $slug = (string) preg_replace('/[ç]/u', 'c', $slug);
        $slug = (string) preg_replace('/[^a-z0-9_-]/', '-', $slug);
        $slug = trim($slug, '-');

        return 'product-badge--' . $slug;
    }
}
