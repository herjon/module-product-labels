<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use Majistar\ProductLabels\Model\View\ProductFacts;
use Majistar\ProductLabels\Model\View\TextRenderer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use PHPUnit\Framework\TestCase;

class TextRendererTest extends TestCase
{
    private function renderer(): TextRenderer
    {
        $currency = $this->createStub(PriceCurrencyInterface::class);
        $currency->method('convertAndFormat')->willReturnCallback(
            static fn($amount): string => '€' . number_format((float) $amount, 2)
        );
        return new TextRenderer($currency);
    }

    private function facts(?float $specialPrice = 79.9, ?float $qty = 4.0): ProductFacts
    {
        return new ProductFacts(
            10,
            'MH01',
            79.9,
            99.9,
            $specialPrice,
            true,
            $qty,
            null,
            null,
            null,
            ['color' => 'Red']
        );
    }

    public function testVariables(): void
    {
        $text = '-{SAVE_PERCENT}% | {SAVE_AMOUNT} | {PRICE} | {SPECIAL_PRICE} | {STOCK_QTY} | {SKU} | {ATTR:color}';

        self::assertSame(
            '-20% | €20.00 | €79.90 | €79.90 | 4 | MH01 | Red',
            $this->renderer()->render($text, $this->facts(), 1)
        );
    }

    public function testMissingValuesBecomeEmpty(): void
    {
        self::assertSame(
            '[] [] []',
            $this->renderer()->render('[{SPECIAL_PRICE}] [{STOCK_QTY}] [{ATTR:size}]', $this->facts(null, null), 1)
        );
    }

    public function testPlainTextIsUntouched(): void
    {
        self::assertSame('Sale', $this->renderer()->render('Sale', $this->facts(), 1));
    }

    public function testAttributeCodesUsedByTexts(): void
    {
        self::assertSame(
            ['color', 'size'],
            $this->renderer()->getAttributeCodes(['{ATTR:color} {ATTR:size}', null, 'New {ATTR:color}'])
        );
    }
}
