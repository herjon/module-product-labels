<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\Label\PendingConditions;
use Magento\Framework\App\Request\DataPersistorInterface;
use PHPUnit\Framework\TestCase;

class PendingConditionsTest extends TestCase
{
    private const CONDITIONS = [
        '1' => ['type' => 'Magento\CatalogRule\Model\Rule\Condition\Combine', 'aggregator' => 'all', 'value' => '1'],
        '1--1' => [
            'type' => 'Magento\CatalogRule\Model\Rule\Condition\Product',
            'attribute' => 'sku',
            'value' => 'MH01',
        ],
    ];

    private function persistor(): DataPersistorInterface
    {
        return new class implements DataPersistorInterface {
            /** @var array */
            private array $data = [];

            public function set($key, $data)
            {
                $this->data[$key] = $data;
            }

            public function get($key)
            {
                return $this->data[$key] ?? null;
            }

            public function clear($key)
            {
                unset($this->data[$key]);
            }
        };
    }

    private function label(?int $id): Label
    {
        $label = $this->createMock(Label::class);
        $label->method('getLabelId')->willReturn($id);
        return $label;
    }

    public function testConditionsOfAFailedSaveComeBackOnTheSameLabel(): void
    {
        $pending = new PendingConditions($this->persistor());
        $pending->remember(['label_id' => '5', 'rule' => ['conditions' => self::CONDITIONS]]);

        $label = $this->label(5);
        $label->expects(self::once())->method('loadPost')->with(['conditions' => self::CONDITIONS]);
        $pending->restore($label);

        $again = $this->label(5);
        $again->expects(self::never())->method('loadPost');
        $pending->restore($again);
    }

    public function testNewLabelMatchesANewLabel(): void
    {
        $pending = new PendingConditions($this->persistor());
        $pending->remember(['label_id' => '', 'rule' => ['conditions' => self::CONDITIONS]]);

        $label = $this->label(null);
        $label->expects(self::once())->method('loadPost');
        $pending->restore($label);
    }

    public function testConditionsAreNeverAppliedToAnotherLabel(): void
    {
        $pending = new PendingConditions($this->persistor());
        $pending->remember(['label_id' => '5', 'rule' => ['conditions' => self::CONDITIONS]]);

        $other = $this->label(9);
        $other->expects(self::never())->method('loadPost');
        $pending->restore($other);

        $original = $this->label(5);
        $original->expects(self::never())->method('loadPost');
        $pending->restore($original);
    }

    public function testPostWithoutConditionsKeepsNothing(): void
    {
        $pending = new PendingConditions($this->persistor());
        $pending->remember(['label_id' => '5']);

        $label = $this->label(5);
        $label->expects(self::never())->method('loadPost');
        $pending->restore($label);
    }
}
