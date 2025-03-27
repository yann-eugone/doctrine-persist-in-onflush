<?php

namespace App\Command;

use App\Entity\AuditEvent;
use App\Entity\Task;
use App\Entity\User;
use App\Listener\AuditEntitiesListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'test',
)]
class TestCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditEntitiesListener $listener,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('mode', mode: InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->listener->mode($input->getArgument('mode'));

        $task = $this->entityManager->getRepository(Task::class)->findOneBy([]);
        $user = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('user')
            ->where('user != :user')
            ->setParameter('user', $task->getAssignedTo())
            ->getQuery()->getSingleResult();

        $task->setAssignedTo($user);

        $this->entityManager->flush();

        if ($this->entityManager->getRepository(AuditEvent::class)->count() === 0) {
            throw new \LogicException('No events recorded.');
        }

        return Command::SUCCESS;
    }
}
