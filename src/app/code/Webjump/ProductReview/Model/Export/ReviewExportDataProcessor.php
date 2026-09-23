<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\Export;

use DateTime;
use DateTimeZone;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Formats review data for export with localized dates, status labels, and product names.
 */
class ReviewExportDataProcessor
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @var array<int, string>
     */
    private array $productNames = [];

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        TimezoneInterface $timezone
    ) {
        $this->productRepository = $productRepository;
        $this->timezone = $timezone;
    }

    /**
     * Retrieve headers for export file.
     *
     * @return Phrase[]
     */
    public function getHeaders(): array
    {
        return [
            __('ID'),
            __('ID do Produto'),
            __('Nome do Produto'),
            __('Autor'),
            __('Comentário'),
            __('Nota'),
            __('Status de Aprovação'),
            __('Criado em'),
            __('Atualizado em')
        ];
    }

    /**
     * Format a single review item into an array of exportable row values.
     *
     * @param DataObject|array $item
     * @return array
     */
    public function getRowData($item): array
    {
        $productId = (int)$this->getItemData($item, 'product_id');
        $isApproved = (int)$this->getItemData($item, 'is_approved');

        return [
            $this->getItemData($item, 'review_id'),
            $productId,
            $this->getProductName($productId),
            $this->getItemData($item, 'author_name'),
            $this->getItemData($item, 'comment'),
            $this->getItemData($item, 'rating'),
            $isApproved === 1 ? (string)__('Sim') : (string)__('Não'),
            $this->formatDate((string)$this->getItemData($item, 'created_at')),
            $this->formatDate((string)$this->getItemData($item, 'updated_at'))
        ];
    }

    /**
     * Safely extract data from DataObject or array.
     *
     * @param DataObject|array $item
     * @param string $key
     * @return mixed
     */
    private function getItemData($item, string $key)
    {
        if ($item instanceof DataObject) {
            return $item->getData($key);
        }
        if (is_array($item) && isset($item[$key])) {
            return $item[$key];
        }
        return '';
    }

    /**
     * Resolve product name with in-memory caching.
     *
     * @param int $productId
     * @return string
     */
    private function getProductName(int $productId): string
    {
        if ($productId <= 0) {
            return '';
        }

        if (!isset($this->productNames[$productId])) {
            try {
                $product = $this->productRepository->getById($productId);
                $this->productNames[$productId] = (string)$product->getName();
            } catch (NoSuchEntityException $e) {
                $this->productNames[$productId] = (string)__('Produto não encontrado (ID: %1)', $productId);
            } catch (Exception $e) {
                $this->productNames[$productId] = '';
            }
        }

        return $this->productNames[$productId];
    }

    /**
     * Convert UTC datetime string to Brazilian format.
     *
     * @param string $dateString
     * @return string
     */
    private function formatDate(string $dateString): string
    {
        if (empty($dateString)) {
            return '';
        }

        try {
            $dateTime = new DateTime($dateString, new DateTimeZone('UTC'));
            $converted = $this->timezone->date($dateTime, 'pt_BR', true);
            return $converted->format('d/m/Y H:i:s');
        } catch (Exception $e) {
            return $dateString;
        }
    }
}
