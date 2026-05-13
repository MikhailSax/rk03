<?php

namespace App\Controller\Admin;

use App\Entity\AdvertisementBooking;
use App\Entity\User;
use App\Repository\AdvertisementBookingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementBookingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdvertisementBookingRepository $bookingRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AdvertisementBooking::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('advertisement', 'Конструкция')
                ->autocomplete(),
            ChoiceField::new('sideCode', 'Сторона')
                ->setChoices([
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                ])
                ->setHelp('Список сторон. Можно выбрать только существующий код.'),
            TextField::new('clientName', 'Клиент')->onlyOnIndex(),
            ChoiceField::new('clientName', 'Клиент')
                ->setChoices($this->getClientChoices())
                ->autocomplete()
                ->onlyOnForms(),
            DateField::new('startDate', 'Дата начала'),
            DateField::new('endDate', 'Дата окончания'),
            TextareaField::new('comment', 'Комментарий')->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->validateBooking($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->validateBooking($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function validateBooking(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof AdvertisementBooking) {
            return;
        }

        if ($entityInstance->getAdvertisement() === null || $entityInstance->getStartDate() === null || $entityInstance->getEndDate() === null || $entityInstance->getSideCode() === null) {
            throw new \InvalidArgumentException('Заполните конструкцию, сторону и обе даты бронирования.');
        }

        if ($entityInstance->getEndDate() < $entityInstance->getStartDate()) {
            throw new \InvalidArgumentException('Дата окончания не может быть раньше даты начала.');
        }

        $sideCode = mb_strtoupper(trim($entityInstance->getSideCode()));
        $entityInstance->setSideCode($sideCode);

        if (!in_array($sideCode, $entityInstance->getAdvertisement()->getSides(), true)) {
            throw new \InvalidArgumentException('У выбранной конструкции нет указанной стороны.');
        }

        if ($this->bookingRepository->hasOverlap(
            $entityInstance->getAdvertisement(),
            $sideCode,
            $entityInstance->getStartDate(),
            $entityInstance->getEndDate(),
            $entityInstance->getId(),
        )) {
            throw new \InvalidArgumentException('Этот период уже занят для выбранной стороны конструкции.');
        }
    }

    /**
     * @return array<string, string>
     */
    private function getClientChoices(): array
    {
        $choices = [];

        foreach ($this->userRepository->findBy([], ['first_name' => 'ASC']) as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $fullName = trim(sprintf('%s %s', (string) $user->getFirstName(), (string) $user->getLastName()));
            $label = $fullName !== ''
                ? sprintf('%s (%s)', $fullName, (string) $user->getEmail())
                : (string) $user->getEmail();

            $choices[$label] = $fullName !== '' ? $fullName : (string) $user->getEmail();
        }

        return $choices;
    }
}
