<?php

namespace SmartInformationSystems\ObjectHistoryBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;

use SmartInformationSystems\ObjectHistoryBundle\Entity\ObjectHistory;
use SmartInformationSystems\ObjectHistoryBundle\Entity\ObjectHistoryDetail;

class DoctrineListener
{
    /**
     * Логгировать сущность.
     *
     * @const string
     */
    const ANNOTATION_LOGGING_ENTITY = 'SmartInformationSystems\ObjectHistoryBundle\Annotations\LoggingEntity';

    /**
     * Логгировать поле.
     *
     * @const string
     */
    const ANNOTATION_LOGGING_FIELD = 'SmartInformationSystems\ObjectHistoryBundle\Annotations\LoggingField';

    /**
     * Не логгировать поле.
     *
     * @const string
     */
    const ANNOTATION_NOT_LOGGING_FIELD = 'SmartInformationSystems\ObjectHistoryBundle\Annotations\NotLoggingField';

    /**
     * Контейнер.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Контекст безопасности.
     *
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * Обработчик аннотаций.
     *
     * @var AnnotationReader
     */
    private $annotationReader = NULL;

    /**
     * История объектов для сохранения.
     *
     * @var ArrayCollection
     */
    private $history = array();

    /**
     * Конструктор.
     *
     * @param ContainerInterface $container Контейнер
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->annotationReader = new AnnotationReader();
        $this->history = new ArrayCollection();

        $this->securityContext = NULL;
    }

    /**
     * Возвращает обработчик аннотаций.
     *
     * @return AnnotationReader
     */
    public function getAnnotationReader()
    {
        return $this->annotationReader;
    }

    /**
     * Обработчик события "preUpdate".
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $objectHistory = NULL;

        $reflectionObject = new \ReflectionObject($entity);
        $objLogging = $this->isLoggingObject($reflectionObject);

        foreach ($reflectionObject->getProperties() as $reflectionProperty) {

            if (
                ($objLogging && !$this->isNotLoggingField($reflectionProperty))
                ||
                $this->isLoggingField($reflectionProperty)
            ) {

                $propertyName = $reflectionProperty->getName();

                if ($args->hasChangedField($propertyName)) {

                    $oldValue = $this->normalizeValue($args->getOldValue($propertyName));
                    $newValue = $this->normalizeValue($args->getNewValue($propertyName));

                    if (!$oldValue && !$newValue) {
                        continue;
                    }

                    if (empty($objectHistory)) {
                        $objectHistory = $this->createObjectHistory(
                            $reflectionObject->getName(),
                            $entity->getId()
                        );
                    }

                    $objectHistory->addDetail(
                        $this->createObjectHistoryDetail(
                            $objectHistory,
                            $propertyName,
                            $oldValue,
                            $newValue
                        )
                    );
                }
            }
        }

        if ($objectHistory) {
            $this->history[] = $objectHistory;
        }
    }

    /**
     * Обработчик события "postUpdate".
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->saveHistory($args->getEntityManager());
    }

    /**
     * Обработчик события "postPersist".
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $objectHistory = NULL;

        $reflectionObject = new \ReflectionObject($entity);
        $objLogging = $this->isLoggingObject($reflectionObject);

        foreach ($reflectionObject->getProperties() as $reflectionProperty) {

            if (
                ($objLogging && !$this->isNotLoggingField($reflectionProperty))
                ||
                $this->isLoggingField($reflectionProperty)
            ) {

                $propertyName = $reflectionProperty->getName();
                $method = 'get' . ucfirst($propertyName);

                if (!$entity->$method()) {
                    continue;
                }

                if (empty($objectHistory)) {
                    $objectHistory = $this->createObjectHistory(
                        $reflectionObject->getName(),
                        $entity->getId()
                    );
                }

                $value = $entity->$method();

                if (is_object($value)) {
                    if (method_exists($value, 'getId')) {
                        $value = $value->{'getId'}();
                    } else {
                        continue;
                    }
                }

                $objectHistory->addDetail(
                    $this->createObjectHistoryDetail(
                        $objectHistory,
                        $propertyName,
                        NULL,
                        $value
                    )
                );
            }
        }

        if ($objectHistory) {
            $this->history[] = $objectHistory;
        }

        $this->saveHistory($args->getEntityManager());
    }

    /**
     * Сохраняет накопившуюся историю.
     *
     * @param EntityManager $em
     *
     * @return void
     */
    private function saveHistory(EntityManager $em)
    {
        $toDelete = array();

        /** @var ObjectHistory $obj */
        foreach ($this->history as $obj) {
            $em->persist($obj);
            foreach ($obj->getDetails() as $detail) {
                $em->persist($detail);
            }
            $toDelete[] = $obj;
        }
        $em->flush();

        foreach ($toDelete as $obj) {
            $this->history->removeElement($obj);
        }
    }

