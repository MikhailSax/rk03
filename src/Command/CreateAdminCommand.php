<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create-admin',
    description: 'Создаёт администратора.',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email администратора')
            ->addArgument('phone', InputArgument::REQUIRED, 'Телефон администратора')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Имя администратора')
            ->addArgument('password', InputArgument::REQUIRED, 'Пароль администратора')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Фамилия администратора');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $phone = (string) $input->getArgument('phone');
        $firstName = (string) $input->getArgument('firstName');
        $password = (string) $input->getArgument('password');
        $lastName = $input->getArgument('lastName');

        if ($this->userRepository->findOneBy(['email' => mb_strtolower(trim($email))]) instanceof User) {
            $io->error('Пользователь с таким email уже существует.');

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setFirstName($firstName);
        $user->setLastName(is_string($lastName) ? $lastName : null);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Администратор %s успешно создан.', $user->getEmail()));

        return Command::SUCCESS;
    }
}
