<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Fixture;

use Majistar\ProductLabels\Api\Data\AppearanceInterfaceFactory;
use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\Label\Appearance;
use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Magento\CatalogRule\Model\Rule\Action\CollectionFactory as ActionCollectionFactory;
use Magento\CatalogRule\Model\Rule\Condition\CombineFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Builds real Label models with mocked collaborators.
 */
trait CreatesLabels
{
    private function appearanceFactory(): AppearanceInterfaceFactory
    {
        $factory = $this->createStub(AppearanceInterfaceFactory::class);
        $factory->method('create')->willReturnCallback(
            static fn(array $arguments = []): Appearance => new Appearance($arguments['data'] ?? [])
        );
        return $factory;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createLabel(array $data = []): Label
    {
        $resource = $this->createStub(LabelResource::class);
        $resource->method('getIdFieldName')->willReturn('label_id');

        return new Label(
            $this->createStub(Context::class),
            $this->createStub(Registry::class),
            $this->createStub(FormFactory::class),
            $this->createStub(TimezoneInterface::class),
            $this->createStub(CombineFactory::class),
            $this->createStub(ActionCollectionFactory::class),
            $this->appearanceFactory(),
            new Json(),
            $resource,
            null,
            $data,
            $this->createStub(ExtensionAttributesFactory::class),
            $this->createStub(AttributeValueFactory::class)
        );
    }
}
