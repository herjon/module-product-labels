<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Block\Adminhtml\Label\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'majistar_product_label_form.majistar_product_label_form',
                                'actionName' => 'save',
                                // false: the form posts to ".../back/edit" and reopens after saving
                                'params' => [false],
                            ],
                        ],
                    ],
                ],
            ],
            'sort_order' => 80,
        ];
    }
}
