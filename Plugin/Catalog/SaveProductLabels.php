<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\Catalog;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Assigns the labels sent in extension_attributes.majistar_product_labels; global for the async bulk API.
 */
class SaveProductLabels
{
    /**
     * @param ProductLabelAssignmentInterface $assignment
     */
    public function __construct(private readonly ProductLabelAssignmentInterface $assignment)
    {
    }

    /**
     * Replaces the assigned labels with the ones sent, when the field was sent.
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $result
     * @param ProductInterface $product the product as sent to save()
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductRepositoryInterface $subject,
        ProductInterface $result,
        ProductInterface $product
    ): ProductInterface {
        $labelIds = $product->getExtensionAttributes()?->getMajistarProductLabels();
        if ($labelIds === null) {
            return $result;
        }
        $labelIds = array_values(array_map('intval', $labelIds));
        $this->assignment->setLabelIds((int) $result->getId(), $labelIds);
        $result->getExtensionAttributes()?->setMajistarProductLabels($labelIds);
        return $result;
    }
}
