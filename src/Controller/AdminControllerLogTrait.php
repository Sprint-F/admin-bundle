<?php

namespace SprintF\Bundle\Admin\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use SprintF\Bundle\Admin\Field\EntityField;
use SprintF\Bundle\Workflow\WorkflowEntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @mixin AbstractAdminController
 */
trait AdminControllerLogTrait
{
    /**
     * URL страницы лога бизнес-действий над сущностью.
     */
    protected function getLogRoute(WorkflowEntityInterface $entity): string
    {
        return $this->getIndexRoute().'/'.$entity->getEntityId().'/log';
    }

    /**
     * Путь к шаблону страницы отображения лога действий над сущностью.
     */
    protected function getLogViewPath(): string
    {
        return '@SprintFAdmin/base/log.html.twig';
    }

    /**
     * Список полей для таблицы лога действий над сущностью.
     *
     * @return EntityField[]
     */
    protected function getLogFields(): array
    {
        /** @var WorkflowEntityInterface $entityClass */
        $entityClass = static::getEntityClass();
        $fields = $this->eh->getFields($entityClass::getActionLogEntryClass());
        unset($fields['entity'], $fields['entityClass'], $fields['entityId'], $fields['context']);

        return $fields;
    }

    /**
     * QueryBuilder, готовящий запрос на получение записей в логе действий бизнес-процесса.
     */
    protected function getLogQueryBuilder($id): QueryBuilder
    {
        $qb = $this->eh->getEntityFindAllLogEntriesQuery(static::getEntityClass(), $id);

        return $qb;
    }

    /**
     * Действие "Отображение лога действий над сущностью".
     */
    public function log($id, Request $request): Response
    {
        $page = $request->get('page', 1);
        $qb = $this->getLogQueryBuilder($id);
        $qb
            ->setFirstResult(($page - 1) * static::ENTITITES_PER_PAGE)
            ->setMaxResults(static::ENTITITES_PER_PAGE);

        $paginator = new Paginator($qb, true);
        $total = $paginator->count();

        return $this->render($this->getLogViewPath(), [
            'route' => $this->getIndexRoute(),
            'id' => $id,
            'label' => $this->eh->getEntityLabel(static::getEntityClass()),
            'fields' => $this->getLogFields(),
            'pages' => [
                'num' => $page,
                'total' => (int) ceil($total / static::ENTITITES_PER_PAGE),
            ],
            'entities' => [
                'total' => $total,
                'data' => $paginator->getIterator(),
            ],
        ]);
    }
}
