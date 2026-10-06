<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Index;

use Majistar\ProductLabels\Model\Index\CategoryExpander;
use Majistar\ProductLabels\Model\Index\LabelMatcher;
use Majistar\ProductLabels\Model\Label;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Iterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelMatcherTest extends TestCase
{
    /**
     * Product collection whose filters return itself; a mock only when the test sets expectations on it.
     *
     * @return Collection&MockObject
     */
    private function collection(bool $withExpectations = true): Collection
    {
        $collection = $withExpectations ? $this->createMock(Collection::class) : $this->createStub(Collection::class);
        foreach (['setStoreId', 'addStoreFilter', 'addIdFilter', 'addCategoriesFilter', 'addAttributeToFilter'] as $m) {
            $collection->method($m)->willReturnSelf();
        }
        $collection->method('getSelect')->willReturn($this->createStub(Select::class));
        return $collection;
    }

    /**
     * Matcher over a collection; category 12 is an anchor category with the children 14 and 15.
     */
    private function matcher(Collection $collection, ?Iterator $iterator = null): LabelMatcher
    {
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $expander = $this->createStub(CategoryExpander::class);
        $expander->method('expand')->willReturnCallback(
            static fn(array $ids): array => $ids === [12] ? [12, 14, 15] : $ids
        );
        return new LabelMatcher(
            $factory,
            $iterator ?? $this->createStub(Iterator::class),
            $this->createStub(ProductFactory::class),
            $expander
        );
    }

    /**
     * @param int[] $categoryIds
     * @param bool $withConditions advanced conditions set (one condition) or empty
     */
    private function label(array $categoryIds, bool $withConditions = true): Label
    {
        $conditions = $this->createStub(Combine::class);
        $conditions->method('getConditions')->willReturn($withConditions ? [$this->createStub(Combine::class)] : []);
        $label = $this->createStub(Label::class);
        $label->method('getCategoryIds')->willReturn($categoryIds);
        $label->method('getConditions')->willReturn($conditions);
        return $label;
    }

    public function testAnchorCategoryAlsoMatchesTheProductsOfItsSubcategories(): void
    {
        $collection = $this->collection();
        $collection->expects(self::once())->method('addCategoriesFilter')->with(['in' => [12, 14, 15]]);

        $this->matcher($collection)->getMatchingProductIds($this->label([12]), 1);
    }

    public function testOnlyEnabledProductsMatch(): void
    {
        $collection = $this->collection();
        $collection->expects(self::once())->method('addAttributeToFilter')->with('status', Status::STATUS_ENABLED);

        $this->matcher($collection)->getMatchingProductIds($this->label([]), 1);
    }

    public function testEnabledProductsAmongSome(): void
    {
        $collection = $this->collection();
        $collection->expects(self::once())->method('setStoreId')->with(2);
        $collection->expects(self::once())->method('addIdFilter')->with([11, 10, 12]);
        $collection->expects(self::once())->method('addAttributeToFilter')->with('status', Status::STATUS_ENABLED);
        $collection->method('getAllIds')->willReturn(['12', '10']);

        self::assertSame([10, 12], $this->matcher($collection)->getEnabledProductIds([11, 10, 12], 2));
        self::assertSame([], $this->matcher($this->collection(false))->getEnabledProductIds([], 2));
    }

    public function testWithoutCategoriesAndConditionsEveryEnabledProductMatches(): void
    {
        $collection = $this->collection(false);
        $collection->method('getAllIds')->willReturn(['20', '10']);
        $iterator = $this->createMock(Iterator::class);
        $iterator->expects(self::never())->method('walk');

        self::assertSame(
            [10, 20],
            $this->matcher($collection, $iterator)->getMatchingProductIds($this->label([], false), 1)
        );
    }
}
