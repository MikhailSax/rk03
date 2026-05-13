<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Заказ')
            ->setEntityLabelInPlural('Заказы')
            ->setPageTitle(Crud::PAGE_INDEX, 'Заказы');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('status', 'Статус')->setChoices([
                'Ожидает оплату' => Order::STATUS_PENDING,
                'Оплачен' => Order::STATUS_PAID,
                'Отменен' => Order::STATUS_CANCELLED,
            ]),
            TextField::new('contactName', 'Контакт'),
            TextField::new('contactPhone', 'Телефон'),
            TextareaField::new('comment', 'Комментарий')->hideOnIndex(),
            AssociationField::new('user', 'Пользователь')->hideOnIndex(),
            CollectionField::new('items', 'Позиции')->onlyOnDetail(),
            DateTimeField::new('createdAt', 'Создан'),
            DateTimeField::new('reservedUntil', 'Бронь до'),
            DateTimeField::new('expiredAt', 'Снято')->hideOnForm(),
        ];
    }
}
