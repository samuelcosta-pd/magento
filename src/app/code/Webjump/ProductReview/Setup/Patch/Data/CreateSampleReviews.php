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
use Webjump\ProductReview\Api\Data\ReviewInterface;
use Webjump\ProductReview\Api\Data\ReviewInterfaceFactory;
use Webjump\ProductReview\Api\ReviewRepositoryInterface;

/**
 * Class CreateSampleReviews
 * Data patch to seed initial sample product reviews.
 */
class CreateSampleReviews implements DataPatchInterface, PatchRevertableInterface
{
    private const SAMPLE_AUTHORS = [
        'Mariana Silva',
        'Carlos Eduardo',
        'Beatriz Souza',
        'Rodrigo Mendes',
        'Juliana Ferreira'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReviewRepositoryInterface $reviewRepository
     * @param ReviewInterfaceFactory $reviewFactory
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly ReviewInterfaceFactory $reviewFactory,
        private readonly ProductCollectionFactory $productCollectionFactory
    ) {
    }

    /**
     * Seed 5 sample product reviews.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setPageSize(5);
        $productIds = $productCollection->getAllIds();

        // Fallback default IDs in case catalog is empty
        if (empty($productIds)) {
            $productIds = [1, 2, 3, 4, 5];
        }

        $sampleData = [
            [
                'author_name' => 'Mariana Silva',
                'rating' => 5,
                'comment' => 'Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.',
                'is_approved' => true
            ],
            [
                'author_name' => 'Carlos Eduardo',
                'rating' => 4,
                'comment' => 'Muito bom, entrega rápida e bem embalado. Recomendo a compra.',
                'is_approved' => true
            ],
            [
                'author_name' => 'Beatriz Souza',
                'rating' => 5,
                'comment' => 'Superou as expectativas, acabamento impecável e cor fiel às fotos do site.',
                'is_approved' => true
            ],
            [
                'author_name' => 'Rodrigo Mendes',
                'rating' => 3,
                'comment' => 'Produto razoável, atende ao básico mas o acabamento interno poderia ser melhor.',
                'is_approved' => false
            ],
            [
                'author_name' => 'Juliana Ferreira',
                'rating' => 5,
                'comment' => 'Amei a compra! Chegou antes do prazo previsto e o atendimento foi nota 10.',
                'is_approved' => true
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
     * Revert sample reviews data.
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
                ['author_name IN (?)' => self::SAMPLE_AUTHORS]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
