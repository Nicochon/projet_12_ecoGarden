<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setPseudo($faker->firstName());
            $user->setEmail($faker->email);
            $user->setCity($faker->city());
            $user->setRoles(['ROLE_USER']);

            $plainPassword = 'password' . $i;
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $manager->flush();

            $months = implode(',', $faker->randomElements(range(1, 12), $faker->numberBetween(1, 3)));
            $advice = new Advice();
            $advice->setMonth(explode(',', $months));
            $advice->setAdvice($faker->sentence());
            $manager->persist($advice);
            $manager->flush();
        }
    }
}
