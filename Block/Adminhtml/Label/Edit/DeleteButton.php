<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Block\Adminhtml\Label\Edit;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        $id = (int) $this->request->getParam('id');
        if (!$id) {
            return [];
        }
        $url = $this->urlBuilder->getUrl('majistar_productlabels/label/delete', ['id' => $id]);
        return [
            'label' => __('Delete Label'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {data: {}})",
                $this->escaper->escapeJs((string) __('Are you sure you want to delete this label?')),
                $this->escaper->escapeJs($url)
            ),
            'sort_order' => 20,
        ];
    }
}
