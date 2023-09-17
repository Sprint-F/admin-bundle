<?php

namespace SprintF\Bundle\Admin\Controller;

use App\Workflow\ActionsSubscriber;
use SprintF\Bundle\Workflow\ActionAbstract;
use SprintF\Bundle\Workflow\Exception\CanNotException;
use SprintF\Bundle\Workflow\Exception\FailException;
use SprintF\Bundle\Workflow\WorkflowEntityInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @mixin AbstractAdminController
 */
trait AdminControllerActionTrait
{
    // @todo: развязать эту связь с App!
    protected ActionsSubscriber $actions;

    #[Required]
    public function setActionsSubscriber(ActionsSubscriber $actions)
    {
        $this->actions = $actions;
    }

    /**
     * URL контроллера отображения формы действия бизнес-процесса.
     */
    protected function getMakeRoute(string $actionClass, $entityId = null): string
    {
        if (null === $entityId) {
            return $this->getIndexRoute().'/make?action='.$actionClass;
        }

        return $this->getIndexRoute().'/make?action='.$actionClass.'&id='.$entityId;
    }

    /**
     * URL контроллера выполнения действия бизнес-процесса.
     */
    protected function getDoRoute(string $actionClass, $entityId = null): string
    {
        if (null === $entityId) {
            return $this->getIndexRoute().'/do?action='.$actionClass;
        }

        return $this->getIndexRoute().'/do?action='.$actionClass.'&id='.$entityId;
    }

    /**
     * Путь к шаблону страницы отображения формы действия бизнес-процесса.
     */
    protected function getMakeViewPath(): string
    {
        return '@SprintFAdmin/base/make.html.twig';
    }

    /**
     * Подготовка данных для формы действия бизнес-процесса.
     * По умолчанию форма создается без заранее заполненных данных.
     */
    protected function getDataForActionForm(string $actionClass, WorkflowEntityInterface $entity = null): ?array
    {
        return null;
    }

    /**
     * Создание формы для действия бизнес-процесса.
     * Применимо, если сущность имеет связанный с ней бизнес-процесс.
     */
    protected function getActionForm(string $actionClass, WorkflowEntityInterface $entity = null): FormInterface
    {
        /** @var WorkflowEntityInterface $entityClass */
        $entityClass = static::getEntityClass();
        if (!is_subclass_of($entityClass, WorkflowEntityInterface::class)) {
            throw new \Exception('Сущность не имеет связанного с ней бизнес-процесса');
        }

        $workflow = $entityClass::getWorkflow();
        $actionInfo = $workflow->getActionInfoByClass($actionClass);
        if (empty($actionInfo)) {
            throw $this->createNotFoundException('Действие не найдено');
        }
        if (empty($actionInfo->form) || !is_subclass_of($actionInfo->form, FormTypeInterface::class)) {
            throw $this->createNotFoundException('Для действия не указана форма');
        }

        $data = $this->getDataForActionForm($actionClass, $entity);
        $entityId = $entity?->getEntityId();
        $form = $this->createForm($actionInfo->form, $data, ['action' => $this->getDoRoute($actionClass, $entityId)]);

        return $form;
    }

    /**
     * Показ формы действия бизнес-процесса.
     */
    public function make(Request $request): Response
    {
        /** @var WorkflowEntityInterface $entityClass */
        $entityClass = static::getEntityClass();
        if (!is_subclass_of($entityClass, WorkflowEntityInterface::class)) {
            throw new \Exception('Сущность не имеет связанного с ней бизнес-процесса');
        }

        $actionClass = $request->get('action', null);
        if (null === $actionClass || !is_subclass_of($actionClass, ActionAbstract::class)) {
            throw $this->createNotFoundException('Действие не указано');
        }
        $actionInfo = $entityClass::getWorkflow()->getActionInfoByClass($actionClass);

        $entityId = $request->get('id', null);
        $entity = $this->eh->findOrNewEntity($entityClass, $entityId);
        if (null === $entity) {
            throw $this->createNotFoundException('Сущность не найдена');
        }

        $form = $this->getActionForm($actionClass, $entity);

        return $this->render($this->getMakeViewPath(), [
            'route' => $this->getIndexRoute(),
            'action' => $actionInfo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Выполнение действия бизнес-процесса.
     */
    public function do(Request $request): Response
    {
        /** @var \SprintF\Bundle\Workflow\WorkflowEntityInterface $entityClass */
        $entityClass = static::getEntityClass();
        if (!is_subclass_of($entityClass, WorkflowEntityInterface::class)) {
            throw new \Exception('Сущность не имеет связанного с ней бизнес-процесса');
        }

        $actionClass = $request->get('action', null);
        if (null === $actionClass || !is_subclass_of($actionClass, ActionAbstract::class)) {
            throw $this->createNotFoundException('Действие не указано');
        }

        $entityId = $request->get('id', null);
        $entity = $this->eh->findOrNewEntity($entityClass, $entityId);
        if (null === $entity) {
            throw $this->createNotFoundException('Сущность не найдена');
        }

        $workflow = $entityClass::getWorkflow();
        $actionInfo = $workflow->getActionInfoByClass($actionClass);

        $form = $this->getActionForm($actionClass, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $this->actions->get($actionClass);
            $context = new ($actionInfo->context)(...$form->getData());

            try {
                $action
                    ->setEntity($entity)
                    ->setContext($context);
                $result = $action(); // @todo: в будущем отличать CanNot от Fail
            } catch (CanNotException $exception) {
                $this->addFlash('error', 'Действие не разрешено: '.$exception->getMessage());
            } catch (FailException $exception) {
                $this->addFlash('error', 'Ошибка: '.$exception->getMessage());
            }
        }

        return $this->redirect($this->getIndexRoute());
    }
}
