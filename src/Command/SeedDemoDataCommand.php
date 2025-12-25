<?php

namespace App\Command;

use App\Entity\Disponibilite;
use App\Entity\Medecin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-demo-data',
    description: 'Seed demo doctor and availability slots for local testing'
)]
class SeedDemoDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(Medecin::class);
        $existing = $repo->findOneBy(['nom' => 'Demo']);
        if ($existing) {
            $output->writeln('<comment>Demo medecin already exists, skipping.</comment>');
            return Command::SUCCESS;
        }

        $med = new Medecin();
        $med->setNom('Demo');
        $med->setPrenom('Doctor');
        $med->setIsGeneraliste(true);

        $this->em->persist($med);

        // Create availability for the next 7 days, 9:00, 10:00, 11:00
        $now = new \DateTimeImmutable();
        for ($d = 1; $d <= 7; $d++) {
            foreach ([9, 10, 11] as $hour) {
                $start = $now->modify("+{$d} days")->setTime($hour, 0, 0);
                $end = $start->modify('+45 minutes');

                $dispo = new Disponibilite();
                $dispo->setMedecin($med);
                $dispo->setDebut($start);
                $dispo->setFin($end);
                $dispo->setEstLibre(true);

                $this->em->persist($dispo);
            }
        }

        $this->em->flush();

        $output->writeln('<info>Seeded demo medecin and availability slots.</info>');
        return Command::SUCCESS;
    }
}
