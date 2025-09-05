<?php

namespace SprintF\Bundle\Admin\Form\Type;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;

/**
 * Класс, заменяющий стандартный EntityType в формах админ-панели
 * Выбор "один из многих" вариантов или "многие из многих" в случае, когда сущность организована в виде дерева.
 */
class SelectTreeEntityType extends \Symfony\Bridge\Doctrine\Form\Type\EntityType
{
    public function getLoader(ObjectManager $manager, object $queryBuilder, string $class): ORMQueryBuilderLoader
    {
        if (!$queryBuilder instanceof QueryBuilder) {
            throw new \TypeError(sprintf('Expected an instance of "%s", but got "%s".', QueryBuilder::class, get_debug_type($queryBuilder)));
        }
        // Для стабильности выдаваемого результата добавляем дефолтную сортировку
        $queryBuilder->addOrderBy($queryBuilder->getRootAlias().'.id');

        return parent::getLoader($manager, $queryBuilder, $class);
    }
}
