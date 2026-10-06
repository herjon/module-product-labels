<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Block;

use Majistar\ProductLabels\Model\Label;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Tags every cached page with "majistar_product_label", so saving a label refreshes all pages.
 */
class CacheTag extends AbstractBlock implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        return [Label::CACHE_TAG];
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string
    {
        return '';
    }
}
