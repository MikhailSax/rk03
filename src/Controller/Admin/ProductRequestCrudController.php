<?php

namespace App\Controller\Admin;

use App\Entity\ProductRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('заявка')
            ->setEntityLabelInPlural('Заявки по продуктам')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle(Crud::PAGE_INDEX, 'Заявки по продуктам')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Карточка заявки');
    }


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('createdAt', 'Создано')->hideOnForm(),
            AssociationField::new('advertisement', 'Конструкция')->setFormTypeOption('disabled', true),
            TextField::new('sideCode', 'Сторона')->setFormTypeOption('disabled', true),
            TextField::new('contactName', 'Контактное лицо')->setFormTypeOption('disabled', true),
            TextField::new('contactPhone', 'Телефон')->setFormTypeOption('disabled', true),
            TextareaField::new('comment', 'Комментарий')->hideOnIndex()->setFormTypeOption('disabled', true),
        ];
    }
}
