<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label;

use Majistar\ProductLabels\Api\LabelRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action implements HttpPostActionInterface
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
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $id = (int) $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a label to delete.'));
            return $redirect;
        }
        try {
            $this->repository->deleteById($id);
            $this->messageManager->addSuccessMessage(__('You deleted the label.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $redirect;
    }
}
