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
    name: 'app:create-demo-patient',
    description: 'Create a demo patient with credentials for testing.'
)]
class CreateDemoPatientCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(Patient::class);
        $existing = $repo->findOneBy(['email' => 'demo.patient@example.com']);
        if ($existing) {
            $output->writeln('<comment>Demo patient already exists, skipping.</comment>');
            return Command::SUCCESS;
        }

        $p = new Patient();
        $p->setNom('Demo');
        $p->setPrenom('Patient');
        $p->setEmail('demo.patient@example.com');
        $p->setDateNaissance(new \DateTime('1990-01-01'));
        $p->setAdresse('Demo Address');
        $p->setTelephone('000000000');
        $p->setRoles(['ROLE_PATIENT']);
        $p->setPassword($this->hasher->hashPassword($p, 'secret'));

        $this->em->persist($p);
        $this->em->flush();

        $output->writeln('<info>Demo patient created: demo.patient@example.com / secret</info>');
        return Command::SUCCESS;
    }
}
