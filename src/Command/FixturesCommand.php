<?php

namespace App\Command;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'fixtures',
)]
class FixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user1 = new User();
        $user1->setName('usr1');
        $this->entityManager->persist($user1);

        $user2 = new User();
        $user2->setName('usr2');
        $this->entityManager->persist($user2);

        $task = new Task();
        $task->setName('reproduce');
        $task->setAssignedTo($user1);
        $this->entityManager->persist($task);

        return Command::SUCCESS;
    }
}
