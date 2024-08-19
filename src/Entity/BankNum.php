<?php

namespace App\Entity;

use App\Repository\BankNumRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BankNumRepository::class)
 */
class BankNum
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $NDFL;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $bankForm;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $migrantNDFL;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $migrantBankForm;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $on2doc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $migrant2doc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $supportHome;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $supportHome2doc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $SZIILS;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $migrantSZIILS;


    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $business;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $migrantBusiness;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $business2doc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $migrantBusiness2doc;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSZIILS(): ?float
    {
        return $this->SZIILS;
    }

    public function setSZIILS(?float $SZIILS): self
    {
        $this->SZIILS = $SZIILS;

        return $this;
    }

    public function getNDFL(): ?float
    {
        return $this->NDFL;
    }

    public function setNDFL(?float $NDFL): self
    {
        $this->NDFL = $NDFL;

        return $this;
    }

    public function getBankForm(): ?float
    {
        return $this->bankForm;
    }

    public function setBankForm(?float $bankForm): self
    {
        $this->bankForm = $bankForm;

        return $this;
    }

    public function getMigrantSZIILS(): ?float
    {
        return $this->migrantSZIILS;
    }

    public function setMigrantSZIILS(?float $migrantSZIILS): self
    {
        $this->migrantSZIILS = $migrantSZIILS;

        return $this;
    }

    public function getMigrantNDFL(): ?float
    {
        return $this->migrantNDFL;
    }

    public function setMigrantNDFL(?float $migrantNDFL): self
    {
        $this->migrantNDFL = $migrantNDFL;

        return $this;
    }

    public function getMigrantBankForm(): ?float
    {
        return $this->migrantBankForm;
    }

    public function setMigrantBankForm(?float $migrantBankForm): self
    {
        $this->migrantBankForm = $migrantBankForm;

        return $this;
    }

    public function getOn2doc(): ?float
    {
        return $this->on2doc;
    }

    public function setOn2doc(?float $on2doc): self
    {
        $this->on2doc = $on2doc;

        return $this;
    }

    public function getMigrant2doc(): ?float
    {
        return $this->migrant2doc;
    }

    public function setMigrant2doc(?float $migrant2doc): self
    {
        $this->migrant2doc = $migrant2doc;

        return $this;
    }

    public function getSupportHome(): ?float
    {
        return $this->supportHome;
    }

    public function setSupportHome(?float $supportHome): self
    {
        $this->supportHome = $supportHome;

        return $this;
    }

    public function getSupportHome2doc(): ?float
    {
        return $this->supportHome2doc;
    }

    public function setSupportHome2doc(?float $supportHome2doc): self
    {
        $this->supportHome2doc = $supportHome2doc;

        return $this;
    }

    public function getBusiness(): ?float
    {
        return $this->business;
    }

    public function setBusiness(?float $business): self
    {
        $this->business = $business;

        return $this;
    }

    public function getMigrantBusiness(): ?float
    {
        return $this->migrantBusiness;
    }

    public function setMigrantBusiness(?float $business): self
    {
        $this->migrantBusiness = $business;

        return $this;
    }

    public function getBusiness2doc(): ?float
    {
        return $this->business2doc;
    }

    public function setBusiness2doc(?float $business): self
    {
        $this->business2doc = $business;

        return $this;
    }

    public function getMigrantBusiness2doc(): ?float
    {
        return $this->migrantBusiness2doc;
    }

    public function setMigrantBusiness2doc(?float $business): self
    {
        $this->migrantBusiness2doc = $business;

        return $this;
    }


}
