<?php

namespace SprintF\Bundle\Admin\Field;

/**
 * Представление поля типа "int", которое относится к внутренней валюте.
 */
class MoneyField extends EntityField
{
    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return null === $value ? '-' : $value.'&nbsp;Ṫ';
    }
}
