<?php

namespace App\DataFixtures;

use App\Entity\Season;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker;

class SeasonFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        for ($i = 0; $i < 50; $i++) {
            $season = new Season();
            $season->setDescription($faker->word());
            $season->setYear($faker->year($max = 'now'));
            $season->setNumber($i);
            $season->setProgram($this->getReference('program_'.random_int(0, count(ProgramFixtures::PROGRAMS)-1)));
            $this->addReference('season_'.$i, $season);
            $manager->persist($season);
        }
        $manager->flush();
    }


    public function getDependencies()
    {
        return [ProgramFixtures::class];
    }
}