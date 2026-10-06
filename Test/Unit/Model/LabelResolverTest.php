<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model;

use DateTime as PhpDateTime;
use Majistar\ProductLabels\Api\Data\LabelViewInterface;
use Majistar\ProductLabels\Model\Config;
use Majistar\ProductLabels\Model\Label\ImageInfo;
use Majistar\ProductLabels\Model\LabelResolver;
use Majistar\ProductLabels\Model\ResourceModel\Index;
use Majistar\ProductLabels\Model\View\AppearancePicker;
use Majistar\ProductLabels\Model\View\FactsProvider;
use Majistar\ProductLabels\Model\View\LabelFilter;
use Majistar\ProductLabels\Model\View\LabelProvider;
use Majistar\ProductLabels\Model\View\LabelSelector;
use Majistar\ProductLabels\Model\View\ProductFacts;
use Majistar\ProductLabels\Model\View\TextRenderer;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class LabelResolverTest extends TestCase
{
    use CreatesLabels;

    private function resolver(bool $enabled = true): LabelResolver
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getMaxLabels')->willReturn(2);
        $config->method('isOutOfStockOnly')->willReturn(true);
        $config->method('getNewSource')->willReturn(Config::NEW_SOURCE_NEWS_DATES);
        $config->method('getNewDays')->willReturn(30);
        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('scopeDate')->willReturn(new PhpDateTime('2026-10-05'));
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtDate')->willReturn('2026-10-05 10:00:00');

        // Product 10: label 1 by rule, label 2 by hand, label 3 inactive.
        $index = $this->createStub(Index::class);
        $index->method('getRows')->willReturn([10 => [1 => 0, 2 => 1, 3 => 0]]);

        $sale = $this->createLabel([
            'label_id' => 1, 'priority' => 2, 'is_active' => 1, 'is_on_sale' => 1,
            'customer_group_ids' => [0], 'show_on' => 'product,listing',
        ]);
        $sale->getProductAppearance()->setType('text')->setText('-{SAVE_PERCENT}%')->setShape('pill');
        $badge = $this->createLabel([
            'label_id' => 2, 'priority' => 1, 'is_active' => 1, 'customer_group_ids' => [0], 'show_on' => 'listing',
        ]);
        $badge->getProductAppearance()->setType('image')->setImage('badge.png')->setWidthPercent(30)
            ->setCornerRadius(6);
        $labels = $this->createStub(LabelProvider::class);
        $labels->method('getActive')->willReturn([1 => $sale, 2 => $badge]);

        $facts = $this->createStub(FactsProvider::class);
        $facts->method('collect')->willReturn([
            10 => new ProductFacts(10, 'MH01', 80.0, 100.0, 80.0, true, 5.0, null, null, null),
        ]);
        $currency = $this->createStub(PriceCurrencyInterface::class);
        $imageInfo = $this->createStub(ImageInfo::class);
        $imageInfo->method('getUrl')->willReturnCallback(static fn(string $file): string => 'https://media/' . $file);

        return new LabelResolver(
            $config,
            $this->createStub(StoreManagerInterface::class),
            $this->createStub(HttpContext::class),
            $timezone,
            $dateTime,
            $index,
            $labels,
            $facts,
            new LabelFilter(),
            new LabelSelector(),
            new AppearancePicker(),
            new TextRenderer($currency),
            $imageInfo
        );
    }

    private function product(int $id): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    public function testBadgesOfAListingInPriorityOrder(): void
    {
        $result = $this->resolver()->resolve([$this->product(10), $this->product(11)], 'listing', 1, 0);

        self::assertSame([10], array_keys($result));
        [$first, $second] = $result[10];
        self::assertInstanceOf(LabelViewInterface::class, $first);
        self::assertSame(2, $first->getLabelId());
        self::assertSame('https://media/badge.png', $first->getImageUrl());
        self::assertNull($first->getText());
        self::assertSame(30, $first->getWidthPercent());
        self::assertSame(6, $first->getCornerRadius());
        self::assertSame(1, $second->getLabelId());
        self::assertSame('-20%', $second->getText());
        self::assertSame('pill', $second->getShape());
    }

    public function testAreaAndGroupFilterTheLabels(): void
    {
        $result = $this->resolver()->resolve([$this->product(10)], 'product', 1, 0);
        self::assertSame([1], array_map(static fn(LabelViewInterface $v): int => $v->getLabelId(), $result[10]));

        self::assertSame([], $this->resolver()->resolve([$this->product(10)], 'listing', 1, 3));
    }

    public function testNothingWhenDisabledOrNoProducts(): void
    {
        self::assertSame([], $this->resolver(false)->resolve([$this->product(10)], 'listing', 1, 0));
        self::assertSame([], $this->resolver()->resolve([], 'listing', 1, 0));
    }
}
