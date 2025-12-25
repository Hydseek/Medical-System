<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Form\ChangePasswordFormType;
use App\Form\PatientEditType;
use App\Form\PatientType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PatientController extends AbstractController
{
    #[IsGranted('ROLE_PATIENT')]
    #[Route('/patient/{id}', name: 'patient_profile')]
    public function profile(Patient $patient): Response
    {
        // Prevent admins from accessing patient pages
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Admins must use the admin dashboard.');
        }

        // Verify the logged-in user is viewing their own profile
        if ($this->getUser()->getId() !== $patient->getId()) {
            throw new AccessDeniedException('You can only view your own profile.');
        }

        return $this->render('patient/index.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[IsGranted('ROLE_PATIENT')]
    #[Route('/patient/{id}/rendez-vous', name: 'patient_rendezvous')]
    public function rendezVous(Patient $patient): Response
    {
        // Prevent admins from accessing patient pages
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Admins must use the admin dashboard.');
        }

        // Verify the logged-in user is viewing their own appointments
        if ($this->getUser()->getId() !== $patient->getId()) {
            throw new AccessDeniedException('You can only view your own appointments.');
        }

        return $this->render('patient/rendezvous.html.twig', [
            'patient' => $patient,
            'rendezvous' => $patient->getRendezVous(),
        ]);
    }


    #[IsGranted('ROLE_PATIENT')]
    #[Route('/patient/{id}/edit', name: 'patient_edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(Request $request, Patient $patient, EntityManagerInterface $em): Response
    {
        // Prevent admins from accessing patient pages
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Admins must use the admin dashboard.');
        }

        // Verify the logged-in user is editing their own profile
        if ($this->getUser()->getId() !== $patient->getId()) {
            throw new AccessDeniedException('You can only edit your own profile.');
        }

        $form = $this->createForm(PatientEditType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('patient_profile', ['id' => $patient->getId()]);
        }

        return $this->render('patient/edit.html.twig', [
            'form' => $form->createView(),
            'patient' => $patient,
        ]);
    }

    #[IsGranted('ROLE_PATIENT')]
    #[Route('/patient/{id}/change-password', name: 'patient_change_password')]
    public function changePassword(
        Patient $patient,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Prevent admins from accessing patient pages
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Admins must use the admin dashboard.');
        }

        // Verify the logged-in user is editing their own password
        if ($this->getUser()->getId() !== $patient->getId()) {
            throw new AccessDeniedException('You can only change your own password.');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            // Verify current password is correct
            if (!$passwordHasher->isPasswordValid($patient, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
            } else {
                // Hash and set new password
                $hashedPassword = $passwordHasher->hashPassword($patient, $newPassword);
                $patient->setPassword($hashedPassword);

                $entityManager->persist($patient);
                $entityManager->flush();

                $this->addFlash('success', 'Password updated successfully.');
                return $this->redirectToRoute('patient_profile', ['id' => $patient->getId()]);
            }
        }

        return $this->render('patient/changepassword.html.twig', [
            'form' => $form->createView(),
            'patient' => $patient,
        ]);
    }
}
