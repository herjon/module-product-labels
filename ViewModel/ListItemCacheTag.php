<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\ViewModel;

use Majistar\ProductLabels\Model\Label;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Hyvä product card processor: tags the cached card, so saving a label refreshes it.
 */
class ListItemCacheTag implements ArgumentInterface
{
    /**
     * Called by Hyvä before rendering each product card.
     *
     * @param AbstractBlock $itemRendererBlock
     * @param Product $product
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeListItemToHtml(AbstractBlock $itemRendererBlock, Product $product): void
    {
        $tags = (array) $itemRendererBlock->getData('cache_tags');
        if (!in_array(Label::CACHE_TAG, $tags, true)) {
            $tags[] = Label::CACHE_TAG;
        }
        $itemRendererBlock->setData('cache_tags', array_values($tags));
    }
}
