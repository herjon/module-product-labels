<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Block\Adminhtml\Label\Edit;

use Majistar\ProductLabels\Model\Label\PendingConditions;
use Majistar\ProductLabels\Model\LabelFactory;
use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions as ConditionsRenderer;
use Magento\Rule\Model\Condition\AbstractCondition;

/**
 * "Advanced Conditions" of the label form: the Catalog Price Rules condition builder.
 */
class Conditions extends Generic
{
    public const FORM_NAME = 'majistar_product_label_form';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param ConditionsRenderer $conditionsRenderer
     * @param LabelFactory $labelFactory
     * @param LabelResource $labelResource
     * @param PendingConditions $pendingConditions
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        private readonly ConditionsRenderer $conditionsRenderer,
        private readonly LabelFactory $labelFactory,
        private readonly LabelResource $labelResource,
        private readonly PendingConditions $pendingConditions,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        $label = $this->labelFactory->create();
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            $this->labelResource->load($label, $id);
        }
        $this->pendingConditions->restore($label);
        $fieldsetId = $label->getConditionsFieldSetId(self::FORM_NAME);

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        /** @var Fieldset $renderer */
        $renderer = $this->getLayout()->createBlock(Fieldset::class);
        $renderer->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl(
                'majistar_productlabels/label/newConditionHtml/form/' . $fieldsetId,
                ['form_namespace' => self::FORM_NAME]
            ))
            ->setFieldSetId($fieldsetId);

        $fieldset = $form->addFieldset(
            $fieldsetId,
            ['legend' => __('Show the label only on products matching these conditions (leave empty for all products)')]
        )->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', [
            'name' => 'conditions',
            'label' => __('Conditions'),
            'title' => __('Conditions'),
            'required' => true,
            // makes the UI form post these legacy inputs too
            'data-form-part' => self::FORM_NAME,
        ])->setRule($label)->setRenderer($this->conditionsRenderer);

        $form->setValues($label->getData());
        $this->setConditionFormName($label->getConditions(), self::FORM_NAME, $fieldsetId);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Gives every condition the form names used in the generated inputs and JS.
     *
     * @param AbstractCondition $conditions
     * @param string $formName
     * @param string $jsFormName
     * @return void
     */
    private function setConditionFormName(AbstractCondition $conditions, string $formName, string $jsFormName): void
    {
        $conditions->setFormName($formName);
        $conditions->setJsFormObject($jsFormName);
        $children = $conditions->getConditions();
        if (is_array($children)) {
            foreach ($children as $condition) {
                $this->setConditionFormName($condition, $formName, $jsFormName);
            }
        }
    }
}
