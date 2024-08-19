<?php

namespace App\Entity;

use App\Repository\BankCheckRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BankCheckRepository::class)
 */
class BankCheck
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOn = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOn2doc = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOn2docMigrant = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOnMother = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOn2docMother = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOnNDFL = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOnBankForm = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOnSZIILS = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsOn(): ?bool
    {
        return $this->isOn;
    }

    public function setIsOn(bool $isOn): self
    {
        $this->isOn = $isOn;

        return $this;
    }

    public function getIsOn2doc(): ?bool
    {
        return $this->isOn2doc;
    }

    public function setIsOn2doc(bool $isOn2doc): self
    {
        $this->isOn2doc = $isOn2doc;

        return $this;
    }

    public function getIsOn2docMigrant(): ?bool
    {
        return $this->isOn2docMigrant;
    }

    public function setIsOn2docMigrant(bool $isOn2docMigrant): self
    {
        $this->isOn2docMigrant = $isOn2docMigrant;

        return $this;
    }

    public function getIsOnMother(): ?bool
    {
        return $this->isOnMother;
    }

    public function setIsOnMother(bool $isOnMother): self
    {
        $this->isOnMother = $isOnMother;

        return $this;
    }

    public function getIsOn2docMother(): ?bool
    {
        return $this->isOn2docMother;
    }

    public function setIsOn2docMother(bool $isOn2docMother): self
    {
        $this->isOn2docMother = $isOn2docMother;

        return $this;
    }

    public function getIsOnNDFL(): ?bool
    {
        return $this->isOnNDFL;
    }

    public function setIsOnNDFL(bool $isOnNDFL): self
    {
        $this->isOnNDFL = $isOnNDFL;

        return $this;
    }

    public function getIsOnSZIILS(): ?bool
    {
        return $this->isOnSZIILS;
    }

    public function setIsOnSZIILS(bool $isOnSZIILS): self
    {
        $this->isOnSZIILS = $isOnSZIILS;

        return $this;
    }

    public function getIsOnBankForm(): ?bool
    {
        return $this->isOnBankForm;
    }

    public function setIsOnBankForm(bool $isOnBankForm): self
    {
        $this->isOnBankForm = $isOnBankForm;

        return $this;
    }

    public function isIsOn(): ?bool
    {
        return $this->isOn;
    }

    public function isIsOn2doc(): ?bool
    {
        return $this->isOn2doc;
    }

    public function isIsOn2docMigrant(): ?bool
    {
        return $this->isOn2docMigrant;
    }

    public function isIsOnMother(): ?bool
    {
        return $this->isOnMother;
    }

    public function isIsOn2docMother(): ?bool
    {
        return $this->isOn2docMother;
    }

    public function isIsOnNDFL(): ?bool
    {
        return $this->isOnNDFL;
    }

    public function isIsOnBankForm(): ?bool
    {
        return $this->isOnBankForm;
    }
}
