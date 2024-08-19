<?php

namespace App\Service;

use App\Entity\BankBonusType;
use App\Entity\Log;
use App\Entity\User;
use App\Entity\Offer;
use Dadata\DadataClient;
use App\Entity\Calculated;
use App\Repository\BankRepository;
use App\Repository\OfferRepository;
use App\Service\SiteSettingsFinder;
use App\Service\Banks\BanksCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use App\Repository\SitesettingsRepository;
use Symfony\Component\Security\Core\Security;
use App\Controller\Admin\CalculatedCrudController;
use App\Entity\BankMain;
use App\Repository\AttachmentRepository;
use App\Repository\BankMainRepository;
use App\Service\Calculator\CalculatorService;
use App\Service\Logs\LogService;
use App\Service\Wallet\WalletService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\VarDumper\VarDumper;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class OfferService
{
    private $em;
    private $offerRep;
    private $urlgen;
    private $settsRep;
    private $settsFinder;
    private $adminUrlGenerator;
    private $security;
    private $bankRep;
    private $bankMainRep;
    private $banksCalculator;
    private $calculatorService;

    public function __construct(
        EntityManagerInterface $em,
        OfferRepository $offerRep,
        UrlGeneratorInterface $urlgen,
        SitesettingsRepository $sitesettsRep,
        SiteSettingsFinder $siteSettingsFinder,
        AdminUrlGenerator $adminUrlGenerator,
        Security $security,
        BankRepository $bankRepository,
        BanksCalculator $banksCalculator,
        BankMainRepository $bankMainRepository,
        CalculatorService $calculatorService,
        private LogService $logService,
        private ReferalsService $referalsService,
        private WalletService $walletService,
        private AttachmentRepository $attachmentRepository,
        private UploaderHelper $uploaderHelper,
    ) {
        $this->em = $em;
        $this->offerRep = $offerRep;
        $this->urlgen = $urlgen;
        $this->settsRep = $sitesettsRep;
        $this->settsFinder = $siteSettingsFinder;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->security = $security;
        $this->bankRep = $bankRepository;
        $this->banksCalculator = $banksCalculator;
        $this->bankMainRep = $bankMainRepository;
        $this->calculatorService = $calculatorService;
    }

    /**
     * Находит число заявок партнера $me на заёмщика по номеру телефона заёмщика $phone
     */
    public function fetchUserBuyerOffers(User $me, string $phone): array
    {
        $counter = 0;
        $returnArray = [];
        $offers = $this->offerRep->findByBuyerPhone($me, $phone);
        foreach ($offers as $key => $offer) {
            if ($offer->getCalculateds()) {
                $counter += count($offer->getCalculateds());
                foreach ($offer->getCalculateds() as $keycalc => $calc) {
                    if ($calc->getStatus() < 20 || $calc->getStatus() > 120) continue;
                    $returnArray[] = $calc;
                }
            }
        }
        return $returnArray;
    }

    /**
     * Создаёт ссылки на заявки. Необходимо для сообщения об ошибке, когда
     * заявок больше 2-х штук
     */
    public function generateLinksToOffers(array $calculateds): string
    {
        $linksArr = [];
        foreach ($calculateds as $calc) {
            $linksArr[] = '<a target="_blank" href="' . $this->urlgen->generate('suboffer_show', ['id' => $calc->getId()]) . '">№' .
                $calc->getOffer()->getId() . '-' . $calc->getId() . '</a>';
        }
        return implode(" и ", $linksArr);
    }



    /**
     * Определяет изменение статуса заявки и подставляет сообщения об изменении
     */
    public function processCalcStatusUpdate(Calculated $calc, int $oldStatus)
    {
        $newStatus = $calc->getStatus();
        if ($newStatus === $oldStatus) return;

        $defaultMessage = "<div class='offer__popstat'>«" . array_search($oldStatus, Offer::OFFER_STATUS) . "» → «" . array_search($newStatus, Offer::OFFER_STATUS) . "»</div>";

        $textMess = $this->settsFinder->get("offer_message_status-" . $newStatus);
        if ($textMess) {
            $defaultMessage .= "<div class='offer__popmess'>" . $textMess . "</div>";
        }

        if ($newStatus < 0) {
            $notTextMess = $this->settsFinder->get("offer_message_status-NOT");
            $defaultMessage .= "<div class='offer__popmess'>" . $notTextMess . "</div>";
        }

        $message = $defaultMessage;
        $calc->setNewEventType(1);
        $calc->setNewEventMessage($message);
        $this->em->persist($calc);
        $this->em->flush();
    }


    /**
     * Удаление под-заявки с проверкой остались ли другие подзаявки
     * Если нет, то удаление остатков
     */
    public function calcDelete(Calculated $calc)
    {
        $offer = $calc->getOffer();
        $this->em->remove($calc);
        $this->em->flush();
        if ($offer->getCalculateds()->isEmpty()) {
            $this->em->remove($offer);
            $this->em->flush();
        }
    }



    /**
     * Запись суммы выданного кредита на партнера, когда сделка закрывается для последующего расчёта бонусов
     */
    public function calcFinishCash(Calculated $calculated, $bonusStatus)
    {
        $partner = $calculated->getOffer()->getUser()->getPartner();
        if ($calculated->getIsPayDone()) {
            throw new \Exception("По заявке уже было выплачено вознаграждение");
        }

        $bonus = $this->calcGetBonus($calculated);

        if ($bonusStatus) {
            $bonus = 0;
        }

        $resultSumm = $partner->getTotalsumm() + $calculated->getTruefullsumm();

        $bonusHistory = $partner->getBonusHistory();
        $bonusHistory[] = [
            'summ' => $calculated->getTruefullsumm(),
            'bank' => $calculated->getOther()['bankId'] ? $calculated->getOther()['bankId'] : 'Ошибка',
            'bonus' => $bonus,
            'bankTitle' => $calculated->getOther()['bankName'] ? $calculated->getOther()['bankName'] : 'Ошибка',
        ];

        $partner->setTotalsumm($resultSumm);
        $partner->setBonusHistory($bonusHistory);

        $log = new Log();
        $log->setEntityType('Calculated');
        $log->setEntityId($calculated->getId());
        $log->setTitle("Начисление за сделку {$calculated->getId()}");
        $log->setUser($calculated->getOffer()->getUser());
        $log->setContent('Вам начислено вознагражение за сделку №' . $calculated->getId() . ' в размере ' . $bonus . "руб.");

        $message = "Сделка завешена. Ваше вознаграждение за сделку составило " . $bonus . " руб.";
        $calculated->setNewEventType(1);
        $calculated->setNewEventMessage($message);

        $wallet = $this->referalsService->finishRegeralBonusForOffer($calculated);
        $calculated->setIsPayDone(true);
        $this->em->persist($calculated);
        $this->em->persist($partner);
        $this->em->persist($log);
        $this->em->flush();

        if ($wallet) $this->walletService->recalculateWallet($wallet);
        return $resultSumm;
    }


    /**
     * Высчитывает и возвращает бонус заявки в рублях
     */
    public function calcGetBonus(Calculated $calculated): int
    {
        $summ = $calculated->getFullsumm();
        if ($calculated->getStatus() >= 60 && $calculated->getTruefullsumm()) {
            $summ = $calculated->getTruefullsumm();
        }
        $bankMain = $this->bankMainRep->find($calculated->getOther()['bankId']);
        // TODO: убрать при удалении старых банков
        if (isset($calculated->getOther()['version']) && $calculated->getOther()['version'] === 2) {
            $bankProc = $this->getOfferBonusProcent($calculated->getOffer(), $bankMain);
        } else {
            $bankProc = $calculated->getBank()->getBonusProcent();
        }
        //бонус из динамической ставки
        $other = $calculated->getOther();
        if ($other['dinamic']){
            $bankProc = $other['dinamic']['feePercent'];
        }
        if ($findNewBankPercentLogic = $this->getBankBonusPercent($calculated->getOffer(), $bankMain)) {
            $bankProc = $findNewBankPercentLogic;
        }
        $bonus = ($summ * $bankProc) / 100;
        return intval(round($bonus, 0, PHP_ROUND_HALF_UP));
    }


    /** Высчитывает процент бонуса в зависимости от типа заявки и банка */
    public function getOfferBonusProcent(Offer $offer, BankMain $bankMain): float
    {
        if ($percent = $this->getBankBonusPercent($offer, $bankMain)) {
            return $percent;
        }
        if ($bankMain->getTitle() === 'СГБ' && $offer->getTown()->getTitle() == 'Самарская область') {
            return 0.3;
        }
        /** если гос. поддержка
            "Льготная ипотека" => 15664,
            "Семейная ипотека" => 15666,
            "Военная ипотека" => 15668,
            IT-ипотека => 15956
         */
        if (in_array($offer->getStateSupport(), [15664, 15666, 15956]) || $offer->getIsMilitaryMortgage()) {

            return $bankMain->getBonusStateSupport();
        }
        if ($offer->getCreditTarget() == 'залог') {
            return $bankMain->getBonusPledge();
        }
        return $bankMain->getBonusProcent();
    }

    /**
     * Новая логика высчитывания КВ для партнёра, через BankBonusType
     *
     * @param Offer $offer
     * @param BankMain $bankMain
     * @return float|null возвращает либо бонусный процент, либо null
     */
    private function getBankBonusPercent(Offer $offer, BankMain $bankMain): ?float
    {
        $bankBonusRepository = $this->em->getRepository(BankBonusType::class);
        if ($offer->getCreditTarget() == 'ипотека') {
            switch ($offer->getSalerType()) {
                case 'физ_по_дкп': // Вторичка, у вторички не бывает господдержки
                case 'юр_по_дкп':
                    if ($bankMain->getTitle() == 'Альфа Банк') {
                        if ($offer->getObjectType() == 'кн') {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_commerce']))?->getPercent();
                        }
                        if (in_array($offer->getTown()->getTitle(), ['Московская область', 'Ленинградская область', 'Москва',
                            'Санкт-Петербург', 'Краснодарский край'])) {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_base_gotovoe_jile_ms_spb']))?->getPercent();
                        } else {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_base_gotovoe_jile_other_regions']))?->getPercent();
                        }
                    }
                case 'ижс':
                case 'застр_по_дду':
                case 'юр_по_дупт':
                case 'физ_по_дупт':
                    if ($bankMain->getTitle() == 'Альфа Банк') {
                        if ($offer->getObjectType() == 'ижс' && $offer->getStateSupport() != 15662) {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_lgotnoe_igs']))?->getPercent();
                        }
                        if ($offer->getObjectType() == 'ижс' && $offer->getStateSupport() == 15662
                            && in_array($offer->getTown()->getTitle(), ['Московская область', 'Ленинградская область', 'Москва',
                                'Санкт-Петербург', 'Краснодарский край'])) {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_igs_bes_gos_ms_spb']))?->getPercent();
                        } else if ($offer->getObjectType() == 'ижс' && $offer->getStateSupport() == 15662) {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_igs_bes_gos_regions']))?->getPercent();
                        }
                        if ($offer->getObjectType() == 'кн') {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_commerce']))?->getPercent();
                        }
                        if ((in_array($offer->getTown()->getTitle(), ['Московская область', 'Ленинградская область', 'Москва',
                            'Санкт-Петербург', 'Краснодарский край']) && $offer->getStateSupport() == 15662)
                        || (in_array($offer->getTown()->getTitle(), ['Московская область', 'Москва']) && $offer->getStateSupport() == 15662)) {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_base_stroyascheesya_igs_lgotnie_krome_igs_msk']))?->getPercent();
                        }
                        if (in_array($offer->getTown()->getTitle(), ['Московская область', 'Москва'])) {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_base_stroyascheesya_igs_lgotnie_krome_igs_msk']))?->getPercent();
                        } else {
                            return ($bankBonusRepository->findOneBy(['slug' => 'alpha_base_stroyascheesya_igs_lgotnie_krome_igs_other_regions']))?->getPercent();
                        }

                    }

            }
        }
        if ($bankMain->getTitle() == 'Уралсиб Банк') {
            if (in_array($offer->getTown()->getTitle(), ['Москва','Московская область', 'Ленинградская область', 'МСК',
                'Санкт-Петербург', 'Краснодарский край'])) {
                return ($bankBonusRepository->findOneBy(['slug' => 'uralsib_base_msk_spb']))?->getPercent();
            } else {
                return ($bankBonusRepository->findOneBy(['slug' => 'uralsib_base_other_regions']))?->getPercent();
            }
        }
        if ($bankMain->getTitle() == 'ДОМ.РФ' && $offer->getObjectType() == 'ижс') {
            return ($bankBonusRepository->findOneBy(['slug' => 'dom_rf_igs_gos']))?->getPercent();
        }
        return null;
    }


    /**
     * Высчитывает бонус с запрошенной изначально суммы заявки (не выданной)
     */
    public function calcGetOldBonus(Calculated $calculated): int
    {
        $summ = $calculated->getFullsumm();
        // TODO: убрать при удалении старых банков
        if (isset($calculated->getOther()['version']) && $calculated->getOther()['version'] === 2) {
            $bankMain = $this->bankMainRep->find($calculated->getOther()['bankId']);
            $bankProc = $this->getOfferBonusProcent($calculated->getOffer(), $bankMain);
        } else {
            $bankProc = $calculated->getBank()->getBonusProcent();
        }
        //бонус из динамической ставки
        $other = $calculated->getOther();
        if ($other['dinamic']){
            $bankProc = $other['dinamic']['feePercent'];
        }
        $bonus = ($summ * $bankProc) / 100;
        return intval(round($bonus, 0, PHP_ROUND_HALF_UP));
    }


    /**
     * Принимает id банка (нов. версии) и выдаёт массив с названием и картинкой
     */
    public function getBankInfo(int $bankId): array
    {
        $bankEntity = $this->bankMainRep->find($bankId);
        if (!$bankEntity) return [
            'title' => 'Неизвестный банк',
            'img' => '/img/favicon.png',
            'referenceFile' => false,
        ];

        $imgSrc = '/img/favicon.png';
        $attachId = isset($bankEntity->getOther()['logoId']) ? intval($bankEntity->getOther()['logoId']) : null;
        if ($attachId) {
            $attach = $this->attachmentRepository->find($attachId);
            if ($attach) $imgSrc =  $this->uploaderHelper->asset($attach, 'file');
        }

        $referenceFile = false;
        $attachId = isset($bankEntity->getOther()['referenceFile']) ? intval($bankEntity->getOther()['referenceFile']) : null;
        if ($attachId) {
            $attach = $this->attachmentRepository->find($attachId);
            if ($attach) $referenceFile =  $this->uploaderHelper->asset($attach, 'file');
        }

        return [
            'title' => $bankEntity->getTitle(),
            'img' => $imgSrc,
            'referenceFile' => $referenceFile,
        ];
    }



    /**
     * Выводит правильный список документов на заёмщика в зависимости от данных заемщика
     */
    public function getDocumentList(Offer $offer)
    {
        $siteSettRep = $this->settsRep;
        $getReturn = function ($label) use ($siteSettRep) {
            $entity = $siteSettRep->findSitesettingByLabel($label);
            if (!$entity) return "<p>Ошибка. Не найден список документов. Обратитесь в техническую поддержку</p>";
            return $entity->getSettings();
        };

        //Военная ипотека
        if ($offer->getIsMilitaryMortgage()) {
            return $getReturn('doclist_var');
        }

        //Бизнес
        if ($offer->getHiringType() === 30) {
            return $getReturn('doclist_biz');
        }

        //ИП
        if ($offer->getHiringType() === 20) {
            return $getReturn('doclist_ip');
        }

        // Самозанятый
        if ($offer->getHiringType() === 40) {
            if ($offer->getNationality() == 10) {
                return $getReturn('doclist_self_employed');
            }

            return $getReturn('doclist_self_employed_nerezident');
        }

        //По 2 документам
        if ($offer->getProofMoney() === 30) {
            return $getReturn('doclist_2doc');
        }


        //2НДФЛ
        if ($offer->getProofMoney() === 10) {
            if ($offer->getNationality() !== 10) return $getReturn('doclist_empl_unres_2ndfl');
            if ($offer->getIsMotherCap()) return $getReturn('doclist_empl_rf_2ndfl_mother');
            if ($offer->getCreditTarget() === "рефинансирование") return $getReturn('doclist_empl_rf_2ndfl_refinance');
            if ($offer->getCreditTarget() === "залог") return $getReturn('doclist_empl_rf_2ndfl_zalog');
            return $getReturn('doclist_empl_rf_2ndfl');
        }

        //Форма банка
        if ($offer->getProofMoney() === 20) {
            if ($offer->getNationality() !== 10) return $getReturn('doclist_empl_unres_bank');
            if ($offer->getIsMotherCap()) return $getReturn('doclist_empl_rf_bank_mother');
            if ($offer->getCreditTarget() === "рефинансирование") return $getReturn('doclist_empl_rf_bank_refinance');
            if ($offer->getCreditTarget() === "залог") return $getReturn('doclist_empl_rf_bank_zalog');
            return $getReturn('doclist_empl_rf_bank');
        }

        return $getReturn(null);
    }


    public function findDublicates(Offer $offer): array
    {
        $buyer = $offer->getBuyer();

        //Находим все подзаявки с нужным номером телефона
        $exitsCalculated = $this->fetchUserBuyerOffers($offer->getUser(), $buyer->getPhone());

        //Проверяем, есть ли среди них полностью совпадающие по имени и фамилии
        $fitredExitsCalculated = array_filter($exitsCalculated, function (Calculated $calc) use ($buyer) {
            $calcByuer = $calc->getOffer()->getBuyer();
            $this->em->refresh($calcByuer);
            $testEq = strtolower($calcByuer->getFirstname()) === strtolower($buyer->getFirstname()) &&
                strtolower($calcByuer->getLastname()) === strtolower($buyer->getLastname());
            if ($testEq) {
                return true;
            }
            return false;
        });
        return $fitredExitsCalculated;
    }

    public function getOfferAdminLink(Calculated $calc)
    {
        return $this->adminUrlGenerator
            ->setController(CalculatedCrudController::class)
            ->setAction(Action::EDIT)
            ->setEntityId($calc->getId())
            ->generateUrl();
    }




    public function generateOffer(FormInterface $form, Offer $newOffer, User $oldUser): Offer
    {
        $newOffer->time = $form->get('time')->getData();
        $newOffer->cost = $form->get('cost')->getData();
        $newOffer->firstpay = $form->get('firstpay')->getData();
        $newOffer->motherCapSize = $form->get('motherCapSize')->getData();

        /**
         * Банки приходят в поле "banks" формы в виде json формата
         * например [{8: "Открытие"}, {4, "Сбербанк"}]
         */
        $newOffer->banks = json_decode($form->get('banks')->getData(), true);
        $newOffer->setUser($oldUser);

        foreach ($newOffer->banks as $key => $chosenBank) {
            $bankEntity = $this->bankMainRep->find($key);
            if (!$bankEntity) throw new \Error("Банк не найден");
            $this->calculatorService->testBank($bankEntity, $newOffer);
            if (!$bankEntity->isAccept) throw new \Exception('Банк не подходит для заявки');
            $calcEntity = new Calculated();
            if ($newOffer->getIsMotherCap()) {
                $calcEntity->setMotherCapSize(floatval($newOffer->motherCapSize));
            }
            $calcEntity->setOffer($newOffer);
            $calcEntity->setMonthcount($newOffer->time);
            $calcEntity->setFirstpay($newOffer->firstpay);
            $calcEntity->setFullsumm($this->banksCalculator->calculateBodySummOfType($newOffer, $bankEntity));
            $calcEntity->setProcent($bankEntity->calcData['rate']);
            $calcEntity->setMounthpay($bankEntity->calcData['monthlyPayment']);
            $calcEntity->setOther([
                'bankId' => $bankEntity->getId(),
                'bankName' => $bankEntity->getTitle(),
                'version' => 2,
            ]);

            $bankDepricated = $this->bankRep->findAll()[0]; //TODO удалить потом при смене банков
            $calcEntity->setBank($bankDepricated); //TODO - старый банк.
            $newOffer->addCalculated($calcEntity);
        }
        return $newOffer;
    }


    public function addCopyData(Offer $oldOffer, Offer $newOffer): bool
    {
        if ($oldOffer->getBuyer()) {
            $newOffer->setBuyer(clone $oldOffer->getBuyer());
        }
        $oldOffer->getCobuyers()->map(function ($cobuyer) use ($newOffer) {
            $newOffer->addCobuyer(clone $cobuyer);
        });
        $oldOffer->getDocuments()->map(function ($oldAttach) use ($newOffer, $oldOffer) {
            $newAttach = clone $oldAttach;
            $oldFile = $oldAttach->getFile();
            $newFileName = tempnam(sys_get_temp_dir(), 'copy' . $oldAttach->getId());

            try {
                copy($oldFile->getPathname(), $newFileName);
                $newFile = new UploadedFile(
                    $newFileName,
                    $oldFile->getFilename(),
                    $oldFile->getMimeType(),
                    null,
                    true
                );
                $newAttach->setFoldername("offer" . $oldOffer->getId() . "_copy");
                $newAttach->setOffer($newOffer);
                $newAttach->setFile($newFile);
                $this->em->persist($newAttach);
            } catch (\Throwable $th) {
                $errorLog = $this->logService->addSysLog($th->getMessage());
                $this->em->persist($errorLog);
            }
        });
        return true;
    }


    public function fillCalculatedToUser(User $user): User
    {
        foreach ($user->getOffers() as $offer) {
            foreach ($offer->getCalculateds() as $key => $calc) {
                $user->calculatedSynt[] = $calc;
            }
        }
        usort($user->calculatedSynt, function (Calculated $a, Calculated $b) {
            return $b->getStatus() - $a->getStatus();
        });
        return $user;
    }
}
