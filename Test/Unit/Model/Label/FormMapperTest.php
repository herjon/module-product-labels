<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label\Appearance;
use Majistar\ProductLabels\Model\Label\FormMapper;
use Majistar\ProductLabels\Model\Label\ImageInfo;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use PHPUnit\Framework\TestCase;

class FormMapperTest extends TestCase
{
    use CreatesLabels;

    private function mapper(): FormMapper
    {
        $imageInfo = $this->createStub(ImageInfo::class);
        $imageInfo->method('toFormValue')->willReturnCallback(
            static fn(string $file): array => ['name' => $file, 'url' => 'https://media/' . $file]
        );
        return new FormMapper($this->appearanceFactory(), $imageInfo);
    }

    public function testDefaultsForANewLabel(): void
    {
        $defaults = $this->mapper()->defaults([0, 1, 2]);

        self::assertSame('1', $defaults['is_active']);
        self::assertSame(['0'], $defaults['store_ids']);
        self::assertSame(['0', '1', '2'], $defaults['customer_group_ids']);
        self::assertSame(['product', 'listing'], $defaults['show_on']);
        self::assertSame('text', $defaults['product_appearance']['type']);
        self::assertSame('1', $defaults['listing_appearance']['use_product']);
    }

    public function testFormDataOfASavedLabel(): void
    {
        $label = $this->createLabel([
            'label_id' => 4,
            'name' => 'Sale',
            'store_ids' => [1],
            'price_from' => '10.000000',
            'active_from' => '2026-10-04 08:00:00',
        ]);
        $label->getProductAppearance()->setType('image')->setImage('sale.png')->setWidthPercent(30)
            ->setCornerRadius(12);

        $data = $this->mapper()->toFormData($label);

        self::assertSame('4', $data['label_id']);
        self::assertSame(['1'], $data['store_ids']);
        self::assertSame('10', $data['price_from']);
        self::assertSame('2026-10-04 08:00:00', $data['active_from']);
        self::assertSame(
            [['name' => 'sale.png', 'url' => 'https://media/sale.png']],
            $data['product_appearance']['image']
        );
        self::assertSame('30', $data['product_appearance']['width_percent']);
        self::assertSame('12', $data['product_appearance']['corner_radius']);
        self::assertSame('1', $data['listing_appearance']['use_product']);
        self::assertSame('image', $data['listing_appearance']['type'], 'listing fields start from the product ones');
    }

    public function testOwnListingAppearanceIsShown(): void
    {
        $label = $this->createLabel(['label_id' => 4]);
        $label->setListingAppearance(new Appearance(['type' => 'text', 'text' => 'Short']));

        $data = $this->mapper()->toFormData($label);

        self::assertSame('0', $data['listing_appearance']['use_product']);
        self::assertSame('Short', $data['listing_appearance']['text']);
    }

