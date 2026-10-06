<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Api\Data\LabelSearchResultsInterface;
use Majistar\ProductLabels\Api\Data\LabelSearchResultsInterfaceFactory;
use Majistar\ProductLabels\Api\LabelRepositoryInterface;
use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Model\Label\ImageContentStorage;
use Majistar\ProductLabels\Model\Label\Validator;
use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;

/**
 * Validates labels on every save.
 */
class LabelRepository implements LabelRepositoryInterface
{
    /**
     * @param LabelResource $resource
     * @param LabelFactory $labelFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LabelSearchResultsInterfaceFactory $searchResultsFactory
     * @param Validator $validator
     * @param ImageContentStorage $imageContentStorage
     */
    public function __construct(
        private readonly LabelResource $resource,
        private readonly LabelFactory $labelFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly LabelSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly Validator $validator,
        private readonly ImageContentStorage $imageContentStorage
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(LabelInterface $label): LabelInterface
    {
        $model = $this->toModel($label);
        if ($model->getId() && $model->getOrigData() === null) {
            $model = $this->mergeIntoStoredLabel($model);
        }
        $errors = array_merge($this->validator->validate($model), $this->validateImageContents($model));
        if ($errors) {
            $exception = new InputException();
            foreach ($errors as $error) {
                $exception->addError($error);
            }
            throw $exception;
        }
        $this->storeImageContents($model);
        try {
            $this->resource->save($model);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the label: %1', $e->getMessage()), $e);
        }
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $labelId): LabelInterface
    {
        $label = $this->labelFactory->create();
        $this->resource->load($label, $labelId);
        if (!$label->getId()) {
            throw new NoSuchEntityException(__('The label with ID "%1" does not exist.', $labelId));
        }
        return $label;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LabelSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems(array_values($collection->getItems()));
        $results->setTotalCount($collection->getSize());
        return $results;
    }

    /**
     * @inheritdoc
     */
    public function delete(LabelInterface $label): bool
    {
        try {
            $this->resource->delete($this->toModel($label));
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the label: %1', $e->getMessage()), $e);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $labelId): bool
    {
        return $this->delete($this->getById($labelId));
    }

    /**
     * Web API update: applies the sent fields to the stored label (sent appearances replace the stored ones).
     *
     * @param Label $changes
     * @return Label
     * @throws NoSuchEntityException
     */
    private function mergeIntoStoredLabel(Label $changes): Label
    {
        /** @var Label $stored */
        $stored = $this->getById((int) $changes->getId());
        foreach ($changes->getData() as $key => $value) {
            $stored->setData($key, $value);
        }
        return $stored;
    }

    /**
     * Appearances of a label by context name (for error messages).
     *
     * @param Label $label
     * @return array<string, AppearanceInterface>
     */
    private function appearances(Label $label): array
    {
        return array_filter([
            (string) __('Product page') => $label->getProductAppearance(),
            (string) __('Product listings') => $label->getListingAppearance(),
            (string) __('Cart') => $label->getCartAppearance(),
        ]);
    }

    /**
     * Problems with images sent as base64.
     *
     * @param Label $label
     * @return Phrase[]
     */
    private function validateImageContents(Label $label): array
    {
        $errors = [];
        foreach ($this->appearances($label) as $context => $appearance) {
            $content = $appearance->getImageContent();
            foreach ($content ? $this->imageContentStorage->validate($content) : [] as $error) {
                // already translated; placeholders would show up as "%1: %2" in web API responses
                $errors[] = new Phrase(sprintf('%s: %s', $context, $error));
            }
        }
        return $errors;
    }

    /**
     * Saves images sent as base64 and puts their file names in the appearances.
     *
     * @param Label $label
     * @return void
     */
    private function storeImageContents(Label $label): void
    {
        foreach ($this->appearances($label) as $appearance) {
            $content = $appearance->getImageContent();
            if ($content) {
                $appearance->setImage($this->imageContentStorage->save($content))->setImageContent(null);
            }
        }
    }

    /**
     * The resource model works with the Label model (the preference of LabelInterface).
     *
     * @param LabelInterface $label
     * @return Label
     * @throws CouldNotSaveException
     */
    private function toModel(LabelInterface $label): Label
    {
        if (!$label instanceof Label) {
            throw new CouldNotSaveException(__('Unsupported label implementation "%1".', get_class($label)));
        }
        return $label;
    }
}
