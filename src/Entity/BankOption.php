<?php

namespace App\Entity;

use App\Repository\BankOptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BankOptionRepository::class)
 */
class BankOption
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkFlat;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkHome;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkTynehouse;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkRoom;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkLastroom;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkApartments;




    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procFlat;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procFlatNew;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procSocial;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procFamily;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procWar;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procIt;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procHome;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procTynehouse;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procRoom;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procLastRoom;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procApartments;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procKn;



    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstFlat;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstFlatNew;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstSocial;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstFamily;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstWar;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstIt;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstHome;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstTynehouse;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstRoom;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstLastRoom;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstApartments;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstKn;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkKn;

    /**
     * @ORM\OneToOne(targetEntity=BankCheck::class, cascade={"persist", "remove"})
     */
    private $checkIjs;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procIjs;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procIjsSocial;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procIjsFamily;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $procIjsIt;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstIjs;

    /**
     * @ORM\OneToOne(targetEntity=BankNum::class, cascade={"persist", "remove"})
     */
    private $firstIjsSocialFamilyIt;

    public function __construct()
    {
        $this->procFlat = new BankNum();
        $this->procFlatNew = new BankNum();
        $this->procSocial = new BankNum();
        $this->procFamily = new BankNum();
        $this->procWar = new BankNum();
        $this->procIt = new BankNum();
        $this->procHome = new BankNum();
        $this->procTynehouse = new BankNum();
        $this->procRoom = new BankNum();
        $this->procLastRoom = new BankNum();
        $this->procApartments = new BankNum();
        $this->procKn = new BankNum();
        $this->procIjs = new BankNum();
        $this->procIjsSocial = new BankNum();
        $this->procIjsFamily = new BankNum();
        $this->procIjsIt = new BankNum();
        $this->firstFlat = new BankNum();
        $this->firstFlatNew = new BankNum();
        $this->firstSocial = new BankNum();
        $this->firstFamily = new BankNum();
        $this->firstWar = new BankNum();
        $this->firstIt = new BankNum();
        $this->firstHome = new BankNum();
        $this->firstTynehouse = new BankNum();
        $this->firstRoom = new BankNum();
        $this->firstLastRoom = new BankNum();
        $this->firstApartments = new BankNum();
        $this->firstKn = new BankNum();
        $this->firstIjs = new BankNum();
        $this->firstIjsSocialFamilyIt = new BankNum();
        $this->checkFlat = new BankCheck();
        $this->checkHome = new BankCheck();
        $this->checkTynehouse = new BankCheck();
        $this->checkRoom = new BankCheck();
        $this->checkLastroom = new BankCheck();
        $this->checkApartments = new BankCheck();
        $this->checkKn = new BankCheck();
        $this->checkIjs = new BankCheck();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCheckFlat(): ?BankCheck
    {
        return $this->checkFlat;
    }

    public function setCheckFlat(BankCheck $checkFlat): self
    {
        $this->checkFlat = $checkFlat;

        return $this;
    }

    public function getCheckHome(): ?BankCheck
    {
        return $this->checkHome;
    }

    public function setCheckHome(BankCheck $checkHome): self
    {
        $this->checkHome = $checkHome;

        return $this;
    }

    public function getCheckTynehouse(): ?BankCheck
    {
        return $this->checkTynehouse;
    }

    public function setCheckTynehouse(BankCheck $checkTynehouse): self
    {
        $this->checkTynehouse = $checkTynehouse;

        return $this;
    }

    public function getCheckRoom(): ?BankCheck
    {
        return $this->checkRoom;
    }

    public function setCheckRoom(BankCheck $checkRoom): self
    {
        $this->checkRoom = $checkRoom;

        return $this;
    }

    public function getCheckLastroom(): ?BankCheck
    {
        return $this->checkLastroom;
    }

    public function setCheckLastroom(BankCheck $checkLastroom): self
    {
        $this->checkLastroom = $checkLastroom;

        return $this;
    }

    public function getCheckKn(): ?BankCheck
    {
        return $this->checkKn;
    }

    public function setChecKn(BankCheck $checkKn): self
    {
        $this->checkKn = $checkKn;

        return $this;
    }

    public function getCheckApartments(): ?BankCheck
    {
        return $this->checkApartments;
    }

    public function setCheckApartments(BankCheck $checkApartments): self
    {
        $this->checkApartments = $checkApartments;

        return $this;
    }

    public function getProcFlat(): ?BankNum
    {
        return $this->procFlat;
    }

    public function setProcFlat(BankNum $procFlat): self
    {
        $this->procFlat = $procFlat;

        return $this;
    }

    public function getProcFlatNew(): ?BankNum
    {
        return $this->procFlatNew;
    }

    public function setProcFlatNew(BankNum $procFlatNew): self
    {
        $this->procFlatNew = $procFlatNew;

        return $this;
    }

    public function getProcSocial(): ?BankNum
    {
        return $this->procSocial;
    }

    public function setProcSocial(BankNum $procSocial): self
    {
        $this->procSocial = $procSocial;

        return $this;
    }

    public function getProcFamily(): ?BankNum
    {
        return $this->procFamily;
    }

    public function setProcFamily(BankNum $procFamily): self
    {
        $this->procFamily = $procFamily;

        return $this;
    }

    public function getProcWar(): ?BankNum
    {
        return $this->procWar;
    }

    public function setProcWar(BankNum $procWar): self
    {
        $this->procWar = $procWar;

        return $this;
    }

    public function getProcHome(): ?BankNum
    {
        return $this->procHome;
    }

    public function setProcHome(BankNum $procHome): self
    {
        $this->procHome = $procHome;

        return $this;
    }

    public function getProcTynehouse(): ?BankNum
    {
        return $this->procTynehouse;
    }

    public function setProcTynehouse(BankNum $procTynehouse): self
    {
        $this->procTynehouse = $procTynehouse;

        return $this;
    }

    public function getProcRoom(): ?BankNum
    {
        return $this->procRoom;
    }

    public function setProcRoom(BankNum $procRoom): self
    {
        $this->procRoom = $procRoom;

        return $this;
    }

    public function getProcLastRoom(): ?BankNum
    {
        return $this->procLastRoom;
    }

    public function setProcLastRoom(BankNum $procLastRoom): self
    {
        $this->procLastRoom = $procLastRoom;

        return $this;
    }

    public function getProcApartments(): ?BankNum
    {
        return $this->procApartments;
    }

    public function setProcApartments(BankNum $procApartments): self
    {
        $this->procApartments = $procApartments;

        return $this;
    }

    public function getProcKn(): ?BankNum
    {
        return $this->procKn;
    }

    public function setProcKn(BankNum $procKn): self
    {
        $this->procKn = $procKn;

        return $this;
    }

    public function getFirstFlat(): ?BankNum
    {
        return $this->firstFlat;
    }

    public function setFirstFlat(BankNum $firstFlat): self
    {
        $this->firstFlat = $firstFlat;

        return $this;
    }

    public function getFirstFlatNew(): ?BankNum
    {
        return $this->firstFlatNew;
    }

    public function setFirstFlatNew(BankNum $firstFlatNew): self
    {
        $this->firstFlatNew = $firstFlatNew;

        return $this;
    }

    public function getFirstSocial(): ?BankNum
    {
        return $this->firstSocial;
    }

    public function setFirstSocial(BankNum $firstSocial): self
    {
        $this->firstSocial = $firstSocial;

        return $this;
    }

    public function getFirstFamily(): ?BankNum
    {
        return $this->firstFamily;
    }

    public function setFirstFamily(BankNum $firstFamily): self
    {
        $this->firstFamily = $firstFamily;

        return $this;
    }

    public function getFirstWar(): ?BankNum
    {
        return $this->firstWar;
    }

    public function setFirstWar(BankNum $firstWar): self
    {
        $this->firstWar = $firstWar;

        return $this;
    }

    public function getFirstHome(): ?BankNum
    {
        return $this->firstHome;
    }

    public function setFirstHome(BankNum $firstHome): self
    {
        $this->firstHome = $firstHome;

        return $this;
    }

    public function getFirstTynehouse(): ?BankNum
    {
        return $this->firstTynehouse;
    }

    public function setFirstTynehouse(BankNum $firstTynehouse): self
    {
        $this->firstTynehouse = $firstTynehouse;

        return $this;
    }

    public function getFirstRoom(): ?BankNum
    {
        return $this->firstRoom;
    }

    public function setFirstRoom(BankNum $firstRoom): self
    {
        $this->firstRoom = $firstRoom;

        return $this;
    }

    public function getFirstLastRoom(): ?BankNum
    {
        return $this->firstLastRoom;
    }

    public function setFirstLastRoom(BankNum $firstLastRoom): self
    {
        $this->firstLastRoom = $firstLastRoom;

        return $this;
    }

    public function getFirstApartments(): ?BankNum
    {
        return $this->firstApartments;
    }

    public function setFirstApartments(BankNum $firstApartments): self
    {
        $this->firstApartments = $firstApartments;

        return $this;
    }

    public function getFirstKn(): ?BankNum
    {
        return $this->firstKn;
    }

    public function setFirstKn(BankNum $firstKn): self
    {
        $this->firstKn = $firstKn;

        return $this;
    }

    public function getProcIt(): ?BankNum
    {
        return $this->procIt;
    }

    public function setProcIt(?BankNum $procIt): self
    {
        $this->procIt = $procIt;

        return $this;
    }

    public function getFirstIt(): ?BankNum
    {
        return $this->firstIt;
    }

    public function setFirstIt(?BankNum $firstIt): self
    {
        $this->firstIt = $firstIt;

        return $this;
    }


    public function getFirstIjs(): ?BankNum
    {
        return $this->firstIjs;
    }

    public function setFirstIjs(?BankNum $firstIjs): self
    {
        $this->firstIjs = $firstIjs;

        return $this;
    }

    public function getProcIjs(): ?BankNum
    {
        return $this->procIjs;
    }

    public function setProcIjs(?BankNum $procIjs): self
    {
        $this->procIjs = $procIjs;

        return $this;
    }

    public function getCheckIjs(): ?BankCheck
    {
        return $this->checkIjs;
    }

    public function setCheckIjs(?BankCheck $checkIjs): self
    {
        $this->checkIjs = $checkIjs;

        return $this;
    }

    public function getFirstIjsSocialFamilyIt(): ?BankNum
    {
        return $this->firstIjsSocialFamilyIt;
    }

    public function setFirstIjsSocialFamilyIt(?BankNum $firstIjs): self
    {
        $this->firstIjsSocialFamilyIt = $firstIjs;

        return $this;
    }

    public function getProcIjsSocial(): ?BankNum
    {
        return $this->procIjsSocial;
    }

    public function setProcIjsSocial(?BankNum $procIjs): self
    {
        $this->procIjsSocial = $procIjs;

        return $this;
    }

    public function getProcIjsFamily(): ?BankNum
    {
        return $this->procIjsFamily;
    }

    public function setProcIjsFamily(?BankNum $procIjs): self
    {
        $this->procIjsFamily = $procIjs;

        return $this;
    }

    public function getProcIjsIt(): ?BankNum
    {
        return $this->procIjsIt;
    }

    public function setProcIjsIt(?BankNum $procIjs): self
    {
        $this->procIjsIt = $procIjs;

        return $this;
    }
}
