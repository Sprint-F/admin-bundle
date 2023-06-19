<?php

namespace SprintF\Bundle\Admin\Enum;

/**
 * Интерфейс для перечислений, значение которых можно отобразить в админ-панели.
 */
interface EnumWithLabelInterface
{
    public function label(): string;
}
