<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @inheritdoc
     */
    public function execute(): Page
    {
        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Majistar_ProductLabels::labels');
        $page->getConfig()->getTitle()->prepend(__('Product Labels'));
        return $page;
    }
}
