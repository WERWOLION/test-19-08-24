<?php

namespace App\Service\Wallet;
use App\Entity\User;
use App\Entity\Wallet;
use App\Entity\Transaction;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;


class WalletService
{
    private $em;
    private $walletRep;
    const BITRIX_WALLET = 3;

    public function __construct(
        EntityManagerInterface $em,
        WalletRepository $walletsRep
    ){
        $this->em = $em;
        $this->walletRep = $walletsRep;
    }


    public function createWallet(User $user)
    {
        if($user->getWallet()) throw new \Exception('Кошелек у пользователя ' .  $user->getId() . " уже создан");
        $newWallet = new Wallet();
        $user->setWallet($newWallet);
        $this->em->persist($user);
        $this->em->flush();
        return $newWallet;
    }


    public function addMoney(User $user, int $summ)
    {
        $adminWallet = $this->walletRep->find($this::BITRIX_WALLET);
        if(!$adminWallet) throw new \Exception('Кошелька сервиса не найдено');
        $wallet = $user->getWallet();
        if(!$wallet) throw new \Exception('У пользователя ' .  $user->getId() . " нет кошелька");

        $transaction = new Transaction();
        $transaction->setSenderWallet($adminWallet);
        $transaction->setReciverWallet($wallet);
        $transaction->setStatus(10);
        $transaction->setAmount($summ);
        $transaction->setMessage("Зачисление средств на счёт");

        $this->em->persist($transaction);
        $this->em->flush();
        $this->recalculateWallet($user->getWallet());
        return $user->getWallet();
    }


    public function recalculateWallet(Wallet $wallet)
    {
        $balance = 0;
        $plus = $wallet->getTransactionsPlus();
        foreach ($plus as $transact) {
            if ($transact->getStatus() !== 20) continue;
            $balance += $transact->getAmount();
        }
        $wallet->setBalance($balance);
        $this->em->persist($wallet);
        $this->em->flush();
    }

}
