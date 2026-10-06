<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Source;

use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Majistar\ProductLabels\Model\Source\Labels;
use PHPUnit\Framework\TestCase;

class LabelsTest extends TestCase
{
    public function testInactiveLabelsAreListedAndMarked(): void
    {
        $resource = $this->createStub(LabelResource::class);
        $resource->method('getOptionRows')->willReturn([
            ['label_id' => '2', 'name' => 'Sale', 'is_active' => '1'],
            ['label_id' => '7', 'name' => 'Old promo', 'is_active' => '0'],
        ]);

        self::assertSame(
            [
                ['value' => '2', 'label' => 'Sale'],
                ['value' => '7', 'label' => 'Old promo (inactive)'],
            ],
            (new Labels($resource))->toOptionArray()
        );
    }
}
