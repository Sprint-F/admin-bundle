<?php

namespace SprintF\Bundle\Admin\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use SprintF\Bundle\Admin\Attribute\EntityLabel;
use SprintF\Bundle\Admin\Button\EntityButton;
use SprintF\Bundle\Admin\Button\TableButton;
use SprintF\Bundle\Admin\Field\EntityField;
use SprintF\Bundle\Admin\Filter\FilterAbstract;
use SprintF\Bundle\Workflow\Entity\WorkflowEntityInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @mixin AbstractAdminController
 */
trait AdminControllerIndexTrait
{
    /**
     * Список дополнительных параметров, которые будут переданы в шаблон.
     */
    protected array $indexTemplateParameters = [];

    /**
     * URL "главной" страницы раздела админ-панели: страницы с таблицей сущностей.
     */
    protected function getIndexRoute(): string
    {
        $invokeReflector = new \ReflectionMethod($this, 'index');
        $routeAttrs = $invokeReflector->getAttributes(Route::class);

        return $routeAttrs[0]->getArguments()['path'] ?? $routeAttrs[0]->getArguments()[0];
    }

    /**
     * Список полей для таблицы сущностей.
     *
     * @return EntityField[]
     */
    protected function getIndexFields(): array
    {
        $fields = $this->eh->getFields(static::getEntityClass());

        // Кроме всех служебных полей дерева, в главной таблице следует удалить поле "родительская нода":
        if ($this->eh->isEntityTree(static::getEntityClass())) {
            unset($fields[$this->eh->getTreeParentColumn(static::getEntityClass())]);
        }

        return $fields;
    }

    /**
     * Путь к шаблону "главной" страницы раздела админ-панели.
     */
    protected function getIndexViewPath(): string
    {
        return '@SprintFAdmin/base/index.html.twig';
    }

