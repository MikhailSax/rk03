<?php

namespace App\Controller\Admin;

use App\Entity\Advertisement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField; // Добавлен импорт
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Advertisement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Рекламная конструкция')
            ->setEntityLabelInPlural('Рекламные конструкции')
            ->setPageTitle(Crud::PAGE_INDEX, 'Рекламные конструкции')
            ->setPageTitle(Crud::PAGE_NEW, 'Добавление рекламной конструкции')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактирование рекламной конструкции')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Карточка рекламной конструкции');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TextField::new('code', 'Код'),
            TextField::new('placeNumber', 'Номер места'),
            TextareaField::new('address', 'Адрес')->hideOnIndex(),
            
            // Отображение списка сторон в таблице (Index)
            ArrayField::new('sides', 'Стороны')->onlyOnIndex(),
            
            // Выбор сторон в формах создания и редактирования
            ChoiceField::new('sides', 'Стороны')
                ->setChoices([
                    'A' => 'A',
                    'B' => 'B',
                    'A1' => 'A1',
                    'A2' => 'A2',
                    'A3' => 'A3',
                    'C' => 'C',
                    'D' => 'D',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false)
                ->onlyOnForms(),

            AssociationField::new('type', 'Тип рекламной продукции'),
            TextField::new('categoryName', 'Категория')->onlyOnIndex(),
            NumberField::new('latitude', 'Широта')->setNumDecimals(6),
            NumberField::new('longitude', 'Долгота')->setNumDecimals(6),
        ];
    }
}