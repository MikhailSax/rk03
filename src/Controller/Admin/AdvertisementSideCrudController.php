<?php

namespace App\Controller\Admin;

use App\Entity\AdvertisementSide;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class AdvertisementSideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdvertisementSide::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Сторона конструкции')
            ->setEntityLabelInPlural('Стороны конструкций')
            ->setPageTitle(Crud::PAGE_INDEX, 'Стороны рекламных конструкций')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление стороны')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактирование стороны')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Карточка стороны');
    }

    public function configureFields(string $pageName): iterable
    {
        $imagePreview = ImageField::new('image', 'Изображение стороны')
            ->setBasePath('/uploads/advertisements/')
            ->hideOnForm();

        $imageUpload = ImageField::new('image', 'Загрузить изображение стороны')
            ->setBasePath('/uploads/advertisements/')
            ->setUploadDir('public/uploads/advertisements/')
            ->setUploadedFileNamePattern('side-[randomhash].[extension]')
            ->setRequired(false)
            ->setFormTypeOption('attr.accept', 'image/*')
            ->onlyOnForms();


        $nightImagePreview = ImageField::new('nightImage', 'Ночное фото')
            ->setBasePath('/uploads/advertisements/')
            ->hideOnForm();

        $nightImageUpload = ImageField::new('nightImage', 'Загрузить ночное фото')
            ->setBasePath('/uploads/advertisements/')
            ->setUploadDir('public/uploads/advertisements/')
            ->setUploadedFileNamePattern('side-night-[randomhash].[extension]')
            ->setRequired(false)
            ->setFormTypeOption('attr.accept', 'image/*')
            ->onlyOnForms();

        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('advertisement', 'Конструкция')
                ->setRequired(true),
            ChoiceField::new('code', 'Сторона')
                ->setChoices([
                    'A' => 'A',
                    'B' => 'B',
                    'A1' => 'A1',
                    'A2' => 'A2',
                    'A3' => 'A3',
                    'C' => 'C',
                    'D' => 'D',
                ])
                ->setRequired(true),
            TextareaField::new('description', 'Описание')->hideOnIndex(),
            MoneyField::new('price', 'Цена')
                ->setCurrency('RUB')
                ->setStoredAsCents(false),
            $imagePreview,
            $nightImagePreview,
            $imageUpload,
            $nightImageUpload,
        ];
    }
}