    /**
     * Кнопки, которые будут располагаться перед таблицей сущностей.
     *
     * @todo: это всё делать iterable
     *
     * @return TableButton[]
     */
    protected function getIndexTableButtons(EntityLabel $entityLabel): array
    {
        return [
            'add' => new TableButton(
                label: 'Добавить '.$entityLabel->getSingleAcc(),
                url: $this->getAddRoute(),
                icon: '<svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            ),
        ];
    }

    /**
     * Действия бизнес-процесса, которые можно вызвать с "новой" сущностью
     * Отображаются в меню действий над таблицей сущностей.
     *
     * @todo: это всё делать iterable
     *
     * @return TableButton[]
     */
    protected function getIndexTableActions(): array
    {
        $buttons = [];

        if (is_subclass_of(static::getEntityClass(), WorkflowEntityInterface::class)) {
            /** @var WorkflowEntityInterface $entityClass */
            $entityClass = static::getEntityClass();
            foreach ($entityClass::getWorkflow()->getAvailableForNewEntitiesActions() as $action) {
                $buttons[$action->class] = new TableButton(
                    label: $action->title,
                    url: $this->getMakeRoute($action->class),
                );
            }
        }

        return $buttons;
    }

    /**
     * Статусы бизнес-процесса.
     * Каждый статус - это сервис.
     * Нужны для отображения в таблице сущностей.
     *
     * @todo: это всё делать iterable
     *
     * @return TableButton[]
     */
    protected function getIndexStatuses(): array
    {
        $statuses = [];

        if (is_subclass_of(static::getEntityClass(), WorkflowEntityInterface::class)) {
            /** @var WorkflowEntityInterface $entityClass */
            $entityClass = static::getEntityClass();
            foreach ($entityClass::getWorkflow()->getAvailableStatuses() as $status) {
                $statuses[$status->class]['title'] = $status->title;
                $statuses[$status->class]['service'] = $this->actions->get($status->class);
            }
        }

        return $statuses;
    }

    /**
     * Кнопки, которые будут рядом с сущностью в таблице.
     *
     * @todo: это всё делать iterable
     *
     * @return EntityButton[]
     */
    protected function getIndexEntityButtons(): array
    {
        $buttons = [
            'edit' => function (object $entity) {
                return new EntityButton(
                    label: '',
                    url: $this->getEditRoute().'/?id='.$entity->getId(),
                    icon: '<svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>'
                );
            },
        ];

        if (is_subclass_of(static::getEntityClass(), WorkflowEntityInterface::class)) {
            /** @var WorkflowEntityInterface $entityClass */
            $entityClass = static::getEntityClass();
            $buttons['log'] = function (WorkflowEntityInterface $entity) {
                return new EntityButton(
                    label: '',
                    url: $this->getLogRoute($entity),
                    icon: '<svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>'
                );
            };
        }

        return $buttons;
    }

    /**
     * Действия бизнес-процесса, которые можно вызвать с существующей сущностью.
     * Отображаются в меню действий в каждом ряду таблицы.
     *
     * @todo: это всё делать iterable
     *
     * @return TableButton[]
     */
    protected function getIndexEntityActions(): array
    {
        $buttons = [];

        if (is_subclass_of(static::getEntityClass(), WorkflowEntityInterface::class)) {
            /** @var WorkflowEntityInterface $entityClass */
            $entityClass = static::getEntityClass();
            foreach ($entityClass::getWorkflow()->getAvailableForExistingEntitiesActions() as $action) {
                $buttons[$action->class] = function (WorkflowEntityInterface $entity) use ($action) {
                    return (new class(label: $action->title, url: $this->getMakeRoute($action->class, $entity->getEntityId())) extends EntityButton {
                        private $canBeApplied;

                        public function setCanBeApplied($canBeApplied)
                        {
                            $this->canBeApplied = $canBeApplied;

                            return $this;
                        }

                        public function visible(WorkflowEntityInterface $entity): bool
                        {
                            return is_callable($this->canBeApplied) ? ($this->canBeApplied)($entity) : (bool) $this->canBeApplied;
                        }
                    })->setCanBeApplied($action->canBeAppliedToExistingEntity);
                };
            }
        }

        return $buttons;
    }

    /**
     * Список фильтров для страницы.
     *
     * @return FilterAbstract[]
     */
    protected function getIndexFilters(): array
    {
        return [];
    }

    /**
     * Класс для формы отображения фильтров.
     */
    protected function getIndexFiltersFormTypeClass(): string
    {
        return FormType::class;
    }

    /**
     * Конструктор формы отображения фильтров.
     */
    protected function createIndexFiltersFormBuilder(mixed $data = null, array $options = []): FormBuilderInterface
    {
        /** @var FormFactoryInterface $factory */
        $factory = $this->container->get('form.factory');

        return $factory->createNamedBuilder('filters', $this->getIndexFiltersFormTypeClass(), $data, $options);
    }

    /**
     * Создание форм-билдера для формы отображения фильтров.
     */
    protected function getIndexFiltersFormBuilder(Request $request): FormBuilderInterface
    {
        $formBuilder = $this->createIndexFiltersFormBuilder();
        $formBuilder->setMethod('GET');
        foreach ($this->getIndexFilters() as $filter) {
            $formBuilder = $filter->modifyFormBuilder($formBuilder, $request);
        }
        $formBuilder->add('__submit', SubmitType::class, ['label' => 'Поиск']);
        $formBuilder->add('__clear', SubmitType::class, ['label' => 'Сброс']);

        return $formBuilder;
    }

    /**
     * Список дополнительных параметров, которые будут переданы в шаблон.
     */
    protected function getIndexTemplateParameters(): array
    {
        return $this->indexTemplateParameters;
    }

    protected function addIndexTemplateParameters(array $parameters): static
    {
        $this->indexTemplateParameters = array_merge($this->indexTemplateParameters, $parameters);

        return $this;
    }

    /**
     * QueryBuilder, готовящий запрос на получение списка сущностей на главной странице раздела.
     */
    protected function getIndexQueryBuilder(): QueryBuilder
    {
        $qb = $this->eh->getEntityFindAllQuery(static::getEntityClass());

        return $qb;
    }

    /**
     * Собственно действие "главной страницы" раздела админ-панели: вывод таблицы сущностей.
     */
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $qb = $this->getIndexQueryBuilder();

        $filtersFormBuilder = $this->getIndexFiltersFormBuilder($request);

        $filtersForm = $filtersFormBuilder->getForm();
        $filtersForm->handleRequest($request);
        if ($filtersForm->isSubmitted()) { // Тут мы осознанно забиваем на isValid, поскольку нам не нужна защита CSRF
            $filtersData = $filtersForm->getData();
            if ($filtersForm->get('__clear')->isClicked()) {
                return new RedirectResponse($this->getIndexRoute());
            }
            foreach ($this->getIndexFilters() as $filter) {
                $qb = $filter->modifyQueryBuilder($qb, $filtersData);
            }
        }

        $baseUrl = $this->getIndexRoute();
        if (!empty($filtersData)) {
            $baseUrl .= '?'.http_build_query(['filters' => $filtersData]);
        }

        $qb
            ->setFirstResult(($page - 1) * static::ENTITITES_PER_PAGE)
            ->setMaxResults(static::ENTITITES_PER_PAGE);
        $paginator = new Paginator($qb, true);
        $total = $paginator->count();

        return $this->render($this->getIndexViewPath(), array_merge($this->getIndexTemplateParameters(), [
            'route' => $this->getIndexRoute(),
            'label' => $this->eh->getEntityLabel(static::getEntityClass()),
            'buttons' => [
                'table' => $this->getIndexTableButtons($this->eh->getEntityLabel(static::getEntityClass())),
                'entity' => $this->getIndexEntityButtons(),
            ],
            'actions' => [
                'table' => $this->getIndexTableActions(),
                'entity' => $this->getIndexEntityActions(),
            ],
            'statuses' => $this->getIndexStatuses(),
            'fields' => $this->getIndexFields(),
            'filters' => [
                'form' => $filtersForm->createView(),
                'data' => $filtersData ?? [],
                'route' => $baseUrl,
            ],
            'pages' => [
                'num' => $page,
                'total' => (int) ceil($total / static::ENTITITES_PER_PAGE),
            ],
            'entities' => [
                'total' => $total,
                'data' => $paginator->getIterator(),
            ],
        ]));
    }
}
