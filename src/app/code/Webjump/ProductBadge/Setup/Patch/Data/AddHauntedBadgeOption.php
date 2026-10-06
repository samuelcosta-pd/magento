<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductBadge\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddHauntedBadgeOption implements DataPatchInterface
{
    public const HAUNTED_OPTION_LABEL = 'Assombrado';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * Adiciona a opção "Assombrado" ao atributo product_badge e garante sua exibição em listagem.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeId = $eavSetup->getAttributeId(
            Product::ENTITY,
            AddProductBadgeAttribute::ATTRIBUTE_CODE
        );

        if ($attributeId) {
            $eavSetup->addAttributeOption([
                'attribute_id' => $attributeId,
                'values' => [
                    self::HAUNTED_OPTION_LABEL
                ]
            ]);

            $eavSetup->updateAttribute(
                Product::ENTITY,
                AddProductBadgeAttribute::ATTRIBUTE_CODE,
                'used_in_product_listing',
                1
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            AddProductBadgeAttribute::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
