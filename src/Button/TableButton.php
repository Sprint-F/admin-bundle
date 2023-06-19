<?php

namespace SprintF\Bundle\Admin\Button;

/**
 * Кнопка какого-либо действия, отображающаяся над таблицей сущностей.
 */
class TableButton
{
    public function __construct(
        /** Надпись на кнопке */
        public string $label,
        /** URL, куда отправит нас кнопка */
        public string $url,
        /** HTML, содержащий иконку для кнопки. Необязательно. */
        public ?string $icon = null,
    ) {
    }
}
