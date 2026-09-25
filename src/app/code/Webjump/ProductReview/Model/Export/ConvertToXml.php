<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\SearchResultIteratorFactory;

/**
 * Custom Excel XML exporter for Product Review grid.
 */
class ConvertToXml
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
     * @var ExcelFactory
     */
    protected ExcelFactory $excelFactory;

    /**
     * @var SearchResultIteratorFactory
     */
    protected SearchResultIteratorFactory $iteratorFactory;

    /**
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param ReviewExportDataProcessor $dataProcessor
     * @param ExcelFactory $excelFactory
     * @param SearchResultIteratorFactory $iteratorFactory
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        ReviewExportDataProcessor $dataProcessor,
        ExcelFactory $excelFactory,
        SearchResultIteratorFactory $iteratorFactory
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dataProcessor = $dataProcessor;
        $this->excelFactory = $excelFactory;
        $this->iteratorFactory = $iteratorFactory;
    }

    /**
     * Row callback to format item values for Excel.
     *
     * @param mixed $document
     * @return array
     */
    public function getRowData($document): array
    {
        return $this->dataProcessor->getRowData($document);
    }

    /**
     * Generates Excel XML file from filtered grid items and returns file response info.
     *
     * @return array<string, mixed>
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function getXmlFile(): array
    {
        $component = $this->filter->getComponent();

        // phpcs:ignore Magento2.Security.InsecureFunction
        $name = md5(microtime());
        $file = 'export/' . $component->getName() . $name . '.xml';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();

        $component->getContext()->getDataProvider()->setLimit(0, 0);
        $searchResult = $component->getContext()->getDataProvider()->getSearchResult();
        $items = $searchResult->getItems() ?: [];

        $searchResultIterator = $this->iteratorFactory->create(['items' => $items]);

        /** @var Excel $excel */
        $excel = $this->excelFactory->create([
            'iterator' => $searchResultIterator,
            'rowCallback' => [$this, 'getRowData']
        ]);

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $excel->setDataHeader($this->dataProcessor->getHeaders());
        $excel->write($stream, $component->getName() . '.xml');

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }
}
