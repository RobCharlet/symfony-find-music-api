<?php

namespace App\DataFixtures;

use App\Factory\AlbumFactory;
use App\Factory\ExternalReferenceFactory;
use App\Factory\SecurityUserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Créer des utilisateurs
        $users = SecurityUserFactory::createMany(10);

        // 2. Créer des albums avec des propriétaires aléatoires
        $albums = AlbumFactory::createMany(20, function () use ($users) {
            return [
                'ownerUuid' => $users[array_rand($users)]->getUuid(),
            ];
        });

        // 3. Créer des références externes pour chaque album (1 à 3 par album)
        foreach ($albums as $album) {
            ExternalReferenceFactory::createMany(rand(1, 3), [
                'album' => $album,
            ]);
        }

        $manager->flush();
    }
}
