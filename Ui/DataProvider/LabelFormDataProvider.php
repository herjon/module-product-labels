<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Ui\DataProvider;

use Majistar\ProductLabels\Model\Label\FormMapper;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;
use Magento\CatalogRule\Model\Rule\CustomerGroupsOptionsProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Label form data: the edited label, defaults for a new one ('' key) or the data of a failed save.
 */
class LabelFormDataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param FormMapper $formMapper
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerGroupsOptionsProvider $customerGroups
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly FormMapper $formMapper,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly CustomerGroupsOptionsProvider $customerGroups,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }
        $this->loadedData = ['' => $this->formMapper->defaults($this->allCustomerGroupIds())];
        foreach ($this->collection->getItems() as $label) {
            $this->loadedData[(int) $label->getId()] = $this->formMapper->toFormData($label);
        }
        $persisted = $this->dataPersistor->get(FormMapper::PERSISTOR_KEY);
        if (is_array($persisted) && $persisted) {
            $id = (int) ($persisted['label_id'] ?? 0);
            $this->loadedData[$id ?: ''] = $persisted;
            $this->dataPersistor->clear(FormMapper::PERSISTOR_KEY);
        }
        return $this->loadedData;
    }

    /**
     * Ids of every customer group, including NOT LOGGED IN.
     *
     * @return int[]
     */
    private function allCustomerGroupIds(): array
    {
        return array_map(
            static fn(array $option): int => (int) $option['value'],
            $this->customerGroups->toOptionArray()
        );
    }
}
