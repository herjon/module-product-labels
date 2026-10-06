<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\ViewModel;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Api\Data\LabelViewInterface;
use Majistar\ProductLabels\Api\LabelResolverInterface;
use Majistar\ProductLabels\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Badges for Hyvä templates: labels per product and area, with their Tailwind classes and styles.
 */
class Badges implements ArgumentInterface
{
    private const VERTICAL = [
        'top' => 'top-2',
        'middle' => 'top-1/2 -translate-y-1/2',
        'bottom' => 'bottom-2',
    ];
    private const VERTICAL_COMPACT = [
        'top' => 'top-1',
        'middle' => 'top-1/2 -translate-y-1/2',
        'bottom' => 'bottom-1',
    ];
    /**
     * Areas with small thumbnails: badges sit closer to the edges.
     */
    private const COMPACT_AREAS = [
        LabelInterface::SHOW_ON_CART,
        LabelInterface::SHOW_ON_MINICART,
        LabelInterface::SHOW_ON_CHECKOUT,
    ];
    private const HORIZONTAL = [
        'left' => 'items-start',
        'center' => 'items-center',
        'right' => 'items-end',
    ];
    private const TEXT_SIZES = [
        AppearanceInterface::TEXT_SIZE_SMALL => 'text-xs px-2 py-0.5',
        AppearanceInterface::TEXT_SIZE_MEDIUM => 'text-sm px-2.5 py-1',
        AppearanceInterface::TEXT_SIZE_LARGE => 'text-base px-3 py-1.5',
    ];
    private const CIRCLE_SIZES = [
        AppearanceInterface::TEXT_SIZE_SMALL => 'size-10 text-xs',
        AppearanceInterface::TEXT_SIZE_MEDIUM => 'size-12 text-sm',
        AppearanceInterface::TEXT_SIZE_LARGE => 'size-16 text-base',
    ];

    /**
     * @var array<string, array<int, LabelViewInterface[]>> area => product id => badges
     */
    private array $resolved = [];

    public const TEMPLATE = 'Majistar_ProductLabels::badges.phtml';

    /**
     * Without swatches in lists no listing-configurable-selection-changed event is ever sent.
     */
    public const XML_PATH_SWATCHES_IN_LISTS = 'catalog/frontend/show_swatches_in_product_list';

