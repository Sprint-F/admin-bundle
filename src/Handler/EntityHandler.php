<?php

namespace SprintF\Bundle\Admin\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\QueryBuilder;
use SprintF\Bundle\Admin\Attribute\AdminField;
use SprintF\Bundle\Admin\Attribute\AdminFieldGroup;
use SprintF\Bundle\Admin\Attribute\EntityLabel;
use SprintF\Bundle\Admin\Enum\EnumWithLabelInterface;
use SprintF\Bundle\Admin\Field\BooleanField;
use SprintF\Bundle\Admin\Field\DateField;
use SprintF\Bundle\Admin\Field\DateTimeField;
use SprintF\Bundle\Admin\Field\EmbeddedFields;
use SprintF\Bundle\Admin\Field\EntityField;
use SprintF\Bundle\Admin\Field\EnumField;
use SprintF\Bundle\Admin\Field\FieldNeedAppParamsInterface;
use SprintF\Bundle\Admin\Field\HasManyField;
use SprintF\Bundle\Admin\Field\HasOneField;
use SprintF\Bundle\Admin\Field\JsonField;
use SprintF\Bundle\Admin\Field\TextField;
use SprintF\Bundle\Admin\Form\Type\SelectEntityType;
use SprintF\Bundle\Admin\Form\Type\SelectTreeEntityType;
use SprintF\Bundle\Workflow\Entity\WorkflowEntityInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Сервис для работы с сущностями для админ-панели.
 */
