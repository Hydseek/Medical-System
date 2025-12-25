<?php

namespace App\Controller;

use App\Entity\Medecin;
use App\Entity\RendezVous;
use App\Repository\DisponibiliteRepository;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RendezVousController extends AbstractController
{
    #[Route('/rendez-vous', name: 'rendez_vous_step_1')]
    public function step1(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Require user to be logged in as a Patient
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Patient) {
            $this->addFlash('error', 'You must be logged in as a patient to book an appointment.');
            return $this->redirectToRoute('app_connexion');
        }

        // Prevent admins from booking appointments
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Administrators cannot book appointments. Please use the admin dashboard.');
            return $this->redirectToRoute('admin_profile_dashboard');
        }

        $medecins = $entityManager->getRepository(Medecin::class)->findBy(['isGeneraliste' => true]);

        if (empty($medecins)) {
            $this->addFlash('error', 'No doctors available');
            return $this->redirectToRoute('app_accueil');
        }

        $form = $this->createFormBuilder()
            ->add('medecin', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($m) => $m->getNom() . ' ' . $m->getPrenom(), $medecins),
                    array_map(fn($m) => $m->getId(), $medecins)
                ),
                'label' => 'Choose a doctor'
            ])
            ->add('submit', SubmitType::class, ['label' => 'Continue'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $medecinId = $form->get('medecin')->getData();
            $request->getSession()->set('medecin_id', $medecinId);
            return $this->redirectToRoute('rendez_vous_step_2');
        }

        return $this->render('rendez_vous/reserve_medecin.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rendez-vous/choisir-disponibilite', name: 'rendez_vous_step_2')]
    public function step2(Request $request, MedecinRepository $medecinRepo, DisponibiliteRepository $dispoRepo): Response
    {
        // Prevent admins from booking appointments
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Administrators cannot book appointments.');
            return $this->redirectToRoute('admin_profile_dashboard');
        }

        $medecinId = $request->getSession()->get('medecin_id');
        if (!$medecinId) {
            $this->addFlash('error', 'No doctor selected.');
            return $this->redirectToRoute('rendez_vous_step_1');
        }

        $medecin = $medecinRepo->find($medecinId);
        if (!$medecin) {
            $this->addFlash('error', 'Doctor not found.');
            return $this->redirectToRoute('rendez_vous_step_1');
        }

        $disponibilites = $dispoRepo->findDisponibilitesByMedecin($medecin);

        // If no available slots, notify the patient and redirect
        if (empty($disponibilites)) {
            $this->addFlash('error', 'There are currently no available time slots for this doctor. Please choose another doctor or contact the clinic.');
            return $this->redirectToRoute('rendez_vous_step_1');
        }

        $form = $this->createFormBuilder()
            ->add('disponibilite', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($d) => $d->getDebut()->format('d/m/Y H:i'), $disponibilites),
                    array_map(fn($d) => $d->getId(), $disponibilites)
                ),
                'label' => 'Choose an available slot',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Continue'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $disponibiliteId = $form->get('disponibilite')->getData();
            $request->getSession()->set('disponibilite_id', $disponibiliteId);
            return $this->redirectToRoute('rendez_vous_step_3');
        }

        return $this->render('rendez_vous/disponibilite.html.twig', [
            'form' => $form->createView(),
            'medecin' => $medecin,
        ]);
    }

    #[Route('/rendez-vous/confirmation', name: 'rendez_vous_step_3')]
    public function step3(
        Request $request,
        EntityManagerInterface $em,
        DisponibiliteRepository $dispoRepo,
        MedecinRepository $medecinRepo
    ): Response {
        // Prevent admins from booking appointments
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Administrators cannot book appointments.');
            return $this->redirectToRoute('admin_profile_dashboard');
        }

        $medecinId = $request->getSession()->get('medecin_id');
        $disponibiliteId = $request->getSession()->get('disponibilite_id');

        if (!$medecinId || !$disponibiliteId) {
            $this->addFlash('error', 'Missing data for confirmation.');
            return $this->redirectToRoute('rendez_vous_step_1');
        }

        $medecin = $medecinRepo->find($medecinId);
        $disponibilite = $dispoRepo->find($disponibiliteId);

        if (!$disponibilite) {
            $this->addFlash('error', 'This time slot is no longer valid or has already been booked.');
            return $this->redirectToRoute('rendez_vous_step_1');
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Patient) {
            throw new \LogicException('The authenticated user is not a patient.');
        }

        $form = $this->createFormBuilder()
            ->add('confirm', SubmitType::class, ['label' => 'Confirm appointment'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rendezVous = new RendezVous();
            $rendezVous->setMedecin($medecin);
            $rendezVous->setDisponibilite($disponibilite);
            $rendezVous->setPatient($user);

            // ✅ Ne plus supprimer, juste la marquer comme utilisée
            $disponibilite->setEstLibre(false);

            $em->persist($rendezVous);
            $em->flush();

            // UX: flash success and clear session
            $this->addFlash('success', 'Appointment booked successfully.');

            $request->getSession()->remove('medecin_id');
            $request->getSession()->remove('disponibilite_id');

            return $this->redirectToRoute('rendez_vous_success', ['id' => $rendezVous->getId()]);
        }

        return $this->render('rendez_vous/confirmation.html.twig', [
            'form' => $form->createView(),
            'medecin' => $medecin,
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/rendez-vous/success/{id}', name: 'rendez_vous_success')]
    public function success(RendezVous $rendezVous): Response
    {
        return $this->render('rendez_vous/success.html.twig', [
            'rendezVous' => $rendezVous,
        ]);
    }

    #[Route('/mes-rendezvous', name: 'mes_rendezvous')]
    public function mesRendezvous(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\Patient) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $rendezvous = $em->getRepository(RendezVous::class)->findBy(['patient' => $user], ['id' => 'ASC']);

        return $this->render('patient/rendezvous.html.twig', [
            'rendezvous' => $rendezvous,
            'patient' => $user,
        ]);
    }

    #[Route('/rendez-vous/annuler/{id}', name: 'rendez_vous_annuler', methods: ['GET', 'POST'])]
    public function annuler(RendezVous $rendezVous, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Patient || $rendezVous->getPatient()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You cannot cancel this appointment.');
        }

        // ✅ Libère la disponibilité sans la recréer
        $rendezVous->getDisponibilite()->setEstLibre(true);

        $em->remove($rendezVous);
        $em->flush();

        $this->addFlash('success', 'Appointment cancelled successfully.');

        return $this->redirectToRoute('mes_rendezvous');
    }
}
