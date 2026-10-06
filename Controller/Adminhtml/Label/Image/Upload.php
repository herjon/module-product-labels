<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label\Image;

use Majistar\ProductLabels\Model\Label\ImageFileValidator;
use Majistar\ProductLabels\Model\Label\ImageStorage;
use Majistar\ProductLabels\Model\Label\UploadedFileLocator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @param Context $context
     * @param ImageStorage $imageStorage
     * @param ImageFileValidator $validator
     * @param UploadedFileLocator $fileLocator
     */
    public function __construct(
        Context $context,
        private readonly ImageStorage $imageStorage,
        private readonly ImageFileValidator $validator,
        private readonly UploadedFileLocator $fileLocator
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json
    {
        $paramName = (string) $this->getRequest()->getParam('param_name', 'image');
        try {
            $files = $this->getRequest()->getFiles();
            $files = is_object($files) && method_exists($files, 'toArray') ? $files->toArray() : (array) $files;
            $file = $this->fileLocator->locate($files, $paramName);
            $this->validator->validate($file);
            $result = $this->imageStorage->saveToTmp($paramName, (string) ($file['name'] ?? ''));
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        /** @var Json $json */
        $json = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $json->setData($result);
    }
}
