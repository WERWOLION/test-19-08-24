<?php

namespace App\EventSubscriber;


use App\Entity\Calculated;
use App\Service\bitrix24\BitrixService;
use App\Service\Logs\LogService;
use Psr\Log\LoggerInterface;
use App\Service\OfferService;
use App\Service\Wallet\WalletService;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class CalculatedListener
{
    private $logger;
    private $offerSerivce;
    private $walletService;
    private $bitrixService;

    public function __construct(
        LoggerInterface $testLogger,
        OfferService $offerSerivce,
        WalletService $walletService,
        BitrixService $bitrixService,
    ) {
        $this->offerSerivce = $offerSerivce;
        $this->logger = $testLogger;
        $this->walletService = $walletService;
        $this->bitrixService = $bitrixService;
    }
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $changeArray = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($args->getObject());

        if (!$entity instanceof Calculated) {
            return;
        }

        /**
         * Если статус 130 - то запускаем выплату партнеру
         */

        if ($entity->getStatus() === 130) {
            $this->bitrixService->updateReferalDealStage($entity->getOffer()->getUser()->getPartner(), 2, 3);
        }

        if ($entity->getStatus() === 130 && !$entity->getIsPayDone()) {
            $bonusStatus = $this->bitrixService->getStatusPersonalDeal($entity->getBitrixID());
            $this->offerSerivce->calcFinishCash($entity, $bonusStatus);
            return;
        }

        /**
         * Если изменился статус, то показываем сообщения об обновлении
         */
        // if(isset($changeArray['status']) && $changeArray['status'][0] >= 20){
        if (isset($changeArray['status'])) {
            $this->offerSerivce->processCalcStatusUpdate($entity, $changeArray['status'][0]);
        }
    }
}
