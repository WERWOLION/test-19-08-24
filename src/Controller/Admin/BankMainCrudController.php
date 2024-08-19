<?php

namespace App\Controller\Admin;

use App\Entity\BankNum;
use App\Entity\BankMain;
use App\Entity\Attachment;
use App\Entity\BankOption;
use App\Repository\TownRepository;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;



class BankMainCrudController extends AbstractCrudController
{
    public function __construct(
        private TownRepository $townRepository,
        private AttachmentRepository $attachmentRepository,
        private UploaderHelper $uploaderHelper,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Список банков')
            ->setPageTitle('new', 'Новый банк')
            ->setPageTitle('edit', fn (BankMain $bank) => 'Редактирование банка: #' . $bank->getId() . ' - ' . $bank->getTitle())

            ;
    }

    public function getLogoItem()
    {
        /**
         * @var BankMain $entity
         */

        $entity = $this->getContext()->getEntity()->getInstance();
        if (!$entity || !is_array($entity->getOther()) || !isset($entity->getOther()['logoId'])) return 'Нет логотипа';
        $logoAttacg = $this->attachmentRepository->findOneBy([
            'id' => $entity->getOther()['logoId'],
        ]);
        if ($logoAttacg) {
            return "<img src='{$this->uploaderHelper->asset($logoAttacg, 'file')}' width=150 />";
        }
        return 'Ошибка логотипа';
    }

    public function getReferenceItem()
    {
        /**
         * @var BankMain $entity
         */

        $entity = $this->getContext()->getEntity()->getInstance();
        if (!$entity || !is_array($entity->getOther()) || !isset($entity->getOther()['referenceFile'])) return 'Справка ФБ отсутствует';
        $logoAttacg = $this->attachmentRepository->findOneBy([
            'id' => $entity->getOther()['referenceFile'],
        ]);
        if ($logoAttacg) {
            return "<a style=\"font-size: 15px;\" href='{$this->uploaderHelper->asset($logoAttacg, 'file')}'>ссылка на файл</a>";
        }
        return 'Ошибка справки фб';
    }

