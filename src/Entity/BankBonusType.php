<?php

namespace App\Entity;

use App\Repository\BankBonusTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BankBonusTypeRepository::class)
 */
class BankBonusType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="float")
     */
    private $percent;

    /**
     * @ORM\ManyToOne(targetEntity=BankMain::class, inversedBy="bankBonusTypes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bank;

    public function __toString(): string
    {
        return $this->bank->getTitle() . ' ' . $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getBankId(): ?int
    {
        return $this->bank_id;
    }

    public function setBankId(int $bank_id): self
    {
        $this->bank_id = $bank_id;

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

    public function getPercent(): ?float
    {
        return $this->percent;
    }

    public function setPercent(float $percent): self
    {
        $this->percent = $percent;

        return $this;
    }

    public function getBank(): ?BankMain
    {
        return $this->bank;
    }

    public function setBank(?BankMain $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

}