    public function testPostIsMappedOntoTheLabel(): void
    {
        $label = $this->mapper()->fromFormData([
            'name' => '  Black Friday ',
            'is_active' => '0',
            'priority' => '5',
            'stop_processing' => '1',
            'active_from' => '2026-11-27T23:00:00.000Z',
            'active_to' => '',
            'show_on' => ['listing'],
            'store_ids' => ['1', '2'],
            'customer_group_ids' => ['0'],
            'is_on_sale' => '1',
            'min_discount_percent' => '20',
            'stock_status' => 'low_stock',
            'low_stock_qty' => '3',
            'category_ids' => ['5', '6'],
            'price_from' => '',
            'price_to' => '99.5',
            'product_appearance' => [
                'type' => 'text',
                'text' => '-{SAVE_PERCENT}%',
                'insert_variable' => '',
                'background_color' => ' #FF0000 ',
                'text_color' => '',
                'shape' => 'pill',
                'text_size' => 'l',
                'image' => null,
                'width_percent' => '',
                'position' => 'top-right',
            ],
            'listing_appearance' => ['use_product' => '1', 'type' => 'image'],
        ], $this->createLabel());

        self::assertSame('Black Friday', $label->getName());
        self::assertFalse($label->getIsActive());
        self::assertSame(5, $label->getPriority());
        self::assertTrue($label->getStopProcessing());
        self::assertSame('2026-11-27 23:00:00', $label->getActiveFrom());
        self::assertNull($label->getActiveTo());
        self::assertSame(['listing'], $label->getShowOn());
        self::assertSame([1, 2], $label->getStoreIds());
        self::assertSame([0], $label->getCustomerGroupIds());
        self::assertTrue($label->getIsOnSale());
        self::assertSame(20, $label->getMinDiscountPercent());
        self::assertSame('low_stock', $label->getStockStatus());
        self::assertSame(3.0, $label->getLowStockQty());
        self::assertSame([5, 6], $label->getCategoryIds());
        self::assertNull($label->getPriceFrom());
        self::assertSame(99.5, $label->getPriceTo());

        $product = $label->getProductAppearance();
        self::assertSame('-{SAVE_PERCENT}%', $product->getText());
        self::assertSame('#ff0000', $product->getBackgroundColor());
        self::assertNull($product->getTextColor());
        self::assertSame('pill', $product->getShape());
        self::assertSame('l', $product->getTextSize());
        self::assertNull($product->getImage());
        self::assertSame(25, $product->getWidthPercent());
        self::assertSame('top-right', $product->getPosition());
        self::assertNull($label->getListingAppearance());
    }

    public function testOwnListingAppearanceIsMapped(): void
    {
        $label = $this->mapper()->fromFormData([
            'listing_appearance' => [
                'use_product' => '0',
                'type' => 'image',
                'image' => 'small.png',
                'width_percent' => '40',
                'corner_radius' => '0',
            ],
        ], $this->createLabel());

        self::assertSame('small.png', $label->getListingAppearance()?->getImage());
        self::assertSame(40, $label->getListingAppearance()?->getWidthPercent());
        self::assertSame(0, $label->getListingAppearance()?->getCornerRadius());
    }

    public function testInvalidDateIsKeptForTheValidator(): void
    {
        $label = $this->mapper()->fromFormData(['active_from' => 'not a date'], $this->createLabel());

        self::assertSame('not a date', $label->getActiveFrom());
    }

    public function testCartDefaultsToTheListingAppearance(): void
    {
        self::assertSame('1', $this->mapper()->defaults([0])['cart_appearance']['use_listing']);

        $label = $this->createLabel(['label_id' => 4]);
        $label->getProductAppearance()->setText('Product');
        $label->setListingAppearance(new Appearance(['type' => 'text', 'text' => 'Listing']));
        $data = $this->mapper()->toFormData($label);

        self::assertSame('1', $data['cart_appearance']['use_listing']);
        self::assertSame('Listing', $data['cart_appearance']['text'], 'cart fields start from the listing ones');
    }

    public function testOwnCartAppearanceRoundTrip(): void
    {
        $label = $this->mapper()->fromFormData([
            'cart_appearance' => [
                'use_listing' => '0',
                'type' => 'text',
                'text' => '-{SAVE_PERCENT}%',
                'text_size' => 's',
            ],
        ], $this->createLabel());
        self::assertSame('s', $label->getCartAppearance()?->getTextSize());
        self::assertSame('0', $this->mapper()->toFormData($label)['cart_appearance']['use_listing']);

        $label = $this->mapper()->fromFormData(['cart_appearance' => ['use_listing' => '1']], $label);
        self::assertNull($label->getCartAppearance());
    }

    public function testUseForParentRoundTrip(): void
    {
        self::assertSame('0', $this->mapper()->defaults([0])['use_for_parent']);
        $label = $this->mapper()->fromFormData(['use_for_parent' => '1'], $this->createLabel());
        self::assertTrue($label->getUseForParent());
        self::assertSame('1', $this->mapper()->toFormData($label)['use_for_parent']);
    }
}
