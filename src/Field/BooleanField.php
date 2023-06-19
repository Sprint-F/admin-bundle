<?php

namespace SprintF\Bundle\Admin\Field;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Представление поля типа "boolean".
 */
class BooleanField extends EntityField
{
    public const FORM_TYPE = CheckboxType::class;

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return $value ? '+' : '-';
    }
}
