<?php

namespace App\DataFixtures;

use App\Entity\AdvertisementType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AdvertisementTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = [
            'Щиты 6x3',
            'Призматроны 12x3',
            'Ситиборды',
            'Суперсайты',
            'Брандмауэры',
        ];

        foreach ($types as $typeName) {
            $type = new AdvertisementType();
            $type->setName($typeName);

            $manager->persist($type);
        }

        $manager->flush();
    }
}
