<?php

namespace SprintF\Bundle\Admin\Controller;

/**
 * Сценарий работы формы редактирования и добавления
 * EDIT - показ формы
 * SAVE - использования формы для сохранения данных.
 */
enum FormBuilderScenario
{
    case EDIT;
    case SAVE;
}
