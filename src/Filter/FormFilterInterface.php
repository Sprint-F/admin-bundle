<?php

namespace SprintF\Bundle\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

interface FormFilterInterface
{
    public function modifyQueryBuilder(QueryBuilder $queryBuilder, Request $request): QueryBuilder;

    public function modifyFormBuilder(FormBuilderInterface $formBuilder, Request $request): FormBuilderInterface;
}