<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Replaces the variables of a label text with the values of one product.
 */
class TextRenderer
{
    private const ATTRIBUTE_PATTERN = '/\{ATTR:([a-z0-9_]+)\}/i';

    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Attribute codes used by {ATTR:code} in some texts.
     *
     * @param array<int,string|null> $texts
     * @return string[]
     */
    public function getAttributeCodes(array $texts): array
    {
        $codes = [];
        foreach ($texts as $text) {
            if ($text !== null && preg_match_all(self::ATTRIBUTE_PATTERN, $text, $matches)) {
                array_push($codes, ...$matches[1]);
            }
        }
        return array_values(array_unique($codes));
    }

    /**
     * Text with variables replaced; unknown values become empty.
     *
     * @param string $text
     * @param ProductFacts $facts
     * @param int $storeId
     * @return string
     */
    public function render(string $text, ProductFacts $facts, int $storeId): string
    {
        if (!str_contains($text, '{')) {
            return $text;
        }
        $text = strtr($text, [
            '{SAVE_PERCENT}' => (string) (int) round($facts->getDiscountPercent()),
            '{SAVE_AMOUNT}' => $this->price(max(0.0, $facts->regularPrice - $facts->finalPrice), $storeId),
            '{PRICE}' => $this->price($facts->finalPrice, $storeId),
            '{SPECIAL_PRICE}' => $facts->specialPrice === null ? '' : $this->price($facts->specialPrice, $storeId),
            '{STOCK_QTY}' => $facts->qty === null ? '' : (string) (int) $facts->qty,
            '{SKU}' => $facts->sku,
        ]);
        return (string) preg_replace_callback(
            self::ATTRIBUTE_PATTERN,
            static fn(array $match): string => $facts->attributes[$match[1]] ?? '',
            $text
        );
    }

    /**
     * Price in the store currency, without HTML.
     *
     * @param float $amount
     * @param int $storeId
     * @return string
     */
    private function price(float $amount, int $storeId): string
    {
        return (string) $this->priceCurrency->convertAndFormat(
            $amount,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $storeId
        );
    }
}
