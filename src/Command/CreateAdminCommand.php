<?php

namespace App\Command;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new administrator account.',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new admin')
            ->addArgument('password', InputArgument::REQUIRED, 'The plain password')
            ->addArgument('role', InputArgument::OPTIONAL, 'The role (admin, editor, viewer)', 'admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        $existing = $this->em->getRepository(Admin::class)->findOneBy(['username' => $username]);
        if ($existing) {
            $io->error(sprintf('User "%s" already exists.', $username));
            return Command::FAILURE;
        }

        $admin = new Admin();
        $admin->setUsername($username);
        $admin->setRole($role);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);

        $this->em->persist($admin);
        $this->em->flush();

        $io->success(sprintf('Admin user "%s" was successfully created with role "%s".', $username, $role));

        return Command::SUCCESS;
    }
}
