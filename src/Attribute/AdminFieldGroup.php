<?php

namespace SprintF\Bundle\Admin\Attribute;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Атрибут, управляющий отображением группы полей сущности в админ-панели.
 * Применяется для отображения встраиваемых (embedded) сущностей
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AdminFieldGroup
{
    public function __construct(
        /** Наименование группы в единственном числе */
        private readonly string $label,
    ) {
    }

    /**
     * Возвращает наименование поля в единственном числе с учетом перевода.
     */
    public function getLabel(): TranslatableMessage
    {
        return new TranslatableMessage($this->label, [], 'admin.labels');
    }
}
