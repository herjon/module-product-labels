<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Majistar\ProductLabels\Ui\DataProvider\Product\Form\Modifier\ProductLabels;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;

/**
 * Saves the "Product Labels" section of the product edit page.
 */
class SaveProductAssignments implements ObserverInterface
{
    /**
     * @param ProductLabelAssignmentInterface $assignment
     * @param AuthorizationInterface $authorization
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly ProductLabelAssignmentInterface $assignment,
        private readonly AuthorizationInterface $authorization,
        private readonly ManagerInterface $messageManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $product = $event->getData('product');
        $controller = $event->getData('controller');
        if (!$product instanceof DataObject || !$product->getId() || !$controller instanceof AbstractAction) {
            return;
        }
        if (!$this->authorization->isAllowed(ProductLabels::ACL_RESOURCE)) {
            return;
        }
        $post = $controller->getRequest()->getPost('product');
        if (!is_array($post) || empty($post[ProductLabels::MARKER])) {
            return;
        }
        $labelIds = $post[ProductLabels::FIELD] ?? [];
        try {
            $this->assignment->setLabelIds(
                (int) $product->getId(),
                is_array($labelIds) ? array_map('intval', $labelIds) : []
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(__('Product labels were not saved: %1', $e->getMessage()));
        }
    }
}
