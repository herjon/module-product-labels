<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Edit and Delete links of the label grid.
 */
class LabelActions extends Column
{
    private const URL_EDIT = 'majistar_productlabels/label/edit';
    private const URL_DELETE = 'majistar_productlabels/label/delete';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $column = (string) $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['label_id'])) {
                continue;
            }
            $name = $this->escaper->escapeHtml((string) ($item['name'] ?? ''));
            $item[$column] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_EDIT, ['id' => $item['label_id']]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_DELETE, ['id' => $item['label_id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete "%1"', $name),
                        'message' => __('Are you sure you want to delete the label "%1"?', $name),
                    ],
                    'post' => true,
                ],
            ];
        }
        unset($item);
        return $dataSource;
    }
}
