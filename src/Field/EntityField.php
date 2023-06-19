<?php

namespace SprintF\Bundle\Admin\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Представление поля сущности.
 */
class EntityField
{
    public const FORM_TYPE = TextType::class;

    public function __construct(
        /** Имя поля в сущности */
        public string $name,
        /** Наименование (отображаемое) поля */
        public string|TranslatableMessage $label,
        /** Имя класса поля формы для этого поля */
        public string $formType = self::FORM_TYPE,
        /** Опции для построения поля формы для этого поля */
        public array $formOptions = [],
        /** Признак того, что поле входит в состав первичного ключа */
        public bool $primary = false,
    ) {
        $this->formOptions = array_merge($this->getDefaultFormOptions(), $this->formOptions);
    }

    /**
     * Опции настройки элемента формы по умолчанию.
     */
    protected function getDefaultFormOptions(): array
    {
        return [];
    }

    protected static function getPropertyAccessor(): PropertyAccessor
    {
        static $propertyAccessor = null;
        if (null === $propertyAccessor) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $propertyAccessor;
    }

    /**
     * Метод получения значения поля для конкретной сущности.
     */
    public function value(object $entity): mixed
    {
        return static::getPropertyAccessor()->getValue($entity, $this->name);
    }

    /**
     * Метод отображения значения поля в сценарии "label": простой текстовый вывод.
     */
    public function renderAsLabel(object $entity): string
    {
        return (string) $this->value($entity);
    }
}
