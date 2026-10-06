<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label;

use Majistar\ProductLabels\Api\LabelRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @param Context $context
     * @param LabelRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly LabelRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $id = (int) $this->getRequest()->getParam('id');
        $title = __('New Label');
        if ($id) {
            try {
                $title = __('Edit Label "%1"', $this->repository->getById($id)->getName());
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('This label no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Majistar_ProductLabels::labels');
        $page->getConfig()->getTitle()->prepend(__('Product Labels'));
        $page->getConfig()->getTitle()->prepend($title);
        return $page;
    }
}
