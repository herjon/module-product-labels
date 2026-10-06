<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\LabelInterface;

/**
 * Appearance of a label in an area: cart areas fall back to listings, listings to the product page.
 */
class AppearancePicker
{
    /**
     * Appearance to render.
     *
     * @param LabelInterface $label
     * @param string $area one of LabelInterface::SHOW_ON_*
     * @return AppearanceInterface
     */
    public function pick(LabelInterface $label, string $area): AppearanceInterface
    {
        $listing = $label->getListingAppearance() ?? $label->getProductAppearance();
        return match ($area) {
            LabelInterface::SHOW_ON_PRODUCT => $label->getProductAppearance(),
            LabelInterface::SHOW_ON_LISTING => $listing,
            default => $label->getCartAppearance() ?? $listing,
        };
    }
}
