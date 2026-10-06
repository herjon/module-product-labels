<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Api\Data\LabelSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @api
 */
interface LabelRepositoryInterface
{
    /**
     * Validates and saves a label (with its store views, customer groups and appearances).
     *
     * @param \Majistar\ProductLabels\Api\Data\LabelInterface $label
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(LabelInterface $label): LabelInterface;

    /**
     * Load a label by id.
     *
     * @param int $labelId
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $labelId): LabelInterface;

    /**
     * Find the labels matching the search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Majistar\ProductLabels\Api\Data\LabelSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LabelSearchResultsInterface;

    /**
     * Delete a label.
     *
     * @param \Majistar\ProductLabels\Api\Data\LabelInterface $label
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(LabelInterface $label): bool;

    /**
     * Delete a label by id.
     *
     * @param int $labelId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $labelId): bool;
}
