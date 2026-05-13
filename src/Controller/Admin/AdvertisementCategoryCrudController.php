<?php

namespace App\Controller\Admin;

use App\Entity\AdvertisementCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdvertisementCategory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Категория рекламной продукции')
            ->setEntityLabelInPlural('Категории рекламной продукции')
            ->setPageTitle(Crud::PAGE_INDEX, 'Категории рекламной продукции')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление категории рекламной продукции')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактирование категории рекламной продукции');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TextField::new('name', 'Название категории'),
        ];
    }
}
