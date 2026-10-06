<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\View\PurchasedProduct;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Adds the checkout badges to "image_overlay_html" of a Majistar_Checkout summary row.
 */
class AddCheckoutItemLabels implements ObserverInterface
{
    /**
     * @param Badges $badges
     * @param PurchasedProduct $purchasedProduct
     */
    public function __construct(
        private readonly Badges $badges,
        private readonly PurchasedProduct $purchasedProduct
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $item = $observer->getEvent()->getData('quote_item');
        $transport = $observer->getEvent()->getData('transport');
        if (!$item instanceof Item || !$transport instanceof DataObject) {
            return;
        }
        $html = $this->badges->renderBadges($this->purchasedProduct->get($item), LabelInterface::SHOW_ON_CHECKOUT);
        $transport->setData('image_overlay_html', $transport->getData('image_overlay_html') . $html);
    }
}
