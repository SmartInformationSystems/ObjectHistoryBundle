<?php

namespace SmartInformationSystems\ObjectHistoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Факт изменения объекта.
 *
 * @ORM\Entity
 * @ORM\Table(name="sis_object_history", indexes={@ORM\Index(name="i_object_class_object_id", columns={"object_class", "object_id"})})
 * @ORM\HasLifecycleCallbacks()
 */
class ObjectHistory
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
     * Имя класса объекта.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255, name="object_class")
     */
    protected $objectClass;

    /**
     * Идентификатор объекта.
     *
     * @var int
     *
     * @ORM\Column(type="integer", name="object_id", options={"unsigned"=true})
     */
    protected $objectId;

    /**
     * Идентификатор текущего пользователя при изменении объекта.
     *
     * @var int
     *
     * @ORM\Column(type="integer", name="user_id", nullable=true, options={"unsigned"=true})
     */
    protected $userId;

    /**
     * Идентификатор текущего адмигистратора при изменении объекта.
     *
     * @var int
     *
     * @ORM\Column(type="integer", name="admin_id", nullable=true, options={"unsigned"=true})
     */
    protected $adminId;

    /**
     * Дата создания.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * Производители.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ObjectHistoryDetail", mappedBy="objectHistory")
     */
    protected $details;

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
     * Устанавливает имя класса объекта.
     *
     * @param string $objectClass Имя класса объекта
     *
     * @return ObjectHistory
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    /**
     * Возвращает имя класса объекта.
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Устанавливает идентификатор объекта.
     *
     * @param integer $objectId Идентификатор объекта
     *
     * @return ObjectHistory
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Возвращает идентификатор объекта.
     *
     * @return integer
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Устанавливает идентификатор текущего пользователя при изменении объекта.
     *
     * @param integer $userId Идентификатор пользователя
     *
     * @return ObjectHistory
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Возвращает идентификатор текущего пользователя при изменении объекта.
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Устанавливает идентификатор текущего администратора при изменении объекта.
     *
     * @param integer $adminId
     * @return ObjectHistory
     */
    public function setAdminId($adminId)
    {
        $this->adminId = $adminId;

        return $this;
    }

    /**
     * Возвращает идентификатор текущего администратора при изменении объекта.
     *
     * @return integer
     */
    public function getAdminId()
    {
        return $this->adminId;
    }

    /**
     * Устанавливает дату изменения.
     *
     * @param \DateTime $createdAt
     * @return ObjectHistory
     */
    private function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Возвращает дату изменения.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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

    /**
     * Добавляет поле.
     *
     * @param ObjectHistoryDetail $detail Изменение поля
     *
     * @return ObjectHistory
     */
    public function addDetail(ObjectHistoryDetail $detail)
    {
        $this->details[] = $detail;

        return $this;
    }

    /**
     * Удаляет поле.
     *
     * @param ObjectHistoryDetail $detail Изменение поля
     *
     * @return ObjectHistory
     */
    public function removeDetail(ObjectHistoryDetail $detail)
    {
        $this->details->removeElement($detail);

        return $this;
    }

    /**
     * Возвращает изменившиеся поля.
     *
     * @return ArrayCollection
     */
    public function getDetails()
    {
        return $this->details;
    }
}
