<?php

namespace App\EventListener;

use App\Entity\Calculated;
use Doctrine\ORM\Events;
use App\Entity\MoneyRequest;
use App\Service\Wallet\WalletService;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;

class EntitiesChangeListener implements EventSubscriberInterface
{

    public function __construct(
        private WalletService $walletService
    ){}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->updateWallet($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->updateWallet($args);
        $this->testCalculatedDelete($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->updateWallet($args);
    }

    public function updateWallet(LifecycleEventArgs $args)
    {
        $moneyRequest = $args->getObject();
        if (!$moneyRequest instanceof MoneyRequest) return;
        // $changeArray = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($args->getObject());
        if ($moneyRequest->getStatus() !== 10) return;
        $wallet = $moneyRequest->getWallet();
        $wallet->setBalanceReady($wallet->getBalanceReady() - $moneyRequest->getAmount());
        $moneyRequest->setStatus(20);
        $args->getObjectManager()->persist($moneyRequest);
        $args->getObjectManager()->persist($wallet);
        $args->getObjectManager()->flush();
    }

    public function testCalculatedDelete(LifecycleEventArgs $args)
    {
        // $em = $args->getObjectManager();
        // $calculated = $args->getObject();
        // if (!$calculated instanceof Calculated) return;
        // $offer = $calculated->getOffer();
        // if (!$offer) return;
        // if ($offer->getCalculateds()->isEmpty()) {
        //     $em->remove($offer);
        //     $em->flush();
        // }
    }
}
