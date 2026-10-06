<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Majistar\ProductLabels\Model\Source\Labels;
use Majistar\ProductLabels\Ui\DataProvider\Product\Form\Modifier\ProductLabels;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\AuthorizationInterface;
use PHPUnit\Framework\TestCase;

class ProductLabelsTest extends TestCase
{
    private function modifier(bool $allowed, ?int $productId = 5): ProductLabels
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn($productId);
        $locator = $this->createStub(LocatorInterface::class);
        $locator->method('getProduct')->willReturn($product);

        $assignment = $this->createStub(ProductLabelAssignmentInterface::class);
        $assignment->method('getLabelIds')->willReturn([2, 7]);
        $labels = $this->createStub(Labels::class);
        $labels->method('toOptionArray')->willReturn([['value' => '2', 'label' => 'Sale']]);
        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturnCallback(
            static fn(string $resource): bool => $resource === 'Majistar_ProductLabels::labels' && $allowed
        );

        return new ProductLabels($locator, $assignment, $labels, $authorization);
    }

    public function testDataContainsAssignedLabelsAndMarker(): void
    {
        $data = $this->modifier(true)->modifyData([]);

        self::assertSame(['2', '7'], $data[5]['product'][ProductLabels::FIELD]);
        self::assertSame('1', $data[5]['product'][ProductLabels::MARKER]);
    }

    public function testNewProductHasNoAssignments(): void
    {
        $data = $this->modifier(true, null)->modifyData([]);

        self::assertSame([], $data['']['product'][ProductLabels::FIELD]);
    }

    public function testMetaAddsTheFieldset(): void
    {
        $meta = $this->modifier(true)->modifyMeta([]);
        $field = $meta[ProductLabels::FIELDSET]['children'][ProductLabels::FIELD]['arguments']['data']['config'];

        self::assertSame('multiselect', $field['formElement']);
        self::assertSame([['value' => '2', 'label' => 'Sale']], $field['options']);
    }

    public function testNothingChangesWithoutPermission(): void
    {
        $modifier = $this->modifier(false);

        self::assertSame(['x' => 1], $modifier->modifyMeta(['x' => 1]));
        self::assertSame(['x' => 1], $modifier->modifyData(['x' => 1]));
    }
}
