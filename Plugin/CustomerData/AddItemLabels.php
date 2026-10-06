<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\CustomerData;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\View\PurchasedProduct;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Quote\Model\Quote\Item;

/**
 * Adds the mini-cart badges of each item to the "cart" customer section (items[].majistar_product_labels).
 */
class AddItemLabels
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
     * Badge groups (see Badges::toGroups) of the purchased product, area "minicart".
     *
     * @param AbstractItem $subject
     * @param array $result
     * @param Item $item
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetItemData(AbstractItem $subject, array $result, Item $item): array
    {
        $result['majistar_product_labels'] = $this->badges->toGroups(
            $this->badges->getBadges($this->purchasedProduct->get($item), LabelInterface::SHOW_ON_MINICART),
            LabelInterface::SHOW_ON_MINICART
        );
        return $result;
    }
}