    /**
     * Создает объект истории.
     *
     * @param string $objectClass Имя класса
     * @param int $objectId Идентификатор
     *
     * @return ObjectHistory
     */
    private function createObjectHistory($objectClass, $objectId)
    {
        $obj = new ObjectHistory();
        $obj->setObjectClass($objectClass);
        $obj->setObjectId($objectId);

        if ($user = $this->getSecurityContext()->getToken()->getUser()) {
            if (is_object($user) && method_exists($user, 'getId')) {
                if ($this->getSecurityContext()->isGranted('ROLE_ADMIN')) {
                    $obj->setAdminId($user->getId());
                } else {
                    $obj->setUserId($user->getId());
                }
            }
        }

        return $obj;
    }

    /**
     * Создает объект исторического поля.
     *
     * @param ObjectHistory $objectHistory Объект истории
     * @param string $propertyName Имя поля
     * @param mixed $oldValue Старое значение
     * @param mixed $newValue Новое значение
     *
     * @return ObjectHistoryDetail
     */
    private function createObjectHistoryDetail(ObjectHistory $objectHistory, $propertyName, $oldValue, $newValue)
    {
        $detail = new ObjectHistoryDetail();
        $detail->setObjectHistory($objectHistory);
        $detail->setFieldName($propertyName);
        $detail->setOldValue($oldValue);
        $detail->setNewValue($newValue);

        return $detail;
    }

    /**
     * Возвращает, стоит ли логгировать объект.
     *
     * @param \ReflectionObject $reflectionObject
     *
     * @return bool
     */
    private function isLoggingObject(\ReflectionObject $reflectionObject)
    {
        return $this->getAnnotationReader()->getClassAnnotation($reflectionObject, self::ANNOTATION_LOGGING_ENTITY) ? TRUE : FALSE;
    }

    /**
     * Возвращает, логгируемое ли поле.
     *
     * @param \ReflectionProperty $reflectionProperty
     *
     * @return bool
     */
    private function isLoggingField(\ReflectionProperty $reflectionProperty)
    {
        return $this->getAnnotationReader()->getPropertyAnnotation($reflectionProperty, self::ANNOTATION_LOGGING_FIELD);
    }

    /**
     * Возвращает, нелоггируемое ли поле.
     *
     * @param \ReflectionProperty $reflectionProperty
     *
     * @return bool
     */
    private function isNotLoggingField(\ReflectionProperty $reflectionProperty)
    {
        return $this->getAnnotationReader()->getPropertyAnnotation($reflectionProperty, self::ANNOTATION_NOT_LOGGING_FIELD);
    }

    /**
     * Возвращает контекст безоспасности.
     *
     * @return SecurityContext
     */
    private function getSecurityContext()
    {
        if (empty($this->securityContext)) {
            $this->securityContext = $this->container->get('security.context');
        }

        return $this->securityContext;
    }

    /**
     * Нормализует значение.
     *
     * @param mixed $value Исходное значение
     *
     * @return string
     */
    private function normalizeValue($value)
    {
        return $value;
    }
}
