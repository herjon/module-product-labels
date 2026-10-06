<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\LabelResolverInterface;
use Majistar\ProductLabels\Model\Label\ImageInfo;
use Majistar\ProductLabels\Model\ResourceModel\Index;
use Majistar\ProductLabels\Model\View\AppearancePicker;
use Majistar\ProductLabels\Model\View\FactsProvider;
use Majistar\ProductLabels\Model\View\FilterContext;
use Majistar\ProductLabels\Model\View\LabelFilter;
use Majistar\ProductLabels\Model\View\LabelProvider;
use Majistar\ProductLabels\Model\View\LabelSelector;
use Majistar\ProductLabels\Model\View\LabelView;
use Majistar\ProductLabels\Model\View\ProductFacts;
use Majistar\ProductLabels\Model\View\TextRenderer;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Storefront entry point: labels of the products of a page, ready to render.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LabelResolver implements LabelResolverInterface
{
    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param HttpContext $httpContext
     * @param TimezoneInterface $timezone
     * @param DateTime $dateTime
     * @param Index $index
     * @param LabelProvider $labelProvider
     * @param FactsProvider $factsProvider
     * @param LabelFilter $filter
     * @param LabelSelector $selector
     * @param AppearancePicker $appearancePicker
     * @param TextRenderer $textRenderer
     * @param ImageInfo $imageInfo
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly HttpContext $httpContext,
        private readonly TimezoneInterface $timezone,
        private readonly DateTime $dateTime,
        private readonly Index $index,
        private readonly LabelProvider $labelProvider,
        private readonly FactsProvider $factsProvider,
        private readonly LabelFilter $filter,
        private readonly LabelSelector $selector,
        private readonly AppearancePicker $appearancePicker,
        private readonly TextRenderer $textRenderer,
        private readonly ImageInfo $imageInfo
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(array $products, string $area, ?int $storeId = null, ?int $customerGroupId = null): array
    {
        $storeId ??= (int) $this->storeManager->getStore()->getId();
        if (!$products || !$this->config->isEnabled($storeId)) {
            return [];
        }
        $customerGroupId ??= (int) ($this->httpContext->getValue(CustomerContext::CONTEXT_GROUP)
            ?? GroupInterface::NOT_LOGGED_IN_ID);

        $productsById = [];
        foreach ($products as $product) {
            $productsById[(int) $product->getId()] = $product;
        }
        $rows = $this->index->getRows(array_keys($productsById), $storeId);
        $labelIds = array_merge([], ...array_map('array_keys', array_values($rows)));
        $labels = $labelIds ? $this->labelProvider->getActive($labelIds) : [];
        if (!$labels) {
            return [];
        }

        $texts = array_map(
            fn(Label $label): ?string => $this->appearancePicker->pick($label, $area)->getText(),
            $labels
        );
        $facts = $this->factsProvider->collect(
            array_intersect_key($productsById, $rows),
            $storeId,
            $this->textRenderer->getAttributeCodes($texts)
        );
        $context = new FilterContext(
            $this->dateTime->gmtDate(),
            $this->timezone->scopeDate($storeId)->format('Y-m-d'),
            $customerGroupId,
            $area,
            $this->config->getNewSource($storeId),
            $this->config->getNewDays($storeId)
        );

        $result = [];
        foreach ($rows as $productId => $manualByLabel) {
            if (!isset($facts[$productId])) {
                continue;
            }
            $passing = [];
            foreach ($manualByLabel as $labelId => $manual) {
                if (isset($labels[$labelId])
                    && $this->filter->passes($labels[$labelId], $facts[$productId], $context, (bool) $manual)
                ) {
                    $passing[] = $labels[$labelId];
                }
            }
            $selected = $this->selector->select(
                $passing,
                $this->config->getMaxLabels($storeId),
                $this->config->isOutOfStockOnly($storeId),
                !$facts[$productId]->isSalable
            );
            foreach ($selected as $label) {
                $result[$productId][] = $this->createView($label, $area, $facts[$productId], $storeId);
            }
        }
        return $result;
    }

    /**
     * Badge ready to render.
     *
     * @param Label $label
     * @param string $area
     * @param ProductFacts $facts
     * @param int $storeId
     * @return LabelView
     */
    private function createView(Label $label, string $area, ProductFacts $facts, int $storeId): LabelView
    {
        $appearance = $this->appearancePicker->pick($label, $area);
        $isText = $appearance->getType() === AppearanceInterface::TYPE_TEXT;
        $image = $appearance->getImage();
        return new LabelView(
            (int) $label->getLabelId(),
            $label->getPriority(),
            $appearance->getType(),
            $isText ? $this->textRenderer->render((string) $appearance->getText(), $facts, $storeId) : null,
            !$isText && $image !== null ? $this->imageInfo->getUrl($image) : null,
            $appearance->getImageAlt(),
            $appearance->getShape(),
            $appearance->getTextSize(),
            $appearance->getBackgroundColor(),
            $appearance->getTextColor(),
            $appearance->getWidthPercent(),
            $appearance->getPosition(),
            $appearance->getCornerRadius()
        );
    }
}
