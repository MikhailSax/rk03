<?php

namespace App\DataFixtures;

use App\Entity\AdvertisementType;
use App\Entity\AdvertisementCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class TypeFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $path = __DIR__ . '/data/advertisement_types.json';
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $types = json_decode((string)file_get_contents($path), true);

        $mapping = [
            'billboards'   => ['щит'],
            'prismatrons'  => ['призматрон'],
            'ledscreens'   => ['видео', 'экран', 'led'],
            'brandmauers'  => ['брандмауэр'],
            'other'        => [],
        ];

        foreach ($types as $row) {
            $name = trim($row['name']);
            $lc   = mb_strtolower($name);

            $catKey = 'other';
            foreach ($mapping as $key => $keywords) {
                foreach ($keywords as $kw) {
                    if (mb_strpos($lc, $kw) !== false) {
                        $catKey = $key;
                        break 2;
                    }
                }
            }

            $type = new AdvertisementType();
            $type->setName($name);
            $type->setCategory(
                $this->getReference('category_' . $catKey, AdvertisementCategory::class)
            );

            $manager->persist($type);

            if (isset($row['id'])) {
                $this->addReference('type_json_' . $row['id'], $type);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['types'];
    }
}
