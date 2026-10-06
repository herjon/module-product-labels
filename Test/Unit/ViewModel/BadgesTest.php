<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\ViewModel;

use Majistar\ProductLabels\Api\LabelResolverInterface;
use Majistar\ProductLabels\Model\View\LabelView;
use Majistar\ProductLabels\ViewModel\Badges;
use Majistar\ProductLabels\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class BadgesTest extends TestCase
{
    private function view(
        int $id,
        string $position,
        string $type = 'text',
        string $shape = 'rectangle',
        string $size = 'm',
        int $cornerRadius = 4
    ): LabelView {
        return new LabelView(
            $id,
            0,
            $type,
            $type === 'text' ? 'Sale' : null,
            $type === 'image' ? 'https://media/badge.png' : null,
            $type === 'image' ? 'Badge' : null,
            $shape,
            $size,
            '#e11d48',
            '#ffffff',
            30,
            $position,
            $cornerRadius
        );
    }

    private function product(int $id): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    private function badges(
        ?LabelResolverInterface $resolver = null,
        bool $enabled = true,
        bool $swatchesInLists = true,
        ?\Magento\Framework\View\LayoutInterface $layout = null
    ): Badges {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnMap([
            [Badges::XML_PATH_SWATCHES_IN_LISTS, 'store', null, $swatchesInLists],
        ]);
        return new Badges(
            $resolver ?? $this->createStub(LabelResolverInterface::class),
            $layout ?? $this->createStub(\Magento\Framework\View\LayoutInterface::class),
            $config,
            $scopeConfig
        );
    }

    /**
     * Configurable product whose variants are the given ids.
     *
     * @param int $id
     * @param int[] $childIds
     */
    private function configurable(int $id, array $childIds): Product
    {
        $type = $this->createStub(Configurable::class);
        $type->method('getUsedProducts')
            ->willReturn(array_map(fn(int $childId) => $this->product($childId), $childIds));
        $type->method('getConfigurableAttributes')->willReturn(['color', 'size']);
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $product->method('getTypeInstance')->willReturn($type);
        return $product;
    }

    public function testPrefetchResolvesAPageOnceAndCaches(): void
    {
        $resolver = $this->createMock(LabelResolverInterface::class);
        $resolver->expects(self::once())->method('resolve')
            ->willReturn([10 => [$this->view(1, 'top-left')]]);
        $badges = $this->badges($resolver);

        $badges->prefetch([$this->product(10), $this->product(11)], 'listing');

        self::assertCount(1, $badges->getBadges($this->product(10), 'listing'));
        self::assertSame([], $badges->getBadges($this->product(11), 'listing'));
    }

    public function testGroupsByPositionKeepingOrder(): void
    {
        $badges = $this->badges();
        $groups = $badges->groupByPosition([
            $this->view(1, 'top-left'),
            $this->view(2, 'bottom-right'),
            $this->view(3, 'top-left'),
        ]);

        self::assertSame(['top-left', 'bottom-right'], array_keys($groups));
        self::assertSame([1, 3], array_map(static fn($view): int => $view->getLabelId(), $groups['top-left']));
    }

    public function testContainerClassesPerPosition(): void
    {
        $badges = $this->badges();

        self::assertStringContainsString('top-2', $badges->getContainerClasses('top-left'));
        self::assertStringContainsString('items-start', $badges->getContainerClasses('top-left'));
        self::assertStringContainsString('-translate-y-1/2', $badges->getContainerClasses('center'));
        self::assertStringContainsString('items-center', $badges->getContainerClasses('center'));
        self::assertStringContainsString('bottom-2', $badges->getContainerClasses('bottom-right'));
        self::assertStringContainsString('items-end', $badges->getContainerClasses('bottom-right'));
    }

    public function testTextClassesAndStyle(): void
    {
        $badges = $this->badges();

        $smallPill = $badges->getTextClasses($this->view(1, 'top-left', 'text', 'pill', 's'));
        self::assertStringContainsString('rounded-full', $smallPill);
        self::assertStringContainsString('text-xs', $smallPill);
        $largeCircle = $badges->getTextClasses($this->view(1, 'top-left', 'text', 'circle', 'l'));
        self::assertStringContainsString('size-16', $largeCircle);
        self::assertSame(
            'background-color: #e11d48; color: #ffffff; border-radius: 4px',
            $badges->getStyle($this->view(1, 'top-left'))
        );
        self::assertSame(
            'width: 30%; border-radius: 4px',
            $badges->getStyle($this->view(1, 'top-left', 'image'))
        );
    }

    public function testGroupsForAlpine(): void
    {
        $badges = $this->badges();
        $groups = $badges->toGroups([$this->view(1, 'top-right'), $this->view(2, 'top-right', 'image')]);

        self::assertCount(1, $groups);
        self::assertSame('top-right', $groups[0]['position']);
        self::assertStringContainsString('items-end', $groups[0]['class']);
        self::assertSame('Sale', $groups[0]['badges'][0]['text']);
        self::assertSame('https://media/badge.png', $groups[0]['badges'][1]['image']);
        self::assertSame('width: 30%; border-radius: 4px', $groups[0]['badges'][1]['style']);
        // Alpine's CSP build has no "!" or "===" in attributes: the template reads ready-made flags
        self::assertTrue($groups[0]['badges'][0]['is_text']);
        self::assertFalse($groups[0]['badges'][0]['is_image']);
        self::assertTrue($groups[0]['badges'][1]['is_image']);
        self::assertFalse($groups[0]['badges'][1]['is_text']);
    }

    public function testImageBadgeWithoutFileIsShownAsText(): void
    {
        $view = new LabelView(1, 0, 'image', 'Sale', null, null, 'rectangle', 'm', null, null, 30, 'top-left');

        $badge = $this->badges()->toGroups([$view])[0]['badges'][0];

        self::assertFalse($badge['is_image']);
        self::assertTrue($badge['is_text']);
    }

    public function testSmallThumbnailsOfCartAreasGetTighterOffsets(): void
    {
        $badges = $this->badges();

        foreach (['cart', 'minicart', 'checkout'] as $area) {
            $classes = $badges->getContainerClasses('top-left', $area);
            self::assertStringContainsString('inset-x-1', $classes, $area);
            self::assertStringContainsString('top-1', $classes, $area);
            self::assertStringNotContainsString('top-2', $classes, $area);
        }
        self::assertStringContainsString('bottom-1', $badges->getContainerClasses('bottom-left', 'minicart'));
        self::assertStringContainsString('inset-x-2', $badges->getContainerClasses('top-left', 'listing'));
        self::assertStringContainsString(
            'inset-x-1',
            $badges->toGroups([$this->view(1, 'top-left')], 'minicart')[0]['class']
        );
    }

    public function testNoChildrenForSimpleProducts(): void
    {
        $badges = $this->badges();
        $product = $this->product(10);

        self::assertSame([], $badges->getChildrenGroups($product, 'product'));
    }

    public function testRenderBadgesUsesTheBadgeTemplateWithAFreshBlock(): void
    {
        $product = $this->product(10);
        $block = $this->createMock(\Magento\Framework\View\Element\Template::class);
        $block->expects(self::once())->method('setTemplate')
            ->with('Majistar_ProductLabels::badges.phtml')->willReturnSelf();
        // "area" is a Template block's design area: the badge area travels as "badge_area"
        $block->expects(self::once())->method('addData')->with(self::callback(
            static fn(array $data): bool => $data['product'] === $product && $data['badge_area'] === 'listing'
                && !array_key_exists('area', $data) && $data['badges_view_model'] instanceof Badges
        ))->willReturnSelf();
        $block->method('toHtml')->willReturn('<span>Sale</span>');
        $layout = $this->createStub(\Magento\Framework\View\LayoutInterface::class);
        $layout->method('createBlock')->willReturn($block);

        $badges = $this->badges(null, true, true, $layout);

        self::assertSame('<span>Sale</span>', $badges->renderBadges($product, 'listing'));
    }

    public function testDisabledModuleRendersNothing(): void
    {
        $layout = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $layout->expects(self::never())->method('createBlock');
        $badges = $this->badges(null, false, true, $layout);

        self::assertSame('', $badges->renderBadges($this->product(10), 'product'));
        self::assertSame([], $badges->getChildrenGroups($this->configurable(62, [47, 48]), 'product'));
    }

    public function testOnlyVariantsWithBadgesAreSent(): void
    {
        $resolver = $this->createStub(LabelResolverInterface::class);
        $resolver->method('resolve')->willReturn([47 => [$this->view(1, 'bottom-center')]]);

        $variants = $this->badges($resolver)->getChildrenGroups($this->configurable(62, [47, 48]), 'product');

        self::assertSame([47], array_keys($variants));
    }

    public function testListsSwitchVariantsOnlyWhenTheyShowSwatches(): void
    {
        $product = $this->configurable(62, [47]);

        self::assertTrue($this->badges(null, true, false)->hasVariantSwitching($product, 'product'));
        self::assertFalse($this->badges(null, true, false)->hasVariantSwitching($product, 'listing'));
        self::assertTrue($this->badges(null, true, true)->hasVariantSwitching($product, 'listing'));
        self::assertFalse($this->badges()->hasVariantSwitching($product, 'cart'));
        self::assertFalse($this->badges()->hasVariantSwitching($this->product(10), 'product'));
        self::assertSame(2, $this->badges()->getVariantAttributeCount($product));
    }

    public function testVariantsOfAWholeListAreResolvedTogether(): void
    {
        $resolver = $this->createMock(LabelResolverInterface::class);
        $resolver->expects(self::once())->method('resolve')
            ->with(self::callback(static fn(array $products): bool => count($products) === 4))
            ->willReturn([]);
        $badges = $this->badges($resolver);
        $list = [$this->configurable(62, [47, 48]), $this->product(10), $this->configurable(70, [71, 72])];

        $badges->prefetchVariants($list, 'listing');
        $badges->getChildrenGroups($list[0], 'listing');
        $badges->getChildrenGroups($list[2], 'listing');
    }

    public function testBadgesWithNeitherImageNorTextAreSkipped(): void
    {
        $empty = new LabelView(2, 0, 'image', null, null, null, 'rectangle', 'm', '#e11d48', null, 30, 'top-left');
        $resolver = $this->createStub(LabelResolverInterface::class);
        $resolver->method('resolve')->willReturn([10 => [$this->view(1, 'top-left'), $empty]]);

        $views = $this->badges($resolver)->getBadges($this->product(10), 'product');

        self::assertSame([1], array_map(static fn($view): int => $view->getLabelId(), $views));
    }

    public function testCornerRadiusOfRectanglesAndImages(): void
    {
        $badges = $this->badges();

        self::assertStringContainsString(
            'border-radius: 12px',
            $badges->getStyle($this->view(1, 'top-left', 'text', 'rectangle', 'm', 12))
        );
        self::assertStringNotContainsString('rounded', $badges->getTextClasses($this->view(1, 'top-left')));
        self::assertSame('width: 30%', $badges->getStyle($this->view(1, 'top-left', 'image', 'rectangle', 'm', 0)));
        $pill = $this->view(1, 'top-left', 'text', 'pill', 'm', 12);
        self::assertStringNotContainsString('border-radius', $badges->getStyle($pill));
        self::assertStringContainsString('rounded-full', $badges->getTextClasses($pill));
    }
}
