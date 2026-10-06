<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\Label\Appearance;
use Majistar\ProductLabels\Model\Label\Validator;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    use CreatesLabels;

    private function validLabel(): Label
    {
        $label = $this->createLabel();
        $label->setName('Sale')->setShowOn(['product'])->setStoreIds([0])->setCustomerGroupIds([0]);
        $label->getProductAppearance()->setType('text')->setText('Sale');
        return $label;
    }

    /**
     * @return string[]
     */
    private function messages(Label $label): array
    {
        return array_map('strval', (new Validator())->validate($label));
    }

    private function assertHasError(Label $label, string $fragment): void
    {
        $messages = $this->messages($label);
        foreach ($messages as $message) {
            if (str_contains($message, $fragment)) {
                $this->addToAssertionCount(1);
                return;
            }
        }
        self::fail(sprintf('No error containing "%s" in: %s', $fragment, implode(' | ', $messages)));
    }

    public function testValidTextLabelHasNoErrors(): void
    {
        self::assertSame([], $this->messages($this->validLabel()));
    }

    public function testRequiredGeneralFields(): void
    {
        $label = $this->validLabel();
        $label->setName('  ')->setShowOn([])->setStoreIds([])->setCustomerGroupIds([]);

        $this->assertHasError($label, 'Name is required');
        $this->assertHasError($label, 'Choose where the label is shown');
        $this->assertHasError($label, 'at least one store view');
        $this->assertHasError($label, 'at least one customer group');
    }

    public function testUnknownShowOnValue(): void
    {
        $this->assertHasError($this->validLabel()->setShowOn(['product', 'wishlist']), '"Show on"');
    }

    public function testTextLabelNeedsText(): void
    {
        $label = $this->validLabel();
        $label->getProductAppearance()->setText(' ');

        $this->assertHasError($label, 'enter the label text');
    }

    public function testImageLabelNeedsImageButNotText(): void
    {
        $label = $this->validLabel();
        $label->getProductAppearance()->setType('image')->setText(null);
        $this->assertHasError($label, 'upload an image');

        $label->getProductAppearance()->setImage('sale_1.webp');
        self::assertSame([], $this->messages($label));
    }

    public function testImageSentAsContentNeedsNoFileName(): void
    {
        $label = $this->validLabel();
        $label->getProductAppearance()->setType('image')->setText(null)->setImageContent(
            new \Magento\Framework\Api\ImageContent(['base64_encoded_data' => 'iVBORw0KGgo=', 'name' => 'a.png'])
        );

        self::assertSame([], $this->messages($label));
    }

    public function testImageFileNameMustBeASafeImageName(): void
    {
        foreach (['x.svg', '../a.png', 'shell.php', '.hidden.png'] as $name) {
            $label = $this->validLabel();
            $label->getProductAppearance()->setType('image')->setImage($name);
            $this->assertHasError($label, 'JPG, PNG, GIF, WebP or AVIF');
        }
    }

    public function testColorsMustBeHex(): void
    {
        $label = $this->validLabel();
        $label->getProductAppearance()->setBackgroundColor('#12345')->setTextColor('red');

        $this->assertHasError($label, 'background color must be a hex color');
        $this->assertHasError($label, 'text color must be a hex color');
    }

    public function testEnumsAndWidth(): void
    {
        $label = $this->validLabel();
        $label->getProductAppearance()->setShape('star')->setTextSize('xl')->setPosition('top')->setWidthPercent(3)
            ->setCornerRadius(51);

        $this->assertHasError($label, 'shape is not valid');
        $this->assertHasError($label, 'text size is not valid');
        $this->assertHasError($label, 'position is not valid');
        $this->assertHasError($label, 'width must be between 5 and 100');
        $this->assertHasError($label, 'corner radius must be between 0 and 50');
    }

    public function testListingAppearanceIsValidatedWhenPresent(): void
    {
        $label = $this->validLabel();
        $label->setListingAppearance(new Appearance(['type' => 'image']));

        $this->assertHasError($label, 'Product listings: upload an image');
    }

    public function testDates(): void
    {
        $label = $this->validLabel();
        $label->setActiveFrom('2026-13-01 00:00:00');
        $this->assertHasError($label, '"Active from" is not a valid date');

        $label->setActiveFrom('2026-10-05 00:00:00')->setActiveTo('2026-10-04 00:00:00');
        $this->assertHasError($label, '"Active to" must be later');

        $label->setActiveTo('2026-10-06 00:00:00');
        self::assertSame([], $this->messages($label));
    }

    public function testQuickFilters(): void
    {
        $label = $this->validLabel();
        $label->setMinDiscountPercent(120)->setStockStatus('low_stock')->setPriceFrom(50.0)->setPriceTo(10.0);

        $this->assertHasError($label, 'Minimum discount must be between 0 and 100');
        $this->assertHasError($label, 'quantity below which stock is low');
        $this->assertHasError($label, '"Price to" must be greater');

        $this->assertHasError($this->validLabel()->setStockStatus('maybe'), 'Stock filter "maybe" is not valid');
    }

    public function testConditionsMustBeJson(): void
    {
        $this->assertHasError($this->validLabel()->setConditionsSerialized('not json'), 'not valid JSON');
    }

    public function testCartAreasAreValidShowOnValues(): void
    {
        self::assertSame([], $this->messages($this->validLabel()->setShowOn(['cart', 'minicart', 'checkout'])));
    }

    public function testCartAppearanceIsValidatedWhenPresent(): void
    {
        $label = $this->validLabel();
        $label->setCartAppearance(new Appearance(['type' => 'image']));

        $this->assertHasError($label, 'Cart: upload an image');
    }
}
