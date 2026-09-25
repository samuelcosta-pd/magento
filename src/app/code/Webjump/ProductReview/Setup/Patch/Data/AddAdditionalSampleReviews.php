<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Setup\Patch\Data;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Psr\Log\LoggerInterface;
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Api\Data\ReviewInterfaceFactory;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;

/**
 * Class AddAdditionalSampleReviews
 * Data patch to seed 6 additional product reviews for admin grid testing.
 */
class AddAdditionalSampleReviews implements DataPatchInterface, PatchRevertableInterface
{
    private const ADDITIONAL_AUTHORS = [
        'Lucas Rocha',
        'Camila Nogueira',
        'Felipe Albuquerque',
        'Fernanda Lima',
        'Gustavo Henrique',
        'Patrícia Antunes'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReviewRepositoryInterface $reviewRepository
     * @param ReviewInterfaceFactory $reviewFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly ReviewInterfaceFactory $reviewFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Seed 6 additional sample product reviews.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setPageSize(6);
        $productIds = $productCollection->getAllIds();

        if (empty($productIds)) {
            $this->logger->warning(
                'Webjump_ProductReview: No products found in catalog. '
                . 'Additional sample reviews were NOT inserted.'
            );
            $this->moduleDataSetup->getConnection()->endSetup();
            return;
        }

        $sampleData = [
            [
                'author_name' => 'Lucas Rocha',
                'rating' => 4,
                'comment' => 'Ótimo produto, material resistente e entrega antes do prazo combinado.',
                'is_approved' => false
            ],
            [
                'author_name' => 'Camila Nogueira',
                'rating' => 5,
                'comment' => 'Simplesmente incrível! Design muito moderno e atendeu todas as minhas necessidades.',
                'is_approved' => true
            ],
            [
                'author_name' => 'Felipe Albuquerque',
                'rating' => 2,
                'comment' => 'O produto é bonito, mas a costura veio com alguns fios soltos. Esperava mais pelo valor.',
                'is_approved' => false
            ],
            [
                'author_name' => 'Fernanda Lima',
                'rating' => 5,
                'comment' => 'Adorei a compra! Veio muito bem embalado e a cor é idêntica à do anúncio.',
                'is_approved' => true
            ],
            [
                'author_name' => 'Gustavo Henrique',
                'rating' => 4,
                'comment' => 'Bom custo-benefício. Uso diariamente e não apresentou nenhum defeito até o momento.',
                'is_approved' => true
            ],
            [
                'author_name' => 'Patrícia Antunes',
                'rating' => 3,
                'comment' => 'Tamanho ficou um pouco justo em relação à tabela de medidas, mas o tecido é confortável.',
                'is_approved' => false
            ]
        ];

        foreach ($sampleData as $index => $item) {
            $productId = (int)($productIds[$index % count($productIds)]);

            /** @var ReviewInterface $review */
            $review = $this->reviewFactory->create();
            $review->setProductId($productId);
            $review->setAuthorName($item['author_name']);
            $review->setRating($item['rating']);
            $review->setComment($item['comment']);
            $review->setIsApproved($item['is_approved']);

            $this->reviewRepository->save($review);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Revert additional sample reviews.
     *
     * @return void
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('webjump_product_review');

        if ($connection->isTableExists($tableName)) {
            $connection->delete(
                $tableName,
                ['author_name IN (?)' => self::ADDITIONAL_AUTHORS]
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
            CreateSampleReviews::class
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
