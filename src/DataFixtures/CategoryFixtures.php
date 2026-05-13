<?php

namespace App\DataFixtures;

use App\Entity\AdvertisementCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'billboards'   => 'Щиты',
            'prismatrons'  => 'Призматроны',
            'ledscreens'   => 'Видеоэкраны',
            'brandmauers'  => 'Брандмауэры',
            'other'        => 'Прочие',
        ];

        foreach ($categories as $key => $name) {
            $cat = new AdvertisementCategory();
            $cat->setName($name);
            $manager->persist($cat);

            $this->addReference('category_' . $key, $cat);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['categories'];
    }
}
