<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Ui\DataProvider\Product\Form\Modifier;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Majistar\ProductLabels\Model\Source\Labels;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\AuthorizationInterface;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Hidden;
use Magento\Ui\Component\Form\Element\MultiSelect;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;

/**
 * "Product Labels" section of the product edit page (manual assignments).
 */
class ProductLabels extends AbstractModifier
{
    public const FIELDSET = 'majistar_product_labels';
    public const FIELD = 'majistar_product_labels';
    public const MARKER = 'majistar_product_labels_submitted';
    public const ACL_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @param LocatorInterface $locator
     * @param ProductLabelAssignmentInterface $assignment
     * @param Labels $labelOptions
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly ProductLabelAssignmentInterface $assignment,
        private readonly Labels $labelOptions,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            return $data;
        }
        $productId = $this->locator->getProduct()->getId();
        $labelIds = $productId ? $this->assignment->getLabelIds((int) $productId) : [];
        $key = $productId === null ? '' : (int) $productId;
        $data[$key][self::DATA_SOURCE_DEFAULT][self::FIELD] = array_map('strval', $labelIds);
        // An empty multiselect is not posted: the marker tells the save observer the section was there.
        $data[$key][self::DATA_SOURCE_DEFAULT][self::MARKER] = '1';
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            return $meta;
        }
        $meta[self::FIELDSET] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'label' => __('Product Labels'),
                        'collapsible' => true,
                        'opened' => false,
                        'dataScope' => self::DATA_SCOPE_PRODUCT,
                        'sortOrder' => 1000,
                    ],
                ],
            ],
            'children' => [
                self::FIELD => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Field::NAME,
                                'formElement' => MultiSelect::NAME,
                                'dataType' => Text::NAME,
                                'label' => __('Labels'),
                                'notice' => __(
                                    'Shown on this product in addition to the labels assigned by rules. '
                                    . 'Applies to all store views.'
                                ),
                                'options' => $this->labelOptions->toOptionArray(),
                                'dataScope' => self::FIELD,
                                'sortOrder' => 10,
                            ],
                        ],
                    ],
                ],
                self::MARKER => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Field::NAME,
                                'formElement' => Hidden::NAME,
                                'dataType' => Text::NAME,
                                'dataScope' => self::MARKER,
                                'sortOrder' => 20,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $meta;
    }
}
