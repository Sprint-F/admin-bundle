<?php

namespace SprintF\Bundle\Admin\Attribute;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Атрибут, управляющий отображением полей сущности в админ-панели.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AdminField
{
    public function __construct(
        /** Наименование поля в единственном числе */
        private readonly string $label,
        /** Строка подсказки для поля в форме редактирования */
        public readonly string $help = '',
        /** Класс поля. Если null: попробовать вывести класс поля из типа Doctrine */
        public readonly ?string $class = null,
        /** Включать ли режим визуального редактора для поля типа TextField? */
        public readonly bool $editor = true, // @todo: сейчас не работает =false
        /** Путь, по которому нужно сохранить файл, если это у нас поле класса FileField */
        public readonly ?string $uploadPath = null,
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
