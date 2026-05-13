<?php

namespace App\DataFixtures;

use App\Entity\Advertisement;
use App\Entity\AdvertisementType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class AdvertisementsFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $path = __DIR__ . '/data/advertisements.json';
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $ads = json_decode((string)file_get_contents($path), true);

        foreach ($ads as $row) {
            $ad = new Advertisement();
            $ad->setCode($row['place_number']);
            $ad->setAddress($row['address'] ?? null);
            $ad->setPlaceNumber($row['place_number']);
            $ad->setSides($row['sides'] ?? []);

            $ad->setType(
                $this->getReference('type_json_' . $row['type_id'], AdvertisementType::class)
            );

            $manager->persist($ad);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TypeFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['advertisements'];
    }
}
