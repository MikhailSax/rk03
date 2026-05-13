<?php

namespace App\DataFixtures;

use App\Entity\Advertisement;
use App\Entity\AdvertisementLocation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LocationsFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $file = __DIR__ . '/data/locations.json';
        if (!file_exists($file)) {
            throw new \RuntimeException("Файл $file не найден");
        }

        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            throw new \RuntimeException("Некорректный формат JSON в $file");
        }

        $count = 0;

        foreach ($data as $row) {
            $placeNumber = $row['place_number'] ?? null;
            if (!$placeNumber) {
                continue;
            }

            /** @var Advertisement|null $ad */
            $ad = $manager->getRepository(Advertisement::class)
                ->findOneBy(['placeNumber' => $placeNumber]);

            if (!$ad) {
                echo "⚠️ Объявление с place_number={$placeNumber} не найдено\n";
                continue;
            }

            // Проверяем, есть ли уже локация
            $location = $ad->getLocation();
            if (!$location) {
                $location = new AdvertisementLocation();
                $location->setAdvertisement($ad);
                $ad->setLocation($location);
                $manager->persist($location);
            }

            // Обновляем данные
            $location->setLatitude($row['latitude']);
            $location->setLongitude($row['longitude']);
            $location->setAzimuth($row['azimuth']);

            $manager->persist($ad);

            $count++;
        }

        $manager->flush();

        echo "✅ Загружено {$count} локаций\n";
    }

    public function getDependencies(): array
    {
        return [
            AdvertisementsFixtures::class, // сначала объявления
        ];
    }

    public static function getGroups(): array
    {
        return ['locations'];
    }
}
