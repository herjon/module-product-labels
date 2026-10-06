<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

/**
 * Where and when labels are being shown.
 */
class FilterContext
{
    /**
     * @param string $now current UTC date-time, Y-m-d H:i:s
     * @param string $today current date in the store time zone, Y-m-d
     * @param int $customerGroupId
     * @param string $area one of LabelInterface::SHOW_ON_*
     * @param string $newSource Config::NEW_SOURCE_*
     * @param int $newDays
     */
    public function __construct(
        public readonly string $now,
        public readonly string $today,
        public readonly int $customerGroupId,
        public readonly string $area,
        public readonly string $newSource,
        public readonly int $newDays
    ) {
    }
}
