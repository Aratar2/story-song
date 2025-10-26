<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Создать администратора для панели управления')]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email администратора')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Пароль администратора');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        if (!is_string($email) || $email === '') {
            $email = $io->ask('Email администратора');
        }

        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Укажите корректный email.');

            return Command::FAILURE;
        }

        $plainPassword = $input->getOption('password');
        if (!is_string($plainPassword) || $plainPassword === '') {
            $plainPassword = $io->askHidden('Введите пароль (символы не будут отображены)');
        }

        if (!is_string($plainPassword) || $plainPassword === '') {
            $io->error('Пароль не может быть пустым.');

            return Command::FAILURE;
        }

        $repository = $this->entityManager->getRepository(AdminUser::class);
        $existing = $repository->findOneBy(['email' => mb_strtolower($email)]);
        if ($existing instanceof AdminUser) {
            $io->error('Администратор с таким email уже существует.');

            return Command::FAILURE;
        }

        $user = new AdminUser();
        $user->setEmail($email);
        $user->setPassword('');
        $user->setPlainPassword($plainPassword);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Администратор %s создан.', $email));

        return Command::SUCCESS;
    }
}
