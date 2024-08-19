<?php

namespace App\Entity;

use App\Repository\TownRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=TownRepository::class)
 */
class Town
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
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isForCalc = true;

    /**
     * @ORM\ManyToMany(targetEntity=Bank::class, mappedBy="towns")
     */
    private $banks;

    public function __construct()
    {
        $this->banks = new ArrayCollection();
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection|Bank[]
     */
    public function getBanks(): Collection
    {
        return $this->banks;
    }

    public function addBank(Bank $bank): self
    {
        if (!$this->banks->contains($bank)) {
            $this->banks[] = $bank;
            $bank->addTown($this);
        }

        return $this;
    }

    public function removeBank(Bank $bank): self
    {
        if ($this->banks->removeElement($bank)) {
            $bank->removeTown($this);
        }

        return $this;
    }

    public function __toString(){
        // to show the name of the Category in the select
        return $this->title;
        // to show the id of the Category in the select
        // return $this->id;
    }

    public function getIsForCalc(): ?bool
    {
        return $this->isForCalc;
    }

    public function setIsForCalc(bool $isForCalc): self
    {
        $this->isForCalc = $isForCalc;

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function isIsForCalc(): ?bool
    {
        return $this->isForCalc;
    }
}
