<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=WalletRepository::class)
 */
class Wallet
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $recalculatedAt;

    /**
     * @ORM\Column(type="float")
     */
    private $balance = 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $balanceReady = 0;

    /**
     * @ORM\OneToMany(targetEntity=MoneyRequest::class, mappedBy="wallet")
     */
    private $moneyRequests;

    public function getWithdrawalsSumm()
    {
        $summ = 0;
        foreach ($this->moneyRequests as $key => $request) {
            if($request->getStatus() !== 20) continue;
            $summ += $request->getAmount();
        }
        return $summ;
    }

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="senderWallet")
     */
    private $transactionsMinus;

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="reciverWallet")
     */
    private $transactionsPlus;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="wallet", cascade={"persist", "remove"})
     */
    private $userAccount;

    public function __construct()
    {
        $this->moneyRequests = new ArrayCollection();
        $this->transactionsMinus = new ArrayCollection();
        $this->transactionsPlus = new ArrayCollection();
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getUserAccount(): ?User
    {
        return $this->userAccount;
    }

    public function setUserAccount(?User $userAccount): self
    {
        // unset the owning side of the relation if necessary
        if ($userAccount === null && $this->userAccount !== null) {
            $this->userAccount->setWallet(null);
        }

        // set the owning side of the relation if necessary
        if ($userAccount !== null && $userAccount->getWallet() !== $this) {
            $userAccount->setWallet($this);
        }

        $this->userAccount = $userAccount;

        return $this;
    }

    public function getBalanceReady(): ?float
    {
        return $this->balanceReady;
    }

    public function setBalanceReady(?float $balanceReady): self
    {
        $this->balanceReady = $balanceReady;

        return $this;
    }

    /**
     * @return Collection<int, MoneyRequest>
     */
    public function getMoneyRequests(): Collection
    {
        return $this->moneyRequests;
    }

    public function addMoneyRequest(MoneyRequest $moneyRequest): self
    {
        if (!$this->moneyRequests->contains($moneyRequest)) {
            $this->moneyRequests->add($moneyRequest);
            $moneyRequest->setWallet($this);
        }

        return $this;
    }

    public function removeMoneyRequest(MoneyRequest $moneyRequest): self
    {
        if ($this->moneyRequests->removeElement($moneyRequest)) {
            // set the owning side to null (unless already changed)
            if ($moneyRequest->getWallet() === $this) {
                $moneyRequest->setWallet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionsMinus(): Collection
    {
        return $this->transactionsMinus;
    }

    public function addTransactionsMinu(Transaction $transactionsMinu): self
    {
        if (!$this->transactionsMinus->contains($transactionsMinu)) {
            $this->transactionsMinus->add($transactionsMinu);
            $transactionsMinu->setSenderWallet($this);
        }

        return $this;
    }

    public function removeTransactionsMinu(Transaction $transactionsMinu): self
    {
        if ($this->transactionsMinus->removeElement($transactionsMinu)) {
            // set the owning side to null (unless already changed)
            if ($transactionsMinu->getSenderWallet() === $this) {
                $transactionsMinu->setSenderWallet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionsPlus(): Collection
    {
        return $this->transactionsPlus;
    }

    public function addTransactionsPlu(Transaction $transactionsPlu): self
    {
        if (!$this->transactionsPlus->contains($transactionsPlu)) {
            $this->transactionsPlus->add($transactionsPlu);
            $transactionsPlu->setReciverWallet($this);
        }

        return $this;
    }

    public function removeTransactionsPlu(Transaction $transactionsPlu): self
    {
        if ($this->transactionsPlus->removeElement($transactionsPlu)) {
            // set the owning side to null (unless already changed)
            if ($transactionsPlu->getReciverWallet() === $this) {
                $transactionsPlu->setReciverWallet(null);
            }
        }

        return $this;
    }

    public function getRecalculatedAt(): ?\DateTimeInterface
    {
        return $this->recalculatedAt;
    }

    public function setRecalculatedAt(?\DateTimeInterface $recalculatedAt): self
    {
        $this->recalculatedAt = $recalculatedAt;

        return $this;
    }
}
