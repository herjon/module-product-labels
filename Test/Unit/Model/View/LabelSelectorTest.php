<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\View\LabelSelector;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use PHPUnit\Framework\TestCase;

class LabelSelectorTest extends TestCase
{
    use CreatesLabels;

    private function label(int $id, int $priority, array $data = []): Label
    {
        return $this->createLabel($data + ['label_id' => $id, 'priority' => $priority]);
    }

    /**
     * @param Label[] $labels
     * @return int[]
     */
    private function ids(array $labels): array
    {
        return array_map(static fn(Label $label): int => (int) $label->getLabelId(), $labels);
    }

    public function testOrderByPriorityThenIdAndCutAtMax(): void
    {
        $labels = [$this->label(3, 1), $this->label(1, 5), $this->label(2, 1)];

        self::assertSame([2, 3], $this->ids((new LabelSelector())->select($labels, 2, false, false)));
        self::assertSame([2, 3, 1], $this->ids((new LabelSelector())->select($labels, 5, false, false)));
    }

    public function testStopFurtherLabels(): void
    {
        $labels = [$this->label(1, 0), $this->label(2, 1, ['stop_processing' => 1]), $this->label(3, 2)];

        self::assertSame([1, 2], $this->ids((new LabelSelector())->select($labels, 5, false, false)));
    }

    public function testOutOfStockProductKeepsOnlyOutOfStockLabels(): void
    {
        $labels = [$this->label(1, 0), $this->label(2, 1, ['stock_status' => 'out_of_stock'])];

        self::assertSame([2], $this->ids((new LabelSelector())->select($labels, 5, true, true)));
        self::assertSame([1, 2], $this->ids((new LabelSelector())->select($labels, 5, false, true)));
        self::assertSame([1, 2], $this->ids((new LabelSelector())->select($labels, 5, true, false)));
    }

    public function testNoLabels(): void
    {
        self::assertSame([], (new LabelSelector())->select([], 2, true, true));
    }
}
