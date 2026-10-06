<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label;

use Majistar\ProductLabels\Model\LabelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\ConditionFactory;

/**
 * HTML of a new condition builder row (AJAX, as in Catalog Price Rules).
 */
class NewConditionHtml extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @param Context $context
     * @param ConditionFactory $conditionFactory
     * @param LabelFactory $labelFactory
     */
    public function __construct(
        Context $context,
        private readonly ConditionFactory $conditionFactory,
        private readonly LabelFactory $labelFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): Raw
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $types = explode('|', str_replace('-', '/', (string) $this->getRequest()->getParam('type', '')));
        try {
            $condition = $this->conditionFactory->create($types[0]);
        } catch (\InvalidArgumentException) {
            return $result->setContents('');
        }
        if (!$condition instanceof AbstractCondition) {
            return $result->setContents('');
        }
        $condition->setId($this->getRequest()->getParam('id'))
            ->setType($types[0])
            ->setRule($this->labelFactory->create())
            ->setPrefix('conditions');
        if (!empty($types[1])) {
            $condition->setAttribute($types[1]);
        }
        $condition->setJsFormObject($this->getRequest()->getParam('form'));
        $condition->setFormName($this->getRequest()->getParam('form_namespace'));
        return $result->setContents($condition->asHtmlRecursive());
    }
}
