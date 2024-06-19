<?php

namespace SprintF\Bundle\Admin\Field;

/**
 * Представление поля типа "int", которое относится к внутренней валюте.
 */
class MoneyField extends EntityField implements FieldNeedAppParamsInterface
{
    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        if (null === $value) {
            return '-';
        }

        return $value.'&nbsp;'.($this->getAppParams()->has('app.currency.symbol') ? $this->getAppParams()->get('app.currency.symbol') : '¤');
    }
}
