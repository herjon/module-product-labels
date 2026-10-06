<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

abstract class AbstractOptions implements OptionSourceInterface
{
    /**
     * Options as value => label.
     *
     * @return array<string|int, Phrase|string>
     */
    abstract protected function options(): array;

    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: Phrase|string}>
     */
    public function toOptionArray(): array
    {
        $result = [];
        foreach ($this->options() as $value => $label) {
            $result[] = ['value' => (string) $value, 'label' => $label];
        }
        return $result;
    }

    /**
     * Allowed values.
     *
     * @return string[]
     */
    public function values(): array
    {
        return array_map('strval', array_keys($this->options()));
    }
}
