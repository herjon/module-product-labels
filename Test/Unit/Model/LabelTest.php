<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\Label\Appearance;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    use CreatesLabels;

    public function testNewLabelDefaults(): void
    {
        $label = $this->createLabel();

        self::assertNull($label->getLabelId());
        self::assertTrue($label->getIsActive());
        self::assertSame([LabelInterface::SHOW_ON_PRODUCT, LabelInterface::SHOW_ON_LISTING], $label->getShowOn());
        self::assertSame(LabelInterface::STOCK_ANY, $label->getStockStatus());
        self::assertSame([], $label->getStoreIds());
        self::assertNull($label->getConditionsSerialized());
        self::assertSame(AppearanceInterface::TYPE_TEXT, $label->getProductAppearance()->getType());
        self::assertNull($label->getListingAppearance());
    }

    public function testListsAreNormalisedFromDatabaseStrings(): void
    {
        $label = $this->createLabel([
            'show_on' => 'listing',
            'category_ids' => '3,4,,4',
            'store_ids' => ['0', '1', 'x'],
        ]);

        self::assertSame(['listing'], $label->getShowOn());
        self::assertSame([3, 4], $label->getCategoryIds());
        self::assertSame([0, 1], $label->getStoreIds());
    }

    public function testEmptyShowOnStaysEmpty(): void
    {
        self::assertSame([], $this->createLabel(['show_on' => ''])->getShowOn());
        self::assertSame([], $this->createLabel(['show_on' => []])->getShowOn());
    }

    public function testNullableNumbers(): void
    {
        $label = $this->createLabel([
            'label_id' => '7',
            'min_discount_percent' => '',
            'price_from' => '9.90',
            'low_stock_qty' => null,
        ]);

        self::assertSame(7, $label->getLabelId());
        self::assertNull($label->getMinDiscountPercent());
        self::assertSame(9.9, $label->getPriceFrom());
        self::assertNull($label->getLowStockQty());
    }

    public function testSettersRoundTrip(): void
    {
        $label = $this->createLabel();
        $label->setName('Sale')
            ->setIsActive(false)
            ->setPriority(3)
            ->setStopProcessing(true)
            ->setCustomerGroupIds([1, 1, 2])
            ->setActiveFrom('2026-10-04 10:00:00')
            ->setConditionsSerialized('{"type":"x"}');

        self::assertSame('Sale', $label->getName());
        self::assertFalse($label->getIsActive());
        self::assertSame(3, $label->getPriority());
        self::assertTrue($label->getStopProcessing());
        self::assertSame([1, 2], $label->getCustomerGroupIds());
        self::assertSame('2026-10-04 10:00:00', $label->getActiveFrom());
        self::assertSame('{"type":"x"}', $label->getConditionsSerialized());
    }

    public function testListingAppearanceCanBeCleared(): void
    {
        $label = $this->createLabel();
        $label->setListingAppearance(new Appearance(['type' => 'image']));
        self::assertSame('image', $label->getListingAppearance()?->getType());

        $label->setListingAppearance(null);
        self::assertNull($label->getListingAppearance());
    }

    public function testAppearanceDefaults(): void
    {
        $appearance = new Appearance(['width_percent' => '', 'text' => '', 'corner_radius' => '']);

        self::assertSame(AppearanceInterface::SHAPE_RECTANGLE, $appearance->getShape());
        self::assertSame(AppearanceInterface::TEXT_SIZE_MEDIUM, $appearance->getTextSize());
        self::assertSame(AppearanceInterface::POSITION_TOP_LEFT, $appearance->getPosition());
        self::assertSame(AppearanceInterface::DEFAULT_WIDTH_PERCENT, $appearance->getWidthPercent());
        self::assertSame(AppearanceInterface::DEFAULT_CORNER_RADIUS, $appearance->getCornerRadius());
        self::assertNull($appearance->getText());
    }

    public function testConditionsFieldSetIdUsesFormNameAndId(): void
    {
        self::assertSame(
            'majistar_product_label_formrule_conditions_fieldset_7',
            $this->createLabel(['label_id' => 7])->getConditionsFieldSetId('majistar_product_label_form')
        );
    }

    public function testTimestampsHaveSettersForWebapiRoundTrips(): void
    {
        // Without setters ServiceInputProcessor rejects a PUT carrying created_at/updated_at as GET returns them.
        $contract = new \ReflectionClass(LabelInterface::class);
        self::assertTrue($contract->hasMethod('setCreatedAt'));
        self::assertTrue($contract->hasMethod('setUpdatedAt'));

        $label = $this->createLabel();
        $label->setCreatedAt('2026-10-01 08:00:00')->setUpdatedAt('2026-10-02 09:30:00');

        self::assertSame('2026-10-01 08:00:00', $label->getCreatedAt());
        self::assertSame('2026-10-02 09:30:00', $label->getUpdatedAt());
    }

    public function testIdentities(): void
    {
        self::assertSame(
            ['majistar_product_label', 'majistar_product_label_7'],
            $this->createLabel(['label_id' => 7])->getIdentities()
        );
    }

    public function testCartAppearanceIsOptional(): void
    {
        $label = $this->createLabel(['show_on' => 'product,cart,minicart,checkout']);
        self::assertNull($label->getCartAppearance());
        self::assertSame(['product', 'cart', 'minicart', 'checkout'], $label->getShowOn());

        $label->setCartAppearance(new Appearance(['type' => 'text', 'text' => '-20%']));
        self::assertSame('-20%', $label->getCartAppearance()?->getText());

        $label->setCartAppearance(null);
        self::assertNull($label->getCartAppearance());
    }

    public function testUseForParentDefaultsToNo(): void
    {
        $label = $this->createLabel();
        self::assertFalse($label->getUseForParent());
        self::assertTrue($label->setUseForParent(true)->getUseForParent());
    }
}
