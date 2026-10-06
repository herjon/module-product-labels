<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\Catalog;

use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Image;

/**
 * Draws the badges over a marked product image: Hyvä's card and cart templates have no slot for them.
 */
class AddBadgesToImage
{
    /**
     * Image block data: ['product' => ProductInterface, 'area' => LabelInterface::SHOW_ON_*].
     */
    public const TARGET = 'majistar_product_labels_target';

    /**
     * @param Badges $badges
     */
    public function __construct(private readonly Badges $badges)
    {
    }

    /**
     * Image HTML with the badges of the marked product, if any.
     *
     * @param Image $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(Image $subject, $result)
    {
        $target = $subject->getData(self::TARGET);
        if (!is_array($target) || !($target['product'] ?? null) instanceof ProductInterface || $result === '') {
            return $result;
        }
        $badges = $this->badges->renderBadges($target['product'], (string) ($target['area'] ?? ''));
        if (trim($badges) === '') {
            return $result;
        }
        return '<div class="relative">' . $result . $badges . '</div>';
    }
}