    public function uploadAttachments(EntityManagerInterface $entityManager, BankMain $entityInstance)
    {
        $other = $entityInstance->getOther();

        // Добавление логотипа
        $file = $entityInstance->logoId;
        if ($file instanceof UploadedFile) {
            if (isset($entityInstance->getOther()['logoId'])) {
                $oldLogo = $this->attachmentRepository->findOneBy([
                    'id' => $entityInstance->getOther()['logoId'],
                ]);
                if ($oldLogo) {
                    $entityManager->remove($oldLogo);
                }
            }

            $attach = new Attachment();
            $attach->setFile($file);
            $attach->setUser($this->getUser());
            $attach->setFoldername('bankslogo');
            $entityManager->persist($attach);
            $entityManager->flush();

            $other['logoId'] = $attach->getId();
        };


        // Добавление справки фб
        $file = $entityInstance->referenceFile;
        if ($file instanceof UploadedFile) {
            if (isset($entityInstance->getOther()['referenceFile'])) {
                $oldfile = $this->attachmentRepository->findOneBy([
                    'id' => $entityInstance->getOther()['referenceFile'],
                ]);
                if ($oldfile) {
                    $entityManager->remove($oldfile);
                }
            }

            $attach = new Attachment();
            $attach->setFile($file);
            $attach->setUser($this->getUser());
            $attach->setFoldername('banksFile');
            $entityManager->persist($attach);
            $entityManager->flush();

            $other['referenceFile'] = $attach->getId();
        };

        // Сохранения информации
        $entityInstance->setOther($other);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->copyAllTypes($entityInstance);
        $this->uploadAttachments($entityManager, $entityInstance);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->copyAllTypes($entityInstance);
        $this->uploadAttachments($entityManager, $entityInstance);
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    /**
     * Копирует ПВ из "Льготная ипотека" в "Семейная" в тех полях, которые
     * объеденены + копирует в "Льготной ипотеке ПВ" из 2НДФЛ в "по форме банка".
     * При этом поля "Льготная/семейная дом/таунхаус" относятся сразу
     * и к 2НДФЛ и к "по форме банка"
     */
    public function copyFields(BankOption $bankOption): BankOption
    {
        $social = $bankOption->getFirstSocial();
        $family = $bankOption->getFirstFamily();
        $war = $bankOption->getProcWar();

        if ($war && $war->getNDFL()) {
            $war->setBankForm($war->getNDFL());
        }

        $social->setBankForm($social->getNDFL());
        if ($family) {
            $family->setNDFL($social->getNDFL());
            $family->setBankForm($social->getBankForm());
            $family->setOn2doc($social->getOn2doc());
            $family->setSupportHome($social->getSupportHome());
            $family->setSupportHome2doc($social->getSupportHome2doc());
            return $bankOption;
        }
        $newFamily = new BankNum();
        $newFamily->setNDFL($social->getNDFL());
        $newFamily->setBankForm($social->getBankForm());
        $newFamily->setOn2doc($social->getOn2doc());
        $newFamily->setSupportHome($social->getSupportHome());
        $newFamily->setSupportHome2doc($social->getSupportHome2doc());
        $bankOption->setFirstFamily($newFamily);
        return $bankOption;
    }

    public function copyAllTypes(BankMain $entityInstance): BankMain
    {
        $this->copyFields($entityInstance->getIpotekaOptions());
        $this->copyFields($entityInstance->getRefinanceOptions());
        $this->copyFields($entityInstance->getPledgeOptions());
        return $entityInstance;
    }

    public static function getEntityFqcn(): string
    {
        return BankMain::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $resArray = [

            FormField::addTab('Основная информация'),

            IdField::new('id')->onlyOnIndex(),
            TextField::new('title', 'Заголовок'),

            TextField::new('logoId', 'Логотип банка')
                ->setFormType(FileType::class)
                ->onlyOnForms()
                ->setHelp($this->getLogoItem($pageName))
                ->setColumns(6),

            TextField::new('referenceFile', 'Справка по форме банка')
                ->setFormType(FileType::class)
                ->onlyOnForms()
                ->setHelp($this->getReferenceItem($pageName))
                ->setColumns(6),

            // 'Справка по форме банка',

            DateTimeField::new('updatedAt', "Последнее обновление")->onlyOnIndex(),
            FormField::addPanel('По умолчанию'),
            NumberField::new('bonusProcent', "Бонус, ипотека, %")->setColumns(4),
            NumberField::new('bonusPledge', "Бонус, под залог, %")->setColumns(4),
            NumberField::new('bonusStateSupport', "Бонус, господдержка, %")->setColumns(4),

            FormField::addPanel('Привязанные типы бонусов'),
            AssociationField::new('bankBonusTypes', 'Типы бонусов')->onlyOnForms()->setColumns(12),
            FormField::addPanel('Динамическая ставка'),
            CollectionField::new('bonusProcentDinamic', "Ипотека")->setEntryType(\App\Form\FeePercentType::class)->setEntryIsComplex()->renderExpanded()->setColumns(4),
            CollectionField::new('bonusPledgeDinamic', "Залог")->setEntryType(\App\Form\FeePercentType::class)->setEntryIsComplex()->renderExpanded()->setColumns(4),
            CollectionField::new('bonusStateSupportDinamic', "Господдержка")->setEntryType(\App\Form\FeePercentType::class)->setEntryIsComplex()->renderExpanded()->setColumns(4),

            FormField::addPanel('Господдержка'),
            NumberField::new('bitrixId', "ID банка в справочнике Bitrix24")->setFormTypeOption('property_path', 'other[bitrixId]')->onlyOnForms(),
            BooleanField::new('isOn', "Банк включен")->setFormTypeOption('property_path', 'other[isOn]')->onlyOnForms()->setColumns(3),
            BooleanField::new('isForMigrant', "Разрешены нерезиденты")->setFormTypeOption('property_path', 'other[isForMigrant]')->onlyOnForms()->setColumns(3),



            Field::new('isWarCap', "Есть военная ипотека")->onlyOnForms()->setColumns(3),
            Field::new('isSocial', "Есть льготная ипотека")->onlyOnForms()->setColumns(3),
            Field::new('isFamily', "Есть семейная ипотека")->onlyOnForms()->setColumns(3),
            Field::new('isIT', "Есть IT ипотека")->onlyOnForms()->setColumns(3),

            FormField::addPanel('Мин-макс возраст и сроки'),

            NumberField::new('timeMin', "Min срок кредита, лет")->setFormTypeOption('property_path', 'minMax[timeMin]')->onlyOnForms()->setColumns(3),
            NumberField::new('timeMax', "Max срок кредита, лет")->setFormTypeOption('property_path', 'minMax[timeMax]')->onlyOnForms()->setColumns(3),
            NumberField::new('ageMin', "Min возраст кредитования")->setFormTypeOption('property_path', 'minMax[ageMin]')->onlyOnForms()->setColumns(3),
            NumberField::new('ageMax', "Max возраст кредитования")->setFormTypeOption('property_path', 'minMax[ageMax]')->onlyOnForms()->setColumns(3),
            NumberField::new('timeKNMin', "Min срок кредита коммерция, лет")->setFormTypeOption('property_path', 'minMax[timeKNMin]')->onlyOnForms()->setColumns(3),
            NumberField::new('timeKNMax', "Max срок кредита коммерция, лет")->setFormTypeOption('property_path', 'minMax[timeKNMax]')->onlyOnForms()->setColumns(3),


            FormField::addPanel('Мин-макс суммы, обычные'),

            NumberField::new('min', "Мин сумма кредита")->setFormTypeOption('property_path', 'minMax[min]')->onlyOnForms()->setColumns(3),
            NumberField::new('max', "Макс сумма кредита")->setFormTypeOption('property_path', 'minMax[max]')->onlyOnForms()->setColumns(3),
            NumberField::new('minMSK', "Мин сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[minMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxMSK', "Макс сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[maxMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('minSPB', "Мин сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[minSPB]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxSPB', "Макс сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[maxSPB]')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Мин-макс суммы, Льготная'),

            NumberField::new('minSoc', "Мин сумма кредита")->setFormTypeOption('property_path', 'minMax[minSoc]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxSoc', "Макс сумма кредита")->setFormTypeOption('property_path', 'minMax[maxSoc]')->onlyOnForms()->setColumns(3),
            NumberField::new('minSocMSK', "Мин сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[minSocMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxSocMSK', "Макс сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[maxSocMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('minSocSPB', "Мин сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[minSocSPB]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxSocSPB', "Макс сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[maxSocSPB]')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Мин-макс суммы, Семейная  ипотека'),

            NumberField::new('minFamily', "Мин сумма кредита")->setFormTypeOption('property_path', 'minMax[minFamily]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxFamily', "Макс сумма кредита")->setFormTypeOption('property_path', 'minMax[maxFamily]')->onlyOnForms()->setColumns(3),
            NumberField::new('minFamilyMSK', "Мин сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[minFamilyMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxFamilyMSK', "Макс сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[maxFamilyMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('minFamilySPB', "Мин сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[minFamilySPB]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxFamilySPB', "Макс сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[maxFamilySPB]')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Мин-макс суммы, IT-ипотека'),

            NumberField::new('minIt', "Мин сумма кредита")->setFormTypeOption('property_path', 'minMax[minIt]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxIt', "Макс сумма кредита")->setFormTypeOption('property_path', 'minMax[maxIt]')->onlyOnForms()->setColumns(3),
            NumberField::new('minItMSK', "Мин сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[minItMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxItMSK', "Макс сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[maxItMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('minItSPB', "Мин сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[minItSPB]')->onlyOnForms()->setColumns(3),
            NumberField::new('maxItSPB', "Макс сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[maxItSPB]')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Мин-макс суммы, По 2 документам'),

            NumberField::new('min2d', "Мин сумма кредита")->setFormTypeOption('property_path', 'minMax[min2d]')->onlyOnForms()->setColumns(3),
            NumberField::new('max2d', "Макс сумма кредита")->setFormTypeOption('property_path', 'minMax[max2d]')->onlyOnForms()->setColumns(3),
            NumberField::new('min2dMSK', "Мин сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[min2dMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('max2dMSK', "Макс сумма кредита МСК и область")->setFormTypeOption('property_path', 'minMax[max2dMSK]')->onlyOnForms()->setColumns(3),
            NumberField::new('min2dSPB', "Мин сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[min2dSPB]')->onlyOnForms()->setColumns(3),
            NumberField::new('max2dSPB', "Макс сумма кредита СПБ и ЛО")->setFormTypeOption('property_path', 'minMax[max2dSPB]')->onlyOnForms()->setColumns(3),
        ];

        $resArray[] = FormField::addTab('Ипотека');
        $resArray[] = BooleanField::new('hasIpoteka', "Ипотека включена")->setFormTypeOption('property_path', 'other[hasIpoteka]')->onlyOnForms()->onlyOnForms();
        $resArray[] = BooleanField::new('hasMother', "Можно с мат. капиталом")->setFormTypeOption('property_path', 'other[hasMother]')->onlyOnForms()->onlyOnForms();
        $resArray[] = BooleanField::new('hasOn2docMother', "Можно с мат. капиталом (по 2 документам)")->setFormTypeOption('property_path', 'other[hasOn2docMother]')->onlyOnForms()->onlyOnForms();

        $resArray = array_merge($resArray, $this->getOptionsArray('ipotekaOptions'));

        $resArray[] = FormField::addTab('Рефинансирование');
        $resArray[] = BooleanField::new('hasRefinance', "Рефинансирование включено")->setFormTypeOption('property_path', 'other[hasRefinance]')->onlyOnForms()->setColumns(3);
        $resArray[] = BooleanField::new('withAddAmountEnabled', "С доп. суммой")->onlyOnForms()->setColumns(3);
        $resArray[] = BooleanField::new('withConsolidationEnabled', "С консодидацией")->onlyOnForms()->setColumns(3);

        $resArray = array_merge($resArray, $this->getOptionsArray('refinanceOptions'));

        $resArray[] = FormField::addTab('Деньги под залог');
        $resArray[] = BooleanField::new('hasPledge', "Деньги под залог включено")->setFormTypeOption('property_path', 'other[hasPledge]')->onlyOnForms()->onlyOnForms();
        $resArray = array_merge($resArray, $this->getOptionsArray('pledgeOptions'));


        $resArray[] = FormField::addTab('Регионы');
        $resArray[] = ChoiceField::new('towns', 'Список городов')->allowMultipleChoices()->setChoices($this->getTownsList())->renderExpanded()->onlyOnForms();


        return $resArray;
    }




    public function getTownsList()
    {
        $towns = $this->townRepository->findAll();
        $townOptions = [];
        foreach ($towns as $town) {
            $townOptions[$town->getTitle()] = $town->getId();
        }
        return $townOptions;
    }



    public function getOptionsArray($columbName)
    {
        $fieldsArray = [
            FormField::addPanel('Тип недвижимости'),

            BooleanField::new($columbName . '.checkFlat.isOn', "Квартиры")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkHome.isOn', "Дом")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkTynehouse.isOn', "Таунхаус")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkRoom.isOn', "Отдельная комната")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkLastroom.isOn', "Последняя доля")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkApartments.isOn', "Апартаменты")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkKn.isOn', "Коммерция")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkIjs.isOn', "ИЖС")->onlyOnForms()->setColumns(3),

            FormField::addPanel('Подтверждение дохода'),

            BooleanField::new($columbName . '.checkFlat.isOnNDFL', "2НДФЛ")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkFlat.isOnBankForm', "По форме банка")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkFlat.isOnSZIILS', "СЗИ-ИЛС")->onlyOnForms()->setColumns(3),

            FormField::addRow(),

            BooleanField::new($columbName . '.checkFlat.isOn2doc', "По 2 док, РФ, квартира")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkHome.isOn2doc', "По 2 док, РФ, дом")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkTynehouse.isOn2doc', "По 2 док, РФ, таунхаус")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkRoom.isOn2doc', "По 2 док, РФ, Отдельная комната")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkLastroom.isOn2doc', "По 2 док, РФ, Последняя доля")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkApartments.isOn2doc', "По 2 док, РФ, Апартаменты")->onlyOnForms()->setColumns(3),

            BooleanField::new($columbName . '.checkFlat.isOn2docMigrant', "По 2 док, нерезидент, квартира")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkHome.isOn2docMigrant', "По 2 док, нерезидент, дом")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkTynehouse.isOn2docMigrant', "По 2 док, нерезидент, таунхаус")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkRoom.isOn2docMigrant', "По 2 док, нерезидент, Отдельная комната")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkLastroom.isOn2docMigrant', "По 2 док, нерезидент, Последняя доля")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkApartments.isOn2docMigrant', "По 2 док, нерезидент, Апартаменты")->onlyOnForms()->setColumns(3),

            BooleanField::new($columbName . '.checkKn.isOn2doc', "По 2 док, РФ, Коммерция")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkKn.isOn2docMigrant', "По 2 док, нерезидент, Коммерция")->onlyOnForms()->setColumns(3),

            BooleanField::new($columbName . '.checkIjs.isOn2doc', "По 2 док, РФ, ИЖС")->onlyOnForms()->setColumns(3),
            BooleanField::new($columbName . '.checkIjs.isOn2docMigrant', "По 2 док, нерезидент, ИЖС")->onlyOnForms()->setColumns(3),
            $columbName === 'ipotekaOptions' ? BooleanField::new('proofMoney2docEnabled', "По 2 док. ИП/Бизнес")->onlyOnForms()->setColumns(3) : null,
            $columbName === 'ipotekaOptions' ? BooleanField::new('proofMoney2docSelfEmployedEnabled', "По 2 док. Самозанятые")->onlyOnForms()->setColumns(3) : null,
            $columbName === 'ipotekaOptions' ? BooleanField::new('proofMoneySfrBusinessEnabled', "По СФР для бизнеса")->onlyOnForms()->setColumns(3) : null,
            $columbName === 'ipotekaOptions' ? BooleanField::new('proofMoney2ndflBusinessEnabled', "По 2НДФЛ для бизнеса")->onlyOnForms()->setColumns(3) : null,

            ];
            if ($columbName === 'ipotekaOptions'){
                $fieldsArray[] = FormField::addPanel('Динамическая ставка');
                $fieldsArray[] = BooleanField::new('marketIpotekaEnabled', "Рыночная ипотека")->setColumns(3);
                $fieldsArray[] = BooleanField::new('stateIpotekaEnabled', "Госпрограммы")->setColumns(3);
            } else if ($columbName === 'refinanceOptions'){
                $fieldsArray[] = FormField::addPanel('Динамическая ставка');
                $fieldsArray[] = BooleanField::new('marketIpotekaRefEnabled', "Рыночная ипотека")->setColumns(3);
                $fieldsArray[] = BooleanField::new('stateIpotekaRefEnabled', "Госпрограммы")->setColumns(3);
            } else if ($columbName === 'pledgeOptions') {
                $fieldsArray[] = FormField::addPanel('Динамическая ставка');
                $fieldsArray[] = BooleanField::new('marketIpotekaPledgeEnabled', "Рыночная ипотека")->setColumns(3);
                $fieldsArray[] = BooleanField::new('stateIpotekaPledgeEnabled', "Госпрограммы")->setColumns(3);
            }


        $fieldsArray2 = [
            FormField::addPanel('Процентные ставки - РФ + 2НДФЛ'),

            NumberField::new($columbName . '.procFlat.NDFL', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.NDFL', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.NDFL', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.NDFL', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.NDFL', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.NDFL', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.NDFL', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.NDFL', 'Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.NDFL', 'Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.NDFL', 'Военная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIt.NDFL', 'IT-ипотека')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.NDFL', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.NDFL', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsSocial.NDFL', 'ИЖС Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsFamily.NDFL', 'ИЖС Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsIt.NDFL', 'ИЖС IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Процентные ставки - РФ + Форма банка'),

            NumberField::new($columbName . '.procFlat.bankForm', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.bankForm', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.bankForm', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.bankForm', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.bankForm', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.bankForm', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.bankForm', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.bankForm', 'Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.bankForm', 'Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.bankForm', 'Военная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIt.bankForm', 'IT-ипотека')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.bankForm', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.bankForm', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsSocial.bankForm', 'ИЖС Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsFamily.bankForm', 'ИЖС Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsIt.bankForm', 'ИЖС IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Процентные ставки - РФ + СЗИ-ИЛС'),

            NumberField::new($columbName . '.procFlat.SZIILS', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.SZIILS', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.SZIILS', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.SZIILS', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.SZIILS', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.SZIILS', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.SZIILS', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.SZIILS', 'Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.SZIILS', 'Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.SZIILS', 'Военная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIt.SZIILS', 'IT-ипотека')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.SZIILS', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.SZIILS', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsSocial.SZIILS', 'ИЖС Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsFamily.SZIILS', 'ИЖС Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsIt.SZIILS', 'ИЖС IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Процентные ставки - РФ + По 2 документам'),

            NumberField::new($columbName . '.procFlat.on2doc', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.on2doc', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.on2doc', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.on2doc', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.on2doc', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.on2doc', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.on2doc', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.on2doc', 'Льготная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.on2doc', 'Семейная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.on2doc', 'Военная')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.on2doc', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.on2doc', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsSocial.on2doc', 'ИЖС Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsFamily.on2doc', 'ИЖС Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.procIjsIt.on2doc', 'ИЖС IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Процентные ставки - РФ - ИП, Бизнес, самозанятые'),

            NumberField::new($columbName . '.procFlat.business', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.business', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.business', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.business', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.business', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.business', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.business', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.business', 'Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.business', 'Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.business', 'Военная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procIt.business', 'IT-ипотека')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.business', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.business', 'ИЖС')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjsSocial.business', 'ИЖС Льготная ипотека')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjsFamily.business', 'ИЖС Семейная ипотека')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjsIt.business', 'ИЖС IT-ипотека')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Процентные ставки - Нерезидент + 2НДФЛ'),

            NumberField::new($columbName . '.procFlat.migrantNDFL', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.migrantNDFL', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.migrantNDFL', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.migrantNDFL', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.migrantNDFL', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.migrantNDFL', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.migrantNDFL', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.migrantNDFL', 'Льготная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.migrantNDFL', 'Семейная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.migrantNDFL', 'Военная')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.migrantNDFL', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.migrantNDFL', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Процентные ставки - Нерезидент + Форма банка'),

            NumberField::new($columbName . '.procFlat.migrantBankForm', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.migrantBankForm', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.migrantBankForm', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.migrantBankForm', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.migrantBankForm', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.migrantBankForm', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.migrantBankForm', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.migrantBankForm', 'Льготная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.migrantBankForm', 'Семейная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.migrantBankForm', 'Военная')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.migrantBankForm', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.migrantBankForm', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Процентные ставки - Нерезидент + СЗИ-ИЛС'),

            NumberField::new($columbName . '.procFlat.migrantSZIILS', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.migrantSZIILS', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.migrantSZIILS', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.migrantSZIILS', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.migrantSZIILS', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.migrantSZIILS', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.migrantSZIILS', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.migrantSZIILS', 'Льготная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.migrantSZIILS', 'Семейная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.migrantSZIILS', 'Военная')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.migrantSZIILS', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.migrantSZIILS', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Процентные ставки - Нерезидент + По 2 документам'),

            NumberField::new($columbName . '.procFlat.migrant2doc', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.migrant2doc', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.migrant2doc', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.migrant2doc', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.migrant2doc', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.migrant2doc', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.migrant2doc', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.migrant2doc', 'Льготная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.migrant2doc', 'Семейная')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.migrant2doc', 'Военная')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.migrant2doc', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.migrant2doc', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Процентные ставки - нерезедент - ИП, Бизнес, самозанятые'),

            NumberField::new($columbName . '.procFlat.migrantBusiness', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procFlatNew.migrantBusiness', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procHome.migrantBusiness', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procTynehouse.migrantBusiness', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procRoom.migrantBusiness', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procLastRoom.migrantBusiness', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procApartments.migrantBusiness', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procSocial.migrantBusiness', 'Льготная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procFamily.migrantBusiness', 'Семейная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procWar.migrantBusiness', 'Военная ипотека')->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.procIt.migrantBusiness', 'IT-ипотека')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.procKn.migrantBusiness', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.procIjs.migrantBusiness', 'ИЖС')->onlyOnForms()->setColumns(3),



            FormField::addPanel('Первоначальный взнос - РФ + 2НДФЛ, в процентах'),

            NumberField::new($columbName . '.firstFlat.NDFL', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.NDFL', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.NDFL', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.NDFL', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.NDFL', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.NDFL', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.NDFL', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.NDFL', 'Семейная/Льготная - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.supportHome', 'Семейная/Льготная - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstWar.NDFL', 'Военная ипотека - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstWar.supportHome', 'Военная ипотека - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIt.NDFL', 'IT-ипотека - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIt.supportHome', 'IT-ипотека - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            NumberField::new($columbName . '.firstKn.NDFL', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.NDFL', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIjsSocialFamilyIt.NDFL', 'ИЖС Семейная/Льготная IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Первоначальный взнос - РФ + Форма банка, в процентах'),

            NumberField::new($columbName . '.firstFlat.bankForm', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.bankForm', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.bankForm', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.bankForm', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.bankForm', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.bankForm', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.bankForm', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.bankForm', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.bankForm', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIjsSocialFamilyIt.bankForm', 'ИЖС Семейная/Льготная IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Первоначальный взнос - РФ + СЗИ-ИЛС, в процентах'),

            NumberField::new($columbName . '.firstFlat.SZIILS', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.SZIILS', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.SZIILS', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.SZIILS', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.SZIILS', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.SZIILS', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.SZIILS', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.SZIILS', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.SZIILS', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIjsSocialFamilyIt.SZIILS', 'ИЖС Семейная/Льготная IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Первоначальный взнос - РФ + По 2 документам, в процентах'),

            NumberField::new($columbName . '.firstFlat.on2doc', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.on2doc', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.on2doc', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.on2doc', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.on2doc', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.on2doc', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.on2doc', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.on2doc', "Льготная/Семейная - квартиры")->onlyOnForms()->setColumns(3) : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.supportHome2doc', 'Льготная/Семейная - дом/таунхаус')->onlyOnForms()->setColumns(3) : null,
            NumberField::new($columbName . '.firstKn.on2doc', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.on2doc', 'ИЖС')->onlyOnForms()->setColumns(3),
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIjsSocialFamilyIt.on2doc', 'ИЖС Семейная/Льготная IT-ипотека')->onlyOnForms()->setColumns(3) : null,

            FormField::addPanel('Первоначальный взнос - РФ - ИП, Бизнес, самозанятые, в процентах'),

            NumberField::new($columbName . '.firstFlat.business', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.business', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.business', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.business', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.business', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.business', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.business', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.business', 'Семейная/Льготная - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.supportHome', 'Семейная/Льготная - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstWar.business', 'Военная ипотека - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstWar.supportHome', 'Военная ипотека - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIt.business', 'IT-ипотека - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIt.business', 'IT-ипотека - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            NumberField::new($columbName . '.firstKn.business', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.business', 'ИЖС')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjsSocialFamilyIt.on2doc', 'ИЖС Семейная/Льготная IT-ипотека')->onlyOnForms()->setColumns(3),


            FormField::addPanel('Первоначальный взнос - РФ - ИП, Бизнес, самозанятые + По 2 документам, в процентах'),

            NumberField::new($columbName . '.firstFlat.business2doc', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.business2doc', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.business2doc', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.business2doc', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.business2doc', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.business2doc', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.business2doc', 'Апартаменты')->onlyOnForms()->setColumns(3),
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.business2doc', 'Семейная/Льготная - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstSocial.supportHome2doc', 'Семейная/Льготная - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstWar.business2doc', 'Военная ипотека - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName != 'pledgeOptions' ? NumberField::new($columbName . '.firstWar.supportHome2doc', 'Военная ипотека - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIt.business2doc', 'IT-ипотека - квартиры')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            $columbName == 'ipotekaOptions' ? NumberField::new($columbName . '.firstIt.business2doc', 'IT-ипотека - дом/таунхаус')->onlyOnForms()->setColumns(3)->setHelp('Значение общее: 2НДФЛ / по форме банка') : null,
            NumberField::new($columbName . '.firstKn.business2doc', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.business2doc', 'ИЖС')->onlyOnForms()->setColumns(3),


            FormField::addPanel('Первоначальный взнос - Нерезидент + 2НДФЛ, в процентах'),

            NumberField::new($columbName . '.firstFlat.migrantNDFL', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.migrantNDFL', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.migrantNDFL', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.migrantNDFL', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.migrantNDFL', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.migrantNDFL', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.migrantNDFL', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.migrantNDFL', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.migrantNDFL', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Первоначальный взнос - Нерезидент + Форма банка, в процентах'),

            NumberField::new($columbName . '.firstFlat.migrantBankForm', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.migrantBankForm', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.migrantBankForm', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.migrantBankForm', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.migrantBankForm', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.migrantBankForm', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.migrantBankForm', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.migrantBankForm', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.migrantBankForm', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Первоначальный взнос - Нерезидент + СЗИ-ИЛС, в процентах'),

            NumberField::new($columbName . '.firstFlat.migrantSZIILS', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.migrantSZIILS', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.migrantSZIILS', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.migrantSZIILS', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.migrantSZIILS', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.migrantSZIILS', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.migrantSZIILS', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.migrantSZIILS', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.migrantSZIILS', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Первоначальный взнос - Нерезидент + По 2 документам, в процентах'),

            NumberField::new($columbName . '.firstFlat.migrant2doc', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.migrant2doc', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.migrant2doc', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.migrant2doc', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.migrant2doc', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.migrant2doc', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.migrant2doc', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.migrant2doc', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.migrant2doc', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Первоначальный взнос - нерезедент - ИП, Бизнес, самозанятые, в процентах'),

            NumberField::new($columbName . '.firstFlat.migrantBusiness', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.migrantBusiness', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.migrantBusiness', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.migrantBusiness', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.migrantBusiness', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.migrantBusiness', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.migrantBusiness', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.migrantBusiness', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.migrantBusiness', 'ИЖС')->onlyOnForms()->setColumns(3),

            FormField::addPanel('Первоначальный взнос - нерезедент - ИП, Бизнес, самозанятые + По 2 документам, в процентах'),

            NumberField::new($columbName . '.firstFlat.migrantBusiness2doc', 'Квартира (готовое)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstFlatNew.migrantBusiness2doc', 'Квартира (строящееся)')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstHome.migrantBusiness2doc', 'Дом')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstTynehouse.migrantBusiness2doc', 'Таунхаус')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstRoom.migrantBusiness2doc', 'Отдельная комната')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstLastRoom.migrantBusiness2doc', 'Последняя доля')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstApartments.migrantBusiness2doc', 'Апартаменты')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstKn.migrantBusiness2doc', 'Коммерция')->onlyOnForms()->setColumns(3),
            NumberField::new($columbName . '.firstIjs.migrantBusiness2doc', 'ИЖС')->onlyOnForms()->setColumns(3),


        ];

        return array_filter(array_merge($fieldsArray, $fieldsArray2));
    }
}
