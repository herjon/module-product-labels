<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model;

use Majistar\ProductLabels\Api\Data\LabelSearchResultsInterfaceFactory;
use Majistar\ProductLabels\Model\Label\ImageContentStorage;
use Majistar\ProductLabels\Model\Label\Validator;
use Majistar\ProductLabels\Model\LabelFactory;
use Majistar\ProductLabels\Model\LabelRepository;
use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use Magento\Framework\Api\ImageContent;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class LabelRepositoryTest extends TestCase
{
    use CreatesLabels;

    private function repository(
        LabelResource $resource,
        ?Validator $validator = null,
        ?ImageContentStorage $images = null
    ): LabelRepository {
        $labelFactory = $this->createStub(LabelFactory::class);
        $labelFactory->method('create')->willReturnCallback(fn() => $this->createLabel());

        return new LabelRepository(
            $resource,
            $labelFactory,
            $this->createStub(CollectionFactory::class),
            $this->createStub(CollectionProcessorInterface::class),
            $this->createStub(LabelSearchResultsInterfaceFactory::class),
            $validator ?? new Validator(),
            $images ?? $this->createStub(ImageContentStorage::class)
        );
    }

    public function testInvalidLabelIsNotSavedAndAllErrorsAreReported(): void
    {
        $resource = $this->createMock(LabelResource::class);
        $resource->expects(self::never())->method('save');

        try {
            $this->repository($resource)->save($this->createLabel());
            self::fail('InputException expected');
        } catch (InputException $exception) {
            self::assertGreaterThan(1, count($exception->getErrors()));
        }
    }

    public function testValidLabelIsSaved(): void
    {
        $label = $this->createLabel();
        $label->setName('Sale')->setStoreIds([0])->setCustomerGroupIds([0]);
        $label->getProductAppearance()->setText('Sale');

        $resource = $this->createMock(LabelResource::class);
        $resource->expects(self::once())->method('save')->with($label);

        self::assertSame($label, $this->repository($resource)->save($label));
    }

    public function testGetByIdThrowsWhenMissing(): void
    {
        $resource = $this->createStub(LabelResource::class);
        $resource->method('load');

        $this->expectException(NoSuchEntityException::class);
        $this->repository($resource)->getById(404);
    }

    private function validLabel(): \Majistar\ProductLabels\Model\Label
    {
        $label = $this->createLabel();
        $label->setName('Sale')->setStoreIds([0])->setCustomerGroupIds([0]);
        $label->getProductAppearance()->setText('Sale');
        return $label;
    }

    /**
     * Resource whose load() finds label 7, as stored in the database.
     */
    private function resourceWithStoredLabel(): LabelResource
    {
        $resource = $this->createMock(LabelResource::class);
        $resource->method('load')->willReturnCallback(function ($object, $id) use ($resource) {
            if ((int) $id === 7) {
                $object->setData(['label_id' => 7, 'name' => 'Old name', 'priority' => 5]);
                $object->setStoreIds([1])->setCustomerGroupIds([0, 1]);
                $object->getProductAppearance()->setText('Old text');
                $object->setOrigData();
            }
            return $resource;
        });
        return $resource;
    }

    public function testImageContentIsStoredAndReplacedByTheFileName(): void
    {
        $label = $this->validLabel();
        $content = new ImageContent(['base64_encoded_data' => 'iVBORw0KGgo=', 'name' => 'sale.png']);
        $label->getProductAppearance()->setType('image')->setImageContent($content);
        $images = $this->createMock(ImageContentStorage::class);
        $images->method('validate')->willReturn([]);
        $images->expects(self::once())->method('save')->with($content)->willReturn('sale_1.png');
        $resource = $this->createMock(LabelResource::class);
        $resource->expects(self::once())->method('save');

        $saved = $this->repository($resource, null, $images)->save($label);

        self::assertSame('sale_1.png', $saved->getProductAppearance()->getImage());
        self::assertNull($saved->getProductAppearance()->getImageContent());
    }

    public function testInvalidImageContentIsReportedAndNothingIsStored(): void
    {
        $label = $this->validLabel();
        $label->getProductAppearance()->setType('image')
            ->setImageContent(new ImageContent(['base64_encoded_data' => 'nope', 'name' => 'x.png']));
        $images = $this->createMock(ImageContentStorage::class);
        $images->method('validate')->willReturn([__('The image is not valid base64 data.')]);
        $images->expects(self::never())->method('save');
        $resource = $this->createMock(LabelResource::class);
        $resource->expects(self::never())->method('save');

        try {
            $this->repository($resource, null, $images)->save($label);
            self::fail('InputException expected');
        } catch (InputException $exception) {
            // a single error becomes the exception message, several are listed in getErrors()
            self::assertSame('Product page: The image is not valid base64 data.', $exception->getMessage());
        }
    }

    public function testWebApiUpdateChangesOnlyTheSentFields(): void
    {
        $changes = $this->createLabel(['label_id' => 7, 'name' => 'New name']);
        $resource = $this->resourceWithStoredLabel();
        $resource->expects(self::once())->method('save');

        $saved = $this->repository($resource)->save($changes);

        self::assertSame('New name', $saved->getName());
        self::assertSame(5, $saved->getPriority());
        self::assertSame([1], $saved->getStoreIds());
        self::assertSame('Old text', $saved->getProductAppearance()->getText());
    }

    public function testWebApiUpdateOfAMissingLabelFails(): void
    {
        $resource = $this->resourceWithStoredLabel();
        $resource->expects(self::never())->method('save');

        $this->expectException(NoSuchEntityException::class);
        $this->repository($resource)->save($this->createLabel(['label_id' => 404, 'name' => 'New name']));
    }

    public function testLabelLoadedFromTheDatabaseIsSavedAsItIs(): void
    {
        $label = $this->validLabel();
        $label->setId(7);
        $label->setOrigData();
        $resource = $this->createMock(LabelResource::class);
        $resource->expects(self::never())->method('load');
        $resource->expects(self::once())->method('save')->with($label);

        self::assertSame($label, $this->repository($resource)->save($label));
    }
}
