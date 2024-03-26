<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Command;

use Gtt\SyliusRbacPlugin\Service\RbacInitializationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'gtt:rbac:init',
    description: 'Inserts into Sylius RBAC tables needed access rights and rules',
)]
class InitRbacCommand extends Command
{
    public function __construct(private readonly RbacInitializationService $rbacInitializationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Rbac initialization ...');

        $isFullInitMode = true;

        if ($this->rbacInitializationService->checkIfInitialized()) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Rbac was initialized before. Grant "super_admin" access to all existing admins? [y/n]' . PHP_EOL,
                false
            );

            assert($helper instanceof QuestionHelper);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Rbac initialization was exited.');

                return Command::SUCCESS;
            }

            $isFullInitMode = false;
        }

        foreach ($this->rbacInitializationService->initialize($isFullInitMode) as $key => $value) {
            $output->writeln(sprintf(
                ' - %s : %s',
                ucfirst(str_replace('__', ' ', $key)),
                $value,
            ));
        }

        $output->writeln('Rbac initialized successfully.');

        return self::SUCCESS;
    }
}
