<?php

namespace SmartInformationSystems\ObjectHistoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Измененившиеся поля объекта.
 *
 * @ORM\Entity
 * @ORM\Table(name="sis_object_history_detail")
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectHistoryDetail
{
    /**
     * Идентификатор.
     *
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Факт изменения объекта.
     *
     * @var ObjectHistory
     *
     * @ORM\ManyToOne(targetEntity="ObjectHistory", inversedBy="details")
     * @ORM\JoinColumn(name="object_history_id", referencedColumnName="id")
     */
    protected $objectHistory;

    /**
     * Имя поля.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255, name="field_name")
     */
    protected $fieldName;

    /**
     * Старое значение.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255, name="old_value", nullable=true)
     */
    protected $oldValue;

    /**
     * Новое значение.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255, name="new_value", nullable=true)
     */
    protected $newValue;

    /**
     * Дата создания.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;


    /**
     * Возвращает идентификатор.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Устанавливает название поля.
     *
     * @param string $fieldName Название поля
     *
     * @return ObjectHistoryDetail
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * Возвращает название поля.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Устанавливает старое значение поля.
     *
     * @param string $oldValue Старое значение поля
     *
     * @return ObjectHistoryDetail
     */
    public function setOldValue($oldValue)
    {
        if ($oldValue instanceof \DateTime) {
            $this->oldValue = $oldValue->format('r');
        } elseif (is_object($oldValue)) {
            $this->oldValue = 'OBJECT';
        } elseif (is_null($oldValue)) {
            $this->oldValue = NULL;
        } elseif (is_array($oldValue)) {
            $this->oldValue = serialize($oldValue);
        } else {
            $this->oldValue = (string)$oldValue;
        }

        return $this;
    }

    /**
     * Возвращает старое значение поля.
     *
     * @return string
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * Устанавливает новое значение поля.
     *
     * @param string $newValue Новое значение поля
     *
     * @return ObjectHistoryDetail
     */
    public function setNewValue($newValue)
    {
        if ($newValue instanceof \DateTime) {
            $this->newValue = $newValue->format('r');
        } elseif (is_object($newValue)) {
            $this->newValue = 'OBJECT';
        } elseif (is_null($newValue)) {
            $this->newValue = NULL;
        } elseif (is_array($newValue)) {
            $this->newValue = serialize($newValue);
        } else {
            $this->newValue = (string)$newValue;
        }

        return $this;
    }

    /**
     * Возвращает новое значение поля.
     *
     * @return string
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * Устанавливает дату создания записи.
     *
     * @param \DateTime $createdAt Дата создания записи
     *
     * @return ObjectHistoryDetail
     */
    private function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Возвращает дату создания записи.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Устанавливает факт изменения объекта.
     *
     * @param ObjectHistory $objectHistory Факт изменения объекта
     *
     * @return ObjectHistoryDetail
     */
    public function setObjectHistory(ObjectHistory $objectHistory)
    {
        $this->objectHistory = $objectHistory;

        return $this;
    }

    /**
     * Возвращает факт изменения объекта.
     *
     * @return ObjectHistory
     */
    public function getObjectHistory()
    {
        return $this->objectHistory;
    }

    /**
     * Автоматическая установка даты создания.
     *
     * @ORM\PrePersist
     */
    public function prePersistHandler()
    {
        $this->setCreatedAt(new \DateTime());
    }
}
