<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use Majistar\ProductLabels\Model\Label\Appearance;
use Majistar\ProductLabels\Model\View\AppearancePicker;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use PHPUnit\Framework\TestCase;

class AppearancePickerTest extends TestCase
{
    use CreatesLabels;

    public function testFallbacksPerArea(): void
    {
        $label = $this->createLabel();
        $label->getProductAppearance()->setText('product');
        $picker = new AppearancePicker();

        foreach (['product', 'listing', 'cart', 'minicart', 'checkout'] as $area) {
            self::assertSame('product', $picker->pick($label, $area)->getText(), $area);
        }

        $label->setListingAppearance(new Appearance(['text' => 'listing']));
        self::assertSame('product', $picker->pick($label, 'product')->getText());
        self::assertSame('listing', $picker->pick($label, 'listing')->getText());
        self::assertSame('listing', $picker->pick($label, 'minicart')->getText());

        $label->setCartAppearance(new Appearance(['text' => 'cart']));
        self::assertSame('listing', $picker->pick($label, 'listing')->getText());
        foreach (['cart', 'minicart', 'checkout'] as $area) {
            self::assertSame('cart', $picker->pick($label, $area)->getText(), $area);
        }
    }
}
