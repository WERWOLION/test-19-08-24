<?php

namespace App\Command;

use App\Entity\BankNum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

class FillBankOptionsCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:fill-bank-options';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command allows you to fill null fields of table bank_option...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('%s: fill-bank-options', date('Y-m-d H:i:s')));
        $em = $this->entityManager;
        $repo = $em->getRepository(\App\Entity\BankMain::class);
        $banks = $repo->findAll();
        $output->writeln(sprintf('banks found: %s', count($banks)));
        foreach ($banks as $bank){
            $output->writeln(sprintf('bank: %s', $bank->getTitle()));
            foreach (['getIpotekaOptions', 'getRefinanceOptions', 'getPledgeOptions'] as $optionGetter){
                $bankOptions = $bank->$optionGetter();
                if(!$bankOptions->getProcIjsSocial()){
                    $bankNum = new BankNum();
                    $em->persist($bankNum);
                    $bankOptions->setProcIjsSocial($bankNum);
                    $em->persist($bankOptions);
                    $em->persist($bank);
                    $em->flush();
                }
                if(!$bankOptions->getProcIjsFamily()){
                    $bankNum = new BankNum();
                    $em->persist($bankNum);
                    $bankOptions->setProcIjsFamily($bankNum);
                    $em->persist($bankOptions);
                    $em->persist($bank);
                    $em->flush();
                }
                if(!$bankOptions->getProcIjsIt()){
                    $bankNum = new BankNum();
                    $em->persist($bankNum);
                    $bankOptions->setProcIjsIt($bankNum);
                    $em->persist($bankOptions);
                    $em->persist($bank);
                    $em->flush();
                }
                if(!$bankOptions->getFirstIjsSocialFamilyIt()){
                    $bankNum = new BankNum();
                    $em->persist($bankNum);
                    $bankOptions->setFirstIjsSocialFamilyIt($bankNum);
                    $em->persist($bankOptions);
                    $em->persist($bank);
                    $em->flush();
                }
            }

        }
        $output->writeln(sprintf('%s: done', date('Y-m-d H:i:s')));
        return Command::SUCCESS;
    }
}