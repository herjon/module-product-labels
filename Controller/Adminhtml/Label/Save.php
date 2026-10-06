<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Controller\Adminhtml\Label;

use Majistar\ProductLabels\Api\LabelRepositoryInterface;
use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\Label\FormMapper;
use Majistar\ProductLabels\Model\Label\ImageInfo;
use Majistar\ProductLabels\Model\Label\ImageProcessor;
use Majistar\ProductLabels\Model\Label\PendingConditions;
use Majistar\ProductLabels\Model\LabelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Majistar_ProductLabels::labels';

    /**
     * @param Context $context
     * @param LabelRepositoryInterface $repository
     * @param LabelFactory $labelFactory
     * @param FormMapper $formMapper
     * @param ImageProcessor $imageProcessor
     * @param ImageInfo $imageInfo
     * @param DataPersistorInterface $dataPersistor
     * @param PendingConditions $pendingConditions
     */
    public function __construct(
        Context $context,
        private readonly LabelRepositoryInterface $repository,
        private readonly LabelFactory $labelFactory,
        private readonly FormMapper $formMapper,
        private readonly ImageProcessor $imageProcessor,
        private readonly ImageInfo $imageInfo,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly PendingConditions $pendingConditions
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $post = (array) $this->getRequest()->getPostValue();
        if (!$post) {
            return $redirect->setPath('*/*/');
        }
        $id = (int) ($post['label_id'] ?? 0);
        $persist = $post;
        try {
            /** @var Label $label */
            $label = $id ? $this->repository->getById($id) : $this->labelFactory->create();
            foreach ([FormMapper::SCOPE_PRODUCT, FormMapper::SCOPE_LISTING, FormMapper::SCOPE_CART] as $scope) {
                if (!isset($post[$scope]) || !is_array($post[$scope])) {
                    continue;
                }
                $image = $this->imageProcessor->process($post[$scope]['image'] ?? null);
                $post[$scope]['image'] = $image;
                $persist[$scope]['image'] = $image === null ? [] : [$this->imageInfo->toFormValue($image)];
            }
            $this->formMapper->fromFormData($post, $label);
            $this->repository->save($label);
            $this->dataPersistor->clear(FormMapper::PERSISTOR_KEY);
            $this->messageManager->addSuccessMessage(__('You saved the label.'));
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['id' => $label->getLabelId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('This label no longer exists.'));
            return $redirect->setPath('*/*/');
        } catch (InputException $e) {
            foreach ($e->getErrors() ?: [$e] as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the label.'));
        }
        $this->dataPersistor->set(FormMapper::PERSISTOR_KEY, $persist);
        $this->pendingConditions->remember($post);
        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/new');
    }
}
