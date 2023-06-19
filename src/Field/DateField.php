<?php

namespace SprintF\Bundle\Admin\Field;

use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * Представление поля типа "дата".
 */
class DateField extends EntityField
{
    public const FORM_TYPE = DateType::class;

    protected string $format = 'Y-m-d';

    protected function getDefaultFormOptions(): array
    {
        return ['widget' => 'single_text', 'empty_data' => ''];
    }

    /**
     * Задание формата отображения для значения.
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return $value instanceof \DateTimeInterface ? $value->format($this->format) : '-';
    }
}
