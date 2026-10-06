<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Resolves the badges of a category or search page in one go.
 */
class PrefetchListingBadges implements ObserverInterface
{
    /**
     * @param Badges $badges
     */
    public function __construct(
        private readonly Badges $badges
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $collection = $observer->getEvent()->getData('collection');
        if ($collection) {
            $products = array_values($collection->getItems());
            $this->badges->prefetch($products, LabelInterface::SHOW_ON_LISTING);
            $this->badges->prefetchVariants($products, LabelInterface::SHOW_ON_LISTING);
        }
    }
}
