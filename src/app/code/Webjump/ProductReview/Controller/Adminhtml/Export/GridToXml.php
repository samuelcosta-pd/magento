<?php
/**
 * Copyright © Webjump. All rights reserved.
 */

declare(strict_types=1);

namespace Webjump\ProductReview\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Webjump\ProductReview\Model\Export\ConvertToXml;

/**
 * Controller to handle Product Review grid export to Excel XML.
 */
class GridToXml extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews_export';

    /**
     * @var ConvertToXml
     */
    protected ConvertToXml $converter;

    /**
     * @var FileFactory
     */
    protected FileFactory $fileFactory;

    /**
     * @param Context $context
     * @param ConvertToXml $converter
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        ConvertToXml $converter,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Export review grid to Excel XML file.
     *
     * @return ResponseInterface
     * @throws LocalizedException
     */
    public function execute(): ResponseInterface
    {
        return $this->fileFactory->create(
            'avaliacoes_produtos.xml',
            $this->converter->getXmlFile(),
            'var'
        );
    }
}
