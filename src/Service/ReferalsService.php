<?php

namespace App\Service;

use App\Entity\Log;
use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Wallet;
use App\Entity\Partner;
use App\Entity\Calculated;
use App\Entity\Transaction;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Service\Logs\LogService;
use App\Service\Wallet\WalletService;

class ReferalsService
{
    public function __construct(
        private PartnerRepository $partnerRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository,
        private LogService $logService,
        private WalletService $walletService,
    ) {
    }

    public function getReferalLink(Partner $partner): string
    {
        return "https://lk.ipoteka.life/sec/rf{$partner->getId()}";
    }

    public function createReferal(Partner $master, Partner $slave): Partner
    {
        $slave->setMyReferal($master);
        $this->em->persist($slave);
        $newTransaction = new Transaction();
        $newTransaction->setAmount(10000);
        $newTransaction->setType(10);
        $newTransaction->setStatus(10);
        $newTransaction->setMessage("Начисление бонуса партнеру №{$master->getId()}: {$master->getUser()->getFio()} за реферала №{$slave->getId()} - {$slave->getUser()->getFio()} [предварительно]");
        $newTransaction->setSenderWallet($slave->getUser()->getWallet());
        $newTransaction->setReciverWallet($master->getUser()->getWallet());
        $this->em->persist($newTransaction);
        $this->em->flush();
        return $master;
    }

    public function getCurrTransaction($partner, $referal)
    {
        try {
            if (!isset($partner) || !isset($referal)) {
                return false;
            }

            $partnerWallet = $partner->getUser()->getWallet();
            $referalWallet = $referal->getUser()->getWallet();

            $transactions = $this->transactionRepository->findBy([
                'type' => 10,
                'reciverWallet' => $referalWallet->getId(),
                'senderWallet' => $partnerWallet->getId()
            ]);

            return end($transactions);
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getHoldReferalSumm(Partner $partner): array
    {
        $wallet = $partner->getUser()->getWallet();

        $transactions = $this->transactionRepository->findBy([
            'type' => 10,
            'status' => 10,
            'reciverWallet' => $wallet->getId(),
        ]);
        $count = count($transactions);
        $summ = array_sum(array_map(fn (Transaction $transaction) => $transaction->getAmount(), $transactions));
        return [
            'count' => $count,
            'summ' => $summ,
        ];
    }

    public function finishRegeralBonusForOffer(Calculated $calculated): ?Wallet
    {
        if ($calculated->getIsPayDone()) {
            throw new \Error('Ошибка. По заявке уже был выплачен бонус рефералу');
        }
        $calculatedUser = $calculated->getOffer()->getUser();
        $userOwner = $calculatedUser->getPartner()->getMyReferal();
        if (!$userOwner) {
            $log = $this->logService->calcLog($calculated->getId(), "У партнера {$calculated->getOffer()->getUser()->getFio()} нет реферала, бонус за завершение заявки не начислен никому", false);
            $this->em->persist($log);
            return null;
        }
        $transaction = $this->transactionRepository->findOneBy([
            'type' => 10,
            'status' => 10,
            'reciverWallet' => $userOwner->getUser()->getWallet()->getId(),
            'senderWallet' => $calculatedUser->getWallet()->getId(),
        ]);
        if (!$transaction) {
            $log = $this->logService->calcLog($calculated->getId(), "Ошибка. Не найдена транзакция начисления бонуса за реферала для её подтверждения для {$userOwner->getUser()->getFio()} за реферала {$calculatedUser->getFio()}", false);
            $this->em->persist($log);
            return null;
        }
        $transaction->setStatus(20);
        $this->em->persist($transaction);
        return $userOwner->getUser()->getWallet();
    }
}
