<?php

namespace SprintF\Bundle\Admin\Field;

use SprintF\Bundle\Datetime\Component\Form\Type\DateRangeType;
use SprintF\Bundle\Datetime\Value\DateRange;

/**
 * Представление поля типа "Диапазон дат".
 */
class DateRangeField extends EntityField
{
    public const FORM_TYPE = DateRangeType::class;

    protected function getDefaultFormOptions(): array
    {
        return ['empty_data' => null];
    }

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return $value instanceof DateRange ? (string) $value : '-';
    }
}