    /**
     * @param LabelResolverInterface $resolver
     * @param LayoutInterface $layout
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly LabelResolverInterface $resolver,
        private readonly LayoutInterface $layout,
        private readonly Config $config,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * HTML of the badges of a product, to place inside a "relative" wrapper of the product image.
     *
     * @param ProductInterface $product
     * @param string $area one of LabelInterface::SHOW_ON_*
     * @return string
     */
    public function renderBadges(ProductInterface $product, string $area): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }
        // A fresh block each time, so no product data is left over for the next call.
        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class);
        // "badge_area", not "area": that is the block's design area.
        return $block->setTemplate(self::TEMPLATE)
            ->addData(['badges_view_model' => $this, 'product' => $product, 'badge_area' => $area])
            ->toHtml();
    }

    /**
     * Resolves many products at once (a list calls this before rendering its cards).
     *
     * @param ProductInterface[] $products
     * @param string $area
     * @return void
     */
    public function prefetch(array $products, string $area): void
    {
        $missing = [];
        foreach ($products as $product) {
            $productId = (int) $product->getId();
            if ($productId && !isset($this->resolved[$area][$productId])) {
                $missing[$productId] = $product;
            }
        }
        if (!$missing) {
            return;
        }
        $views = $this->resolver->resolve(array_values($missing), $area);
        foreach (array_keys($missing) as $productId) {
            $this->resolved[$area][$productId] = $views[$productId] ?? [];
        }
    }

    /**
     * Badges of one product, highest priority first.
     *
     * @param ProductInterface $product
     * @param string $area
     * @return LabelViewInterface[]
     */
    public function getBadges(ProductInterface $product, string $area): array
    {
        $this->prefetch([$product], $area);
        return array_values(array_filter(
            $this->resolved[$area][(int) $product->getId()] ?? [],
            fn(LabelViewInterface $view): bool => $this->isImage($view) || trim((string) $view->getText()) !== ''
        ));
    }

    /**
     * Badges by position; several badges in the same position are stacked.
     *
     * @param LabelViewInterface[] $views
     * @return array<string,LabelViewInterface[]>
     */
    public function groupByPosition(array $views): array
    {
        $groups = [];
        foreach ($views as $view) {
            $groups[$view->getPosition()][] = $view;
        }
        return $groups;
    }

    /**
     * Classes of the container of one position, inside a "relative" wrapper of the product image.
     *
     * @param string $position
     * @param string $area tighter offsets for the small thumbnails of the cart areas
     * @return string
     */
    public function getContainerClasses(string $position, string $area = ''): string
    {
        [$vertical, $horizontal] = $this->splitPosition($position);
        $compact = in_array($area, self::COMPACT_AREAS, true);
        $verticalClasses = $compact ? self::VERTICAL_COMPACT : self::VERTICAL;
        return implode(' ', [
            'absolute z-10 flex flex-col gap-1 pointer-events-none',
            $compact ? 'inset-x-1' : 'inset-x-2',
            $verticalClasses[$vertical] ?? $verticalClasses['top'],
            self::HORIZONTAL[$horizontal] ?? self::HORIZONTAL['left'],
        ]);
    }

    /**
     * Whether a badge is drawn as an image (an image badge whose file is missing falls back to its text).
     *
     * @param LabelViewInterface $view
     * @return bool
     */
    public function isImage(LabelViewInterface $view): bool
    {
        return $view->getType() === AppearanceInterface::TYPE_IMAGE && (string) $view->getImageUrl() !== '';
    }

    /**
     * Classes of a text badge: shape and size.
     *
     * @param LabelViewInterface $view
     * @return string
     */
    public function getTextClasses(LabelViewInterface $view): string
    {
        $size = $view->getTextSize();
        if ($view->getShape() === AppearanceInterface::SHAPE_CIRCLE) {
            return 'inline-flex items-center justify-center rounded-full text-center font-semibold leading-tight '
                . (self::CIRCLE_SIZES[$size] ?? self::CIRCLE_SIZES[AppearanceInterface::TEXT_SIZE_MEDIUM]);
        }
        // a rectangle gets its corner radius from getStyle()
        $shape = $view->getShape() === AppearanceInterface::SHAPE_PILL ? 'rounded-full ' : '';
        return 'inline-block font-semibold leading-tight ' . $shape
            . (self::TEXT_SIZES[$size] ?? self::TEXT_SIZES[AppearanceInterface::TEXT_SIZE_MEDIUM]);
    }

    /**
     * Inline style: text badge colors, image badge width, corner radius.
     *
     * @param LabelViewInterface $view
     * @return string
     */
    public function getStyle(LabelViewInterface $view): string
    {
        $radius = $view->getCornerRadius() > 0 ? sprintf('border-radius: %dpx', $view->getCornerRadius()) : '';
        if ($this->isImage($view)) {
            return implode('; ', array_filter([sprintf('width: %d%%', $view->getWidthPercent()), $radius]));
        }
        $style = [];
        if ($view->getBackgroundColor()) {
            $style[] = 'background-color: ' . $view->getBackgroundColor();
        }
        if ($view->getTextColor()) {
            $style[] = 'color: ' . $view->getTextColor();
        }
        if ($radius !== '' && $view->getShape() === AppearanceInterface::SHAPE_RECTANGLE) {
            $style[] = $radius;
        }
        return implode('; ', $style);
    }

    /**
     * Badges as plain arrays grouped by position (for Alpine and JSON).
     *
     * @param LabelViewInterface[] $views
     * @param string $area
     * @return array<int,array{position:string,class:string,badges:array<int,array<string,mixed>>}>
     */
    public function toGroups(array $views, string $area = ''): array
    {
        $groups = [];
        foreach ($this->groupByPosition($views) as $position => $positionViews) {
            $groups[] = [
                'position' => $position,
                'class' => $this->getContainerClasses($position, $area),
                'badges' => array_map(function (LabelViewInterface $view): array {
                    $isImage = $this->isImage($view);
                    return [
                        'id' => $view->getLabelId(),
                        'type' => $view->getType(),
                        // Alpine's CSP build has no "!" or "===" in attributes: ready-made flags.
                        'is_image' => $isImage,
                        'is_text' => !$isImage,
                        'text' => (string) $view->getText(),
                        'image' => $view->getImageUrl(),
                        'alt' => (string) $view->getImageAlt(),
                        'class' => $isImage ? '' : $this->getTextClasses($view),
                        'style' => $this->getStyle($view),
                    ];
                }, $positionViews),
            ];
        }
        return $groups;
    }

    /**
     * Whether a configurable's badges follow the chosen variant (product page, lists with swatches).
     *
     * @param ProductInterface $product
     * @param string $area
     * @return bool
     */
    public function hasVariantSwitching(ProductInterface $product, string $area): bool
    {
        if (!$product instanceof Product || $product->getTypeId() !== Configurable::TYPE_CODE) {
            return false;
        }
        if ($area === LabelInterface::SHOW_ON_LISTING) {
            return $this->scopeConfig->isSetFlag(self::XML_PATH_SWATCHES_IN_LISTS, ScopeInterface::SCOPE_STORE);
        }
        return $area === LabelInterface::SHOW_ON_PRODUCT;
    }

    /**
     * Number of configurable attributes: a selection is complete when each one has a value.
     *
     * @param ProductInterface $product
     * @return int
     */
    public function getVariantAttributeCount(ProductInterface $product): int
    {
        if (!$product instanceof Product || $product->getTypeId() !== Configurable::TYPE_CODE) {
            return 0;
        }
        return count($product->getTypeInstance()->getConfigurableAttributes($product));
    }

    /**
     * Resolves the variants of all configurable products of a list at once.
     *
     * @param ProductInterface[] $products
     * @param string $area
     * @return void
     */
    public function prefetchVariants(array $products, string $area): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        $variants = [];
        foreach ($products as $product) {
            if ($this->hasVariantSwitching($product, $area)) {
                $variants[] = $product->getTypeInstance()->getUsedProducts($product);
            }
        }
        $this->prefetch(array_merge([], ...$variants), $area);
    }

    /**
     * Badge groups of the variants that have badges; a variant left out shows no badge.
     *
     * @param ProductInterface $product
     * @param string $area
     * @return array<int,array> child id => groups (see toGroups)
     */
    public function getChildrenGroups(ProductInterface $product, string $area): array
    {
        if (!$this->config->isEnabled() || !$this->hasVariantSwitching($product, $area)) {
            return [];
        }
        /** @var Product $product */
        $children = $product->getTypeInstance()->getUsedProducts($product);
        $this->prefetch($children, $area);
        $result = [];
        foreach ($children as $child) {
            $badges = $this->getBadges($child, $area);
            if ($badges) {
                $result[(int) $child->getId()] = $this->toGroups($badges, $area);
            }
        }
        return $result;
    }

    /**
     * Vertical and horizontal part of a position ("center" is middle-center).
     *
     * @param string $position
     * @return string[]
     */
    private function splitPosition(string $position): array
    {
        if ($position === AppearanceInterface::POSITION_CENTER) {
            return ['middle', 'center'];
        }
        $parts = explode('-', $position, 2);
        return [$parts[0], $parts[1] ?? 'left'];
    }
}
