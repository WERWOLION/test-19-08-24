<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Serializable;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Repository\SavedcontactRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=SavedcontactRepository::class)
 */
class Savedcontact
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $middlename;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pasportSeries;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pasportNum;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $pasportDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pasportCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pasportDescript;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="savedContacts")
     * @Ignore()
     */
    private $creator;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $birthDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $passportAddress;

    /**
     * @ORM\Column(type="boolean")
     */
    private $accessPermission = true;

    public function __construct()
    {
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getMiddlename(): ?string
    {
        return $this->middlename;
    }

    public function setMiddlename(?string $middlename): self
    {
        $this->middlename = $middlename;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPasportSeries(): ?string
    {
        return $this->pasportSeries;
    }

    public function setPasportSeries(string $pasportSeries): self
    {
        $this->pasportSeries = $pasportSeries;

        return $this;
    }

    public function getPasportNum(): ?string
    {
        return $this->pasportNum;
    }

    public function setPasportNum(string $pasportNum): self
    {
        $this->pasportNum = $pasportNum;

        return $this;
    }

    public function getPasportDate(): ?string
    {
        return $this->pasportDate;
    }

    public function setPasportDate(string $pasportDate): self
    {
        $this->pasportDate = $pasportDate;

        return $this;
    }

    public function getPasportCode(): ?string
    {
        return $this->pasportCode;
    }

    public function setPasportCode(?string $pasportCode): self
    {
        $this->pasportCode = $pasportCode;

        return $this;
    }

    public function getPasportDescript(): ?string
    {
        return $this->pasportDescript;
    }

    public function setPasportDescript(?string $pasportDescript): self
    {
        $this->pasportDescript = $pasportDescript;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function setBirthDate(?string $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPassportAddress(): ?string
    {
        return $this->passportAddress;
    }

    public function setPassportAddress(?string $passportAddress): self
    {
        $this->passportAddress = $passportAddress;

        return $this;
    }

    public function getAccessPermission(): ?bool
    {
        return $this->accessPermission;
    }

    public function setAccessPermission(bool $accessPermission): self
    {
        $this->accessPermission = $accessPermission;

        return $this;
    }

    public function isAccessPermission(): ?bool
    {
        return $this->accessPermission;
    }

}
