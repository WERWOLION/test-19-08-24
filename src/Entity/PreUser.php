<?php

namespace App\Entity;

use App\Repository\PreUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=PreUserRepository::class)
 */
class PreUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phone;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bitrixLeadId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acceptCode;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $tryCount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isConfirm = false;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $other = [];

    /**
     * @ORM\ManyToOne(targetEntity=EmployeeRefLink::class, inversedBy="preUsers")
     */
    private $employeeRefLink;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getBitrixLeadId(): ?int
    {
        return $this->bitrixLeadId;
    }

    public function setBitrixLeadId(?int $bitrixLeadId): self
    {
        $this->bitrixLeadId = $bitrixLeadId;

        return $this;
    }

    public function getAcceptCode(): ?string
    {
        return $this->acceptCode;
    }

    public function setAcceptCode(?string $acceptCode): self
    {
        $this->acceptCode = $acceptCode;

        return $this;
    }

    public function getTryCount(): ?int
    {
        return $this->tryCount;
    }

    public function setTryCount(?int $tryCount): self
    {
        $this->tryCount = $tryCount;

        return $this;
    }

    public function getOther(): ?array
    {
        return $this->other;
    }

    public function setOther(?array $other): self
    {
        $this->other = $other;

        return $this;
    }

    public function isIsConfirm(): ?bool
    {
        return $this->isConfirm;
    }

    public function setIsConfirm(?bool $isConfirm): self
    {
        $this->isConfirm = $isConfirm;

        return $this;
    }

    public function getEmployeeRefLink(): ?EmployeeRefLink
    {
        return $this->employeeRefLink;
    }

    public function setEmployeeRefLink(?EmployeeRefLink $employeeRefLink): self
    {
        $this->employeeRefLink = $employeeRefLink;

        return $this;
    }
}
