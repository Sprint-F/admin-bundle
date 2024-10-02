<?php

namespace SprintF\Bundle\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class FilterAbstract
{
    public function modifyQueryBuilder(QueryBuilder $queryBuilder, array $data): QueryBuilder
    {
        return $queryBuilder;
    }

    public function modifyFormBuilder(FormBuilderInterface $formBuilder, Request $request): FormBuilderInterface
    {
        return $formBuilder;
    }
}
