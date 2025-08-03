<?php

namespace SprintF\Bundle\Admin\Controller;

use SprintF\Bundle\Admin\Field\EmbeddedFields;
use SprintF\Bundle\Admin\Field\EntityField;
use SprintF\Bundle\Admin\Field\FileField;
use SprintF\Bundle\Admin\Form\Type\FileUploadType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @mixin AbstractAdminController
 */
trait AdminControllerEditSaveDeleteTrait
{
    /**
     * URL страницы редактирования сущности.
     */
    protected function getEditRoute(): string
    {
        return $this->getIndexRoute().'/edit';
    }

    /**
     * URL страницы добавления сущности (форма).
     */
    protected function getAddRoute(): string
    {
        return $this->getEditRoute().'/?id=new';
    }

    /**
     * URL сохранения сущности (отредактированной или новой).
     */
    protected function getSaveRoute(): string
    {
        return $this->getIndexRoute().'/save';
    }

    /**
     * URL удаления сущности.
     */
    protected function getDeleteRoute(): string
    {
        return $this->getIndexRoute().'/delete';
    }

    /**
     * Путь к шаблону страницы редактирования (создания) сущности.
     */
    protected function getEditViewPath(): string
    {
        return '@SprintFAdmin/base/edit.html.twig';
    }

    /**
     * Список полей для формы редактирования сущности.
     *
     * @return EntityField[]
     */
    protected function getEditFields($entity = null): array
    {
        return $this->eh->getFields(
            null === $entity || (isset($entity->__isInitialized__) && false === $entity->__isInitialized__)
                ? static::getEntityClass()
                : get_class($entity)
        );
    }

    /**
     * Класс для формы редактирования сущности.
     */
    protected function getEditFormTypeClass(): string
    {
        return FormType::class;
    }

    protected function createFormBuilder(mixed $data = null, array $options = []): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createBuilder($this->getEditFormTypeClass(), $data, $options);
    }

    /**
     * Создание форм-билдера для формы добавления и редактирования сущности.
     */
    protected function getEditFormBuilder(object $entity, FormBuilderScenario $scenario = FormBuilderScenario::EDIT): FormBuilderInterface
    {
        $formBuilder = $this->createFormBuilder($entity);

        $formBuilder = $this->addFieldsToFormBuilder($entity, $formBuilder, $this->getEditFields($entity), $scenario);
        $formBuilder->add('__submit', SubmitType::class, ['label' => 'Сохранить']);

        return $formBuilder;
    }

    protected function addFieldsToFormBuilder(object $entity, FormBuilderInterface $formBuilder, array $fields, FormBuilderScenario $scenario): FormBuilderInterface
    {
        foreach ($fields as $key => $field) {
            /** @var EntityField $field */
            if ($field instanceof EmbeddedFields) {
                $accessor = PropertyAccess::createPropertyAccessor();
                $subEntity = $accessor->getValue($entity, $field->name) ?? new ($field->entityClass);

                $embedded = $this->container->get('form.factory')->createNamedBuilder($field->name, $this->getEditFormTypeClass(), $subEntity, ['label' => $field->label]);
                $embedded = $this->addFieldsToFormBuilder($subEntity, $embedded, $field->fields, $scenario);

                $formBuilder->add($embedded);
                continue;
            }
            if ($field->primary) {
                $formBuilder->add($field->name, HiddenType::class, $field->formOptions);
            } else {
                $options = ['label' => $field->label];
                if (FormBuilderScenario::EDIT === $scenario && is_a($field->formType, FileUploadType::class, true)) {
                    $options['mapped'] = true;
                }
                $formBuilder->add($field->name, $field->formType, array_merge($field->formOptions, $options));
            }
        }

        return $formBuilder;
    }

    public function getEntityByRequest(Request $request): object
    {
        $id = $request->get('form') ? $request->get('form')['id'] : $request->get('id', 'new');
        $repository = $this->eh->getEntityRepository(static::getEntityClass());

        return 'new' === $id || empty($id)
            ? (method_exists($repository, 'create') ? $repository->create() : new (static::getEntityClass()))
            : $this->eh->getEntityRepository(static::getEntityClass())->find($id);
    }

    /**
     * Действие "Отображение формы редактирования/создания сущности".
     */
    public function edit(Request $request): Response
    {
        $id = $request->get('id', 'new');
        $entity = $this->getEntityByRequest($request);

        $formBuilder = $this->getEditFormBuilder($entity);
        $formBuilder->setAction($this->getSaveRoute());

        return $this->render($this->getEditViewPath(), [
            'route' => $this->getIndexRoute(),
            'entity' => $entity,
            'isNew' => 'new' === $id,
            'label' => $this->eh->getEntityLabel(static::getEntityClass()),
            'fields' => $this->getEditFields($entity),
            'form' => $formBuilder->getForm(),
        ]);
    }

    /**
     * Действие "сохранение сущности".
     */
    public function save(Request $request): Response
    {
        /** @todo: Убрать это дублирование. Тут оно нужно для понимания - новая ли сущность? */
        $id = $request->get('form') ? $request->get('form')['id'] : $request->get('id', 'new');
        $entity = $this->getEntityByRequest($request);

        $formBuilder = $this->getEditFormBuilder($entity, FormBuilderScenario::SAVE);
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            // Обработка полей с загрузкой файлов
            foreach ($this->getEditFields($entity) as $key => $field) {
                if (is_a($field, FileField::class, true)) {
                    /** @var UploadedFile $file */
                    $formField = $form->get($field->name);
                    $file = $formField->getData();

                    // Если не загружен новый файл, проверим - не надо ли удалить старый?
                    if (null === $file) {
                        if (!empty($request->request->all()[$form->createView()->vars['full_name']][$formField->createView()->vars['name']]['delete'])) {
                            // @todo: хорошо бы работу с файлами вынести в отдельный сервис и здесь удалять ранее загруженный файл
                            $entity->{'set'.ucfirst($field->name)}(null);
                        }
                        continue;
                    }

                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $originalExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$originalExtension;

                    try {
                        $file->move($field->uploadPath, $newFilename);
                        $entity->{'set'.ucfirst($field->name)}($field->uploadPath.'/'.$newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Ошибка загрузки файла, поле '.$field->name);
                    }
                }
            }

            try {
                $this->eh->saveEntity($entity);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Ошибка сохранения данных: '.get_class($e).' - '.$e->getMessage());

                return new RedirectResponse($this->getEditRoute().'?id='.$id);
            }
        }

        return new RedirectResponse($this->getIndexRoute());
    }

    /**
     * Действие "удаления сущности".
     */
    public function delete(Request $request): Response
    {
        $entity = $this->getEntityByRequest($request);

        $this->eh->removeEntity($entity);

        return new RedirectResponse($this->getIndexRoute());
    }
}
