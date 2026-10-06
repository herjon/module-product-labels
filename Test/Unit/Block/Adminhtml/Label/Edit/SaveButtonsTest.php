<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Block\Adminhtml\Label\Edit;

use Majistar\ProductLabels\Block\Adminhtml\Label\Edit\SaveAndContinueButton;
use Majistar\ProductLabels\Block\Adminhtml\Label\Edit\SaveButton;
use PHPUnit\Framework\TestCase;

class SaveButtonsTest extends TestCase
{
    /**
     * @return array
     */
    private function action(array $button): array
    {
        return $button['data_attribute']['mage-init']['buttonAdapter']['actions'][0];
    }

    public function testSaveGoesBackToTheGrid(): void
    {
        $action = $this->action((new SaveButton())->getButtonData());

        self::assertSame('majistar_product_label_form.majistar_product_label_form', $action['targetName']);
        self::assertSame([true], $action['params']);
    }

    public function testSaveAndContinueUsesTheFormClientBackEditConvention(): void
    {
        self::assertSame([false], $this->action((new SaveAndContinueButton())->getButtonData())['params']);
    }
}
