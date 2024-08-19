<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Wallet;
use App\Entity\Calculated;
use App\Service\Logs\LogService;
use App\Repository\OfferRepository;
use App\Repository\WalletRepository;
use App\Service\Wallet\WalletService;
use App\Repository\CalculatedRepository;
use App\Service\bitrix24\BitrixService;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class UtilsController extends AbstractController
{
    /**
     * @Route("/admin/com/clear", name="command_cache_clear")
     */
    public function command_cache_clear(KernelInterface $kernel)
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        $outClear = $this->do_command($kernel, 'cache:clear');
        return new Response("<pre>" . $outClear . "<br><a href='/admin/com/warm'>Далее...</a></pre>");
    }

    /**
     * @Route("/admin/com/warm", name="command_cache_warmup")
     */
    public function command_cache_warmup(KernelInterface $kernel)
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        $out = $this->do_command($kernel, 'cache:warm');

        return new Response("<pre>" . $out . "<br><a href='/admin/'>Вернуться в админку...</a></pre>");
    }

    /**
     * @Route("/admin/com/migration", name="command_migration")
     */
    public function command_make_migration(KernelInterface $kernel)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $outClear = $this->do_command($kernel, 'make:migration');
        return new Response("<pre>" . $outClear . "<br><a href='/admin/com/migrate'>Далее...</a></pre>");
    }

    /**
     * @Route("/admin/com/migrate", name="command_migrate")
     */
    public function command_make_migrate(KernelInterface $kernel)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $outClear = $this->do_command($kernel, 'doctrine:migrations:migrate');
        return new Response("<pre>" . $outClear . "<br><a href='/admin/com/clear'>Далее...</a></pre>");
    }

    private function do_command($kernel, $command)
    {
        $env = $kernel->getEnvironment();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => $command
        ));
        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }


    /**
     * @Route("/admin/removedrafts", name="command_remove_deafts")
     */
    public function command_remove_deafts(
        OfferRepository $offerRepository,
        CalculatedRepository $calculatedRepository,
        Request $request,
        EntityManagerInterface $em
    ) {
        $now = new \DateTimeImmutable();
        $weekBefore = $now->sub(new \DateInterval('P2W'));
        /**
         * @var Calculated[] $calculateds
         */
        $calculateds = $calculatedRepository->createQueryBuilder('o')
            ->andWhere('o.status = 0')
            ->andWhere('o.createdAt < :dateInt')
            ->setParameter('dateInt', $weekBefore)
            ->getQuery()->getResult();

        if ($request->get('run') === "1") {
            foreach ($calculateds as $calculated) {
                $em->remove($calculated);
            }
            $em->flush();
            return new Response("Черновики заявок были очищены. Запустите очистку ошибочных заявок, чтобы очистить следы");
        }
        return new Response("Найдено <b>" . count($calculateds) . "</b> черновиков заявок. Удалить их? <br><br><a href='" . $this->generateUrl('command_remove_deafts', ["run" => "1"]) . "'>Удалить черновики заявок</a>");
    }

    /**
     * @Route("/admin/remove_empty_offers", name="remove_empty_offers")
     */
    public function remove_empty_offers(
        OfferRepository $offerRepository,
        EntityManagerInterface $em,
    ) {
        $allOffers = $offerRepository->findAll();
        $errorOffers = array_filter($allOffers, function (Offer $offer) {
            return $offer->getCalculateds()->isEmpty();
        });
        foreach ($errorOffers as $key => $offer) {
            $em->remove($offer);
        }
        $em->flush();
        dump("Найдено ошибочный заявок:" . count($errorOffers) . " шт.");
        dd($errorOffers);
    }


    /**
     * @Route("/sec/admin/wallet_pay/ksenia_pobuzanskaya", name="check_month_money")
     */
    public function check_month_money(
        WalletRepository $walletRepository,
        EntityManagerInterface $em,
        WalletService $walletService,
        LogService $logService,
        BitrixService $bitrixService,
    ) {
        /**
         * @var Wallet[] $wallets
         */
        $wallets = $walletRepository->createQueryBuilder('w')
            ->andWhere('w.balance > 0')
            ->getQuery()->getResult();

        foreach ($wallets as $wallet) {
            $nowMonth = (new \DateTimeImmutable())->format('n');
            $recalculateMonth = $wallet->getRecalculatedAt()?->format('n');

            if ($nowMonth === $recalculateMonth) {
                $log = $logService->addSysLog(
                    "Не выполнен перенос в 'Готовы к выводу' партнеру {$wallet->getUserAccount()->getId()}/{$wallet->getUserAccount()->getFio()}. В этом месяце уже был перевод средств."
                );
                $log->setTitle('Ошибка перевода баланса в "Готовы к выводу"');
                $log->setEntityId($wallet->getId());
                $log->setEntityType('Wallet');
                $em->persist($log);
                continue;
            };

            $transactions = $wallet->getTransactionsPlus();
            $walletService->recalculateWallet($wallet);
            foreach ($transactions as $trash) {
                $trash->setStatus(30);
                $em->persist($trash);
            }
            $wallet->setBalanceReady($wallet->getBalance() + $wallet->getBalanceReady());
            $oldBalance = $wallet->getBalance();
            $wallet->setBalance(0);
            $wallet->setRecalculatedAt(new \DateTimeImmutable());
            $log = $logService->addSysLog(
                "Выполнен перенос в 'Готовы к выводу' партнеру {$wallet->getUserAccount()->getId()}/{$wallet->getUserAccount()->getFio()} суммы {$oldBalance} ₽"
            );
            $log->setTitle('Перевод баланса в "Готовы к выводу"');
            $log->setEntityId($wallet->getId());
            $log->setEntityType('Wallet');
            $em->persist($log);
            $em->persist($wallet);

            // Изменение стадии сделки для рефералов
            $referals = $wallet->getUserAccount()->getPartner()->getReferals();

            foreach ($referals as $referalPartner) {
                $bitrixService->updateReferalDealStage($referalPartner, 3, 4);
            }
        }
        $em->flush();
        return new Response('Выполнено');
    }
}
