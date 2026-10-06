<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

/**
 * What the live checks and the text variables need to know about one product.
 */
class ProductFacts
{
    /**
     * @param int $productId
     * @param string $sku
     * @param float $finalPrice price the customer pays (configurable: lowest child)
     * @param float $regularPrice price before discounts (configurable: lowest child)
     * @param float|null $specialPrice
     * @param bool $isSalable
     * @param float|null $qty salable quantity, null when unknown
     * @param string|null $newsFromDate "Set Product as New From", Y-m-d
     * @param string|null $newsToDate "Set Product as New To", Y-m-d
     * @param string|null $createdAt UTC, Y-m-d H:i:s
     * @param array<string,string> $attributes values for {ATTR:code}
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        public readonly int $productId,
        public readonly string $sku,
        public readonly float $finalPrice,
        public readonly float $regularPrice,
        public readonly ?float $specialPrice,
        public readonly bool $isSalable,
        public readonly ?float $qty,
        public readonly ?string $newsFromDate,
        public readonly ?string $newsToDate,
        public readonly ?string $createdAt,
        public readonly array $attributes = []
    ) {
    }

    /**
     * Discount in percent (0 when there is none).
     *
     * @return float
     */
    public function getDiscountPercent(): float
    {
        if ($this->regularPrice <= 0 || $this->finalPrice >= $this->regularPrice) {
            return 0.0;
        }
        return ($this->regularPrice - $this->finalPrice) / $this->regularPrice * 100;
    }
}