class EntityHandler
{
    protected EntityManagerInterface $em;
    protected ContainerBagInterface $params;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Required]
    public function setParams(ContainerBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * Возвращает объект с наименованиями сущности в разных числах и падежах.
     */
    public function getEntityLabel(string $entityClass): EntityLabel
    {
        $classReflector = new \ReflectionClass($entityClass);
        $attrs = $classReflector->getAttributes(EntityLabel::class);
        if (empty($attrs)) {
            $attr = new EntityLabel(
                single: 'Entity', plural: 'Entities'
            );
        } else {
            $attr = $attrs[0]->newInstance();
        }

        return $attr;
    }

    public function isEntityTree(string $entityClass): bool
    {
        $classReflector = new \ReflectionClass($entityClass);
        $attrs = $classReflector->getAttributes('Gedmo\Mapping\Annotation\Tree');
        if (!empty($attrs)) {
            return true;
        }

        return false;
    }

    private function getTreeRootColumn(string $entityClass): ?string
    {
        if (!$this->isEntityTree($entityClass)) {
            return null;
        }

        $allEntityProperties = (new \ReflectionClass($entityClass))->getProperties();
        foreach ($allEntityProperties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!empty($property->getAttributes('Gedmo\Mapping\Annotation\TreeRoot'))) {
                return $property->getName();
            }
        }

        return null;
    }

    private function getTreeLeftKeyColumn(string $entityClass): ?string
    {
        if (!$this->isEntityTree($entityClass)) {
            return null;
        }

        $allEntityProperties = (new \ReflectionClass($entityClass))->getProperties();
        foreach ($allEntityProperties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!empty($property->getAttributes('Gedmo\Mapping\Annotation\TreeLeft'))) {
                return $property->getName();
            }
        }

        return null;
    }

    public function getTreeParentColumn(string $entityClass): ?string
    {
        if (!$this->isEntityTree($entityClass)) {
            return null;
        }

        $allEntityProperties = (new \ReflectionClass($entityClass))->getProperties();
        foreach ($allEntityProperties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!empty($property->getAttributes('Gedmo\Mapping\Annotation\TreeParent'))) {
                return $property->getName();
            }
        }

        return null;
    }

    /**
     * Возвращает базовый QueryBuilder, настроенный на выборку всех сущностей указанного класса из базы данных
     * Порядок задается полями первичного ключа.
     */
    public function getEntityFindAllQuery(string $entityClass): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')->from($entityClass, 'e');
        if ($this->isEntityTree($entityClass)) {
            $qb->orderBy(sprintf('e.%s, e.%s', $this->getTreeRootColumn($entityClass), $this->getTreeLeftKeyColumn($entityClass)), 'ASC');
        } else {
            foreach ($this->getPrimaryKeyFields($entityClass) as $primaryKeyField) {
                $qb->addOrderBy('e.'.$primaryKeyField->name, 'ASC');
            }
        }

        return $qb;
    }

    /**
     * Возвращает QueryBuilder, настроенный на выборку всех записей в логе действий над указанной по ID сущностью.
     */
    public function getEntityFindAllLogEntriesQuery(string $entityClass, $entityId): QueryBuilder
    {
        /** @var WorkflowEntityInterface $entityClass */
        $actionLogEntryClass = $entityClass::getActionLogEntryClass();
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')->from($actionLogEntryClass, 'l');

        foreach ($this->getPrimaryKeyFields($entityClass) as $primaryKeyField) {
            // @todo: две следующих строки подолежат рефакторингу, если мы перейдем однажды на составные первичные ключи
            $qb->andWhere('l.entityId=:id');
            $qb->setParameter(':id', $entityId);
        }

        $qb->addOrderBy('l.id', 'DESC');

        return $qb;
    }

    /**
     * Возвращает репозиторий для работы с сущностями.
     */
    public function getEntityRepository(string $entityClass): EntityRepository
    {
        return $this->em->getRepository($entityClass);
    }

    /**
     * Получает сущность из базы данных либо возвращает новую сущность.
     */
    public function findOrNewEntity(string $entityClass, $id = null): ?object
    {
        if (null === $id) {
            return new $entityClass();
        }

        return $this->getEntityRepository($entityClass)->find($id);
    }

    /**
     * Сохраняет в базу данных новую или измененную сущность.
     */
    public function saveEntity(object $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Удаляет сущность из базы данных.
     */
    public function removeEntity(object $entity): void
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * Соответствие типов полей Doctrine и типов полей админ-панели.
     */
    protected function getFieldClassByDoctrineType(string $doctrineType): string
    {
        return match ($doctrineType) {
            'date' => DateField::class,
            'datetime' => DateTimeField::class,
            'boolean' => BooleanField::class,
            'text' => TextField::class,
            'json' => JsonField::class,
            default => EntityField::class,
        };
    }

    /**
     * Возвращает полный список полей сущности.
     *
     * @return EntityField[]
     */
    public function getFields(string $entityClass): array
    {
        $allEntityProperties = (new \ReflectionClass($entityClass))->getProperties();
        $fields = [];
        foreach ($allEntityProperties as $property) {
            // Статические свойства нас не интересуют, пропускаем
            if ($property->isStatic()) {
                continue;
            }

            // Если сущность представляет собой элемент дерева, то пропускаем служебные свойства дерева
            if ($this->isEntityTree($entityClass)) {
                foreach ([
                             'Gedmo\Mapping\Annotation\TreeLeft',
                             'Gedmo\Mapping\Annotation\TreeRight',
                             'Gedmo\Mapping\Annotation\TreeLevel',
                             'Gedmo\Mapping\Annotation\TreeRoot',
                         ] as $treeColumnAttribute) {
                    if (!empty($property->getAttributes($treeColumnAttribute))) {
                        continue 2;
                    }
                }
            }

            // Свойство, помеченное атрибутом AdminFieldGroup (вложенная сущность)
            $propertyFieldGroupAttributes = $property->getAttributes(AdminFieldGroup::class);
            if (!empty($propertyFieldGroupAttributes)) {
                $propertyFieldGroupAttribute = $propertyFieldGroupAttributes[0]->newInstance();
                $fields[$property->getName()] = new EmbeddedFields(
                    name: $property->getName(),
                    label: $propertyFieldGroupAttribute->getLabel() ?: $property->getName(),
                    entityClass: $property->getType()->getName(),
                    fields: $this->getFields($property->getType()->getName())
                );
                continue;
            }

            // Обычное свойство, связанное с поле в БД Doctrine
            $propertyColumnAttributes = $property->getAttributes(Column::class);
            if (!empty($propertyColumnAttributes)) {
                $propertyColumnAttribute = $propertyColumnAttributes[0]->newInstance();
                $propertyType = $propertyColumnAttribute->type;

                $adminFieldAttributes = $property->getAttributes(AdminField::class);
                if (!empty($adminFieldAttributes)) {
                    /** @var AdminField $adminFieldAttribute */
                    $adminFieldAttribute = $adminFieldAttributes[0]->newInstance();
                    $label = $adminFieldAttribute->getLabel();
                    $help = $adminFieldAttribute->help;
                } else {
                    $label = $property->getName();
                    $help = null;
                }

                $isPrimary = false;
                $propertyIdAttributes = $property->getAttributes(Id::class);
                if (!empty($propertyIdAttributes)) {
                    $isPrimary = true;
                }

                // Определяем класс поля в админ-панели
                switch (true) {
                    case !empty($adminFieldAttribute->class):
                        $fieldClass = $adminFieldAttribute->class;
                        break;
                    case ($property->getType() instanceof \ReflectionNamedType) && is_subclass_of($property->getType()?->getName(), EnumWithLabelInterface::class):
                        $fieldClass = EnumField::class;
                        break;
                    default:
                        $fieldClass = $this->getFieldClassByDoctrineType($propertyType);
                }

                // Определяем класс поля формы Symfony
                switch (true) {
                    case ($property->getType() instanceof \ReflectionNamedType) && is_subclass_of($property->getType()?->getName(), EnumWithLabelInterface::class):
                        $formType = EnumType::class;
                        break;
                    default:
                        $formType = $fieldClass::FORM_TYPE;
                }

                $formOptions = ['required' => !$property->getType()?->allowsNull()];
                if (is_a($formType, CheckboxType::class, true)) {
                    $formOptions['required'] = false;
                }
                if (($property->getType() instanceof \ReflectionNamedType) && is_subclass_of($property->getType()?->getName(), EnumWithLabelInterface::class) && enum_exists($property->getType()?->getName())) {
                    $enumType = $property->getType()?->getName();
                    $formOptions['class'] = $enumType;
                    $formOptions['choice_label'] = function ($choice, $key, $value) {
                        return $choice->label();
                    };
                }
                if (!empty($help)) {
                    $formOptions['help'] = $help;
                }

                $fields[$property->getName()] = new ($fieldClass)(
                    name: $property->getName(),
                    label: $label,
                    formType: $formType,
                    formOptions: $formOptions,
                    primary: $isPrimary,
                );
                if ($fields[$property->getName()] instanceof FieldNeedAppParamsInterface) {
                    $fields[$property->getName()]->setAppParams($this->params);
                }

                if (!empty($adminFieldAttribute->uploadPath)) {
                    $fields[$property->getName()]->uploadPath = $adminFieldAttribute->uploadPath;
                }
            }

            // Отношение "многие-к-одному" или "один-к-одному"
            $propertyHasOneAttributes = $property->getAttributes(ManyToOne::class) ?: $property->getAttributes(OneToOne::class);
            if (!empty($propertyHasOneAttributes)) {
                $field = $this->getFieldForHasOneProperty($entityClass, $property, $propertyHasOneAttributes[0]->newInstance());
                if (null !== $field) {
                    $fields[$property->getName()] = $field;
                }
            }

            // Отношение "многие-ко-многим" или "один-ко-многим"
            $propertyHasManyAttributes = $property->getAttributes(ManyToMany::class) ?: $property->getAttributes(OneToMany::class);
            if (!empty($propertyHasManyAttributes)) {
                $field = $this->getFieldForHasManyProperty($property, $propertyHasManyAttributes[0]->newInstance());
                if (null !== $field) {
                    $fields[$property->getName()] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * Возвращает список полей сущности, входящих в первичный ключ.
     */
    public function getPrimaryKeyFields(string $entityClass): array
    {
        return array_filter($this->getFields($entityClass), fn (EntityField $entityField) => $entityField->primary);
    }

    /**
     * По рефлектору свойства и по атрибуту Doctrine ManyToOne или OneToOne строим объект поля админ-панели.
     */
    private function getFieldForHasOneProperty(string $entityClass, \ReflectionProperty $property, ManyToOne|OneToOne $propertyHasOneAttribute): ?HasOneField
    {
        $targetEntity = $propertyHasOneAttribute->targetEntity;

        $adminFieldAttributes = $property->getAttributes(AdminField::class);
        if (empty($adminFieldAttributes)) {
            return null;
        }

        /** @var AdminField $adminFieldAttribute */
        $adminFieldAttribute = $adminFieldAttributes[0]->newInstance();
        $label = $adminFieldAttribute->getLabel();

        $field = new HasOneField(
            name: $property->getName(),
            label: $label,
            formType: $this->isEntityTree($entityClass) ? SelectTreeEntityType::class : SelectEntityType::class,
            formOptions: ['required' => !$property->getType()?->allowsNull(), 'class' => $targetEntity],
            primary: false,
        );

        if ($field instanceof FieldNeedAppParamsInterface) {
            $field->setAppParams($this->params);
        }

        return $field;
    }

    /**
     * По рефлектору свойства и по атрибуту Doctrine ManyToMany или OneToMany строим объект поля админ-панели.
     */
    private function getFieldForHasManyProperty(\ReflectionProperty $property, ManyToMany|OneToMany $propertyHasManyAttribute): ?HasManyField
    {
        $targetEntity = $propertyHasManyAttribute->targetEntity;

        $adminFieldAttributes = $property->getAttributes(AdminField::class);
        if (empty($adminFieldAttributes)) {
            return null;
        }

        /** @var AdminField $adminFieldAttribute */
        $adminFieldAttribute = $adminFieldAttributes[0]->newInstance();
        $label = $adminFieldAttribute->getLabel();

        $field = new HasManyField(
            name: $property->getName(),
            label: $label,
            formType: SelectEntityType::class,
            formOptions: ['multiple' => true, 'required' => false, 'class' => $targetEntity],
            primary: false,
        );

        if ($field instanceof FieldNeedAppParamsInterface) {
            $field->setAppParams($this->params);
        }

        return $field;
    }
}
