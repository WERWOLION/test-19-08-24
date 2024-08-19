<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
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
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $type = 0;

    const TRANSACTION_TYPES = [
        'Начислено за заявку' => 0,
        'Начислено за рефералов' => 10,
        'Вывод средств' => 20,
    ];

    /**
     * @ORM\Column(type="float")
     */
    private $amount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $status = 0;

    const TRANSACTION_STATUS = [
        'Черновик' => 0,
        'На утверждении' => 10,
        'Зачтена' => 20,
        'Уже выплачена' => 30,
    ];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity=Wallet::class, inversedBy="transactionsMinus")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $senderWallet;

    /**
     * @ORM\ManyToOne(targetEntity=Wallet::class, inversedBy="transactionsPlus")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $reciverWallet;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getSenderWallet(): ?Wallet
    {
        return $this->senderWallet;
    }

    public function setSenderWallet(?Wallet $senderWallet): self
    {
        $this->senderWallet = $senderWallet;

        return $this;
    }

    public function getReciverWallet(): ?Wallet
    {
        return $this->reciverWallet;
    }

    public function setReciverWallet(?Wallet $reciverWallet): self
    {
        $this->reciverWallet = $reciverWallet;

        return $this;
    }
}
