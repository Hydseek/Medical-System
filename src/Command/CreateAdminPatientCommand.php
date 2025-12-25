<?php

namespace App\Command;

use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-patient',
    description: 'Create admin inside patient table'
)]
class CreateAdminPatientCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
{
    $email = 'admin@gmail.com';

    // Check if admin already exists
    $existing = $this->em->getRepository(Patient::class)->findOneBy(['email' => $email]);
    if ($existing) {
        $output->writeln('<comment>Admin with email "' . $email . '" already exists, skipping.</comment>');
        return Command::SUCCESS;
    }

    $admin = new Patient();

    // REQUIRED FIELDS
    $admin->setNom('Admin');
    $admin->setPrenom('System');
    $admin->setEmail($email);
    $admin->setDateNaissance(new \DateTime('1990-01-01'));
    $admin->setAdresse('System Address');
    $admin->setTelephone('00000000');

    // SECURITY
    $admin->setRoles(['ROLE_ADMIN']);
    $admin->setPassword(
        $this->hasher->hashPassword($admin, 'admin')
    );

    $this->em->persist($admin);
    $this->em->flush();

    $output->writeln('<info>Admin patient created successfully!</info>');

    return Command::SUCCESS;
}

}
