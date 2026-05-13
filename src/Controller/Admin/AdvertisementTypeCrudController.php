<?php

namespace App\Controller\Admin;

use App\Entity\AdvertisementType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdvertisementType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Тип рекламной продукции')
            ->setEntityLabelInPlural('Типы рекламной продукции')
            ->setPageTitle(Crud::PAGE_INDEX, 'Типы рекламной продукции')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление типа рекламной продукции')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактирование типа рекламной продукции');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TextField::new('name', 'Название типа'),
            AssociationField::new('category', 'Категория'),
        ];
    }
}
