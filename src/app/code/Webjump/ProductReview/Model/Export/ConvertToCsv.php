<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Custom CSV exporter for Product Review grid.
 */
class ConvertToCsv
{
    /**
     * @var WriteInterface
     */
    protected WriteInterface $directory;

    /**
     * @var Filter
     */
    protected Filter $filter;

    /**
     * @var ReviewExportDataProcessor
     */
    protected ReviewExportDataProcessor $dataProcessor;

    /**
     * @var int
     */
    protected int $pageSize;

    /**
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param ReviewExportDataProcessor $dataProcessor
     * @param int $pageSize
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        ReviewExportDataProcessor $dataProcessor,
        int $pageSize = 200
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dataProcessor = $dataProcessor;
        $this->pageSize = $pageSize;
    }

    /**
     * Generates CSV file from filtered grid items and returns file response info.
     *
     * @return array<string, mixed>
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function getCsvFile(): array
    {
        $component = $this->filter->getComponent();

        // phpcs:ignore Magento2.Security.InsecureFunction
        $name = md5(microtime());
        $file = 'export/' . $component->getName() . $name . '.csv';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $stream->writeCsv($this->dataProcessor->getHeaders());

        $i = 1;
        $searchCriteria = $dataProvider->getSearchCriteria()
            ->setCurrentPage($i)
            ->setPageSize($this->pageSize);

        $totalCount = null;
        $totalPagesCount = null;

        do {
            $searchResult = $dataProvider->getSearchResult();
            $items = $searchResult->getItems() ?: [];

            if ($totalCount === null) {
                $totalCount = $searchResult->getTotalCount();
                $totalPagesCount = (int) ceil($totalCount / $this->pageSize);
            }

            $searchResult->setTotalCount($totalCount);

            foreach ($items as $item) {
                $stream->writeCsv($this->dataProcessor->getRowData($item));
            }

            $searchCriteria->setCurrentPage(++$i);
        } while ($i <= $totalPagesCount);

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }
}
