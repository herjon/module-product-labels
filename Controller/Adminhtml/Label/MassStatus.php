<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label;

use Majistar\ProductLabels\Api\LabelRepositoryInterface;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param LabelRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly LabelRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): Redirect
    {
        $isActive = (bool) $this->getRequest()->getParam('status');
        $count = 0;
        try {
            foreach ($this->filter->getCollection($this->collectionFactory->create()) as $label) {
                $label->setIsActive($isActive);
                $this->repository->save($label);
                $count++;
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        if ($count) {
            $this->messageManager->addSuccessMessage(
                $isActive
                    ? __('A total of %1 label(s) have been enabled.', $count)
                    : __('A total of %1 label(s) have been disabled.', $count)
            );
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
