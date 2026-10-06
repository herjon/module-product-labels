<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use Majistar\ProductLabels\Model\Config;
use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\View\FilterContext;
use Majistar\ProductLabels\Model\View\LabelFilter;
use Majistar\ProductLabels\Model\View\ProductFacts;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use PHPUnit\Framework\TestCase;

class LabelFilterTest extends TestCase
{
    use CreatesLabels;

    private function label(array $data = []): Label
    {
        return $this->createLabel($data + [
            'label_id' => 1,
            'is_active' => 1,
            'customer_group_ids' => [0, 1],
            'show_on' => 'product,listing',
        ]);
    }

    private function facts(array $overrides = []): ProductFacts
    {
        $values = $overrides + [
            'finalPrice' => 80.0,
            'regularPrice' => 100.0,
            'specialPrice' => 80.0,
            'isSalable' => true,
            'qty' => 3.0,
            'newsFromDate' => null,
            'newsToDate' => null,
            'createdAt' => '2026-01-01 00:00:00',
            'attributes' => [],
        ];
        return new ProductFacts(
            10,
            'SKU-10',
            $values['finalPrice'],
            $values['regularPrice'],
            $values['specialPrice'],
            $values['isSalable'],
            $values['qty'],
            $values['newsFromDate'],
            $values['newsToDate'],
            $values['createdAt'],
            $values['attributes']
        );
    }

    private function context(array $overrides = []): FilterContext
    {
        $values = $overrides + [
            'now' => '2026-10-05 10:00:00',
            'today' => '2026-10-05',
            'group' => 0,
            'area' => 'listing',
            'newSource' => Config::NEW_SOURCE_NEWS_DATES,
            'newDays' => 30,
        ];
        return new FilterContext(
            $values['now'],
            $values['today'],
            $values['group'],
            $values['area'],
            $values['newSource'],
            $values['newDays']
        );
    }

    private function passes(
        Label $label,
        ?ProductFacts $facts = null,
        ?FilterContext $context = null,
        bool $manual = false
    ): bool {
        return (new LabelFilter())->passes($label, $facts ?? $this->facts(), $context ?? $this->context(), $manual);
    }

    public function testPlainLabelPasses(): void
    {
        self::assertTrue($this->passes($this->label()));
    }

    public function testActivePeriodGroupAndArea(): void
    {
        self::assertFalse($this->passes($this->label(['active_from' => '2026-10-05 10:00:01'])));
        $period = ['active_from' => '2026-10-05 10:00:00', 'active_to' => '2026-10-06 00:00:00'];
        self::assertTrue($this->passes($this->label($period)));
        self::assertFalse($this->passes($this->label(['active_to' => '2026-10-05 09:59:59'])));
        self::assertFalse($this->passes($this->label(), null, $this->context(['group' => 3])));
        self::assertFalse($this->passes($this->label(), null, $this->context(['area' => 'cart'])));
    }

    public function testInactiveLabelNeverPasses(): void
    {
        self::assertFalse($this->passes($this->label(['is_active' => 0]), null, null, true));
    }

    public function testOnSaleWithMinimumDiscount(): void
    {
        self::assertTrue($this->passes($this->label(['is_on_sale' => 1, 'min_discount_percent' => 20])));
        self::assertFalse($this->passes($this->label(['is_on_sale' => 1, 'min_discount_percent' => 21])));
        self::assertFalse($this->passes($this->label(['is_on_sale' => 1]), $this->facts(['finalPrice' => 100.0])));
    }

    public function testStockFilters(): void
    {
        self::assertTrue($this->passes($this->label(['stock_status' => 'in_stock'])));
        self::assertFalse($this->passes($this->label(['stock_status' => 'out_of_stock'])));
        $outOfStock = $this->facts(['isSalable' => false]);
        self::assertTrue($this->passes($this->label(['stock_status' => 'out_of_stock']), $outOfStock));
        self::assertTrue($this->passes($this->label(['stock_status' => 'low_stock', 'low_stock_qty' => 5])));
        self::assertFalse($this->passes($this->label(['stock_status' => 'low_stock', 'low_stock_qty' => 3])));
        $lowStockLabel = $this->label(['stock_status' => 'low_stock', 'low_stock_qty' => 5]);
        self::assertFalse($this->passes($lowStockLabel, $this->facts(['qty' => null])));
    }

    public function testPriceRangeUsesTheFinalPrice(): void
    {
        self::assertTrue($this->passes($this->label(['price_from' => 80, 'price_to' => 80])));
        self::assertFalse($this->passes($this->label(['price_from' => 81])));
        self::assertFalse($this->passes($this->label(['price_to' => 79.99])));
    }

    public function testNewWithNewsDates(): void
    {
        $label = $this->label(['is_new' => 1]);

        self::assertFalse($this->passes($label));
        self::assertTrue($this->passes($label, $this->facts(['newsFromDate' => '2026-10-05'])));
        $period = $this->facts(['newsFromDate' => '2026-10-01', 'newsToDate' => '2026-10-05']);
        self::assertTrue($this->passes($label, $period));
        self::assertFalse($this->passes($label, $this->facts(['newsFromDate' => '2026-10-06'])));
        $expired = $this->facts(['newsFromDate' => '2026-09-01', 'newsToDate' => '2026-10-04']);
        self::assertFalse($this->passes($label, $expired));
    }

    public function testNewWithDaysSinceCreation(): void
    {
        $label = $this->label(['is_new' => 1]);
        $context = $this->context(['newSource' => Config::NEW_SOURCE_CREATED_DAYS, 'newDays' => 30]);

        $justInTime = $this->facts(['createdAt' => '2026-09-05 10:00:00']);
        $tooOld = $this->facts(['createdAt' => '2026-09-05 09:59:59']);
        self::assertTrue($this->passes($label, $justInTime, $context));
        self::assertFalse($this->passes($label, $tooOld, $context));
    }

    public function testManualAssignmentSkipsQuickFiltersButNotVisibility(): void
    {
        $label = $this->label(['is_on_sale' => 1, 'stock_status' => 'out_of_stock', 'price_from' => 500]);

        self::assertFalse($this->passes($label));
        self::assertTrue($this->passes($label, null, null, true));
        self::assertFalse($this->passes($label, null, $this->context(['area' => 'checkout']), true));
    }
}
