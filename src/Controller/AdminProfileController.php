<?php

namespace App\Controller;

use App\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\PatientEditType;
use App\Form\ChangePasswordFormType;

#[IsGranted('ROLE_ADMIN')]
final class AdminProfileController extends AbstractController
{
    #[Route('/admin-profile/dashboard', name: 'admin_profile_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Patient) {
            throw $this->createAccessDeniedException('Admin must be a Patient user.');
        }

        return $this->render('admin_profile/dashboard.html.twig', [
            'admin' => $user,
        ]);
    }

    #[Route('/admin-profile/my-info', name: 'admin_profile_info')]
    public function myInfo(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Patient) {
            throw $this->createAccessDeniedException('Admin must be a Patient user.');
        }

        // Render a read-only admin info page; editing is available via the Edit button
        return $this->render('admin_profile/info.html.twig', [
            'admin' => $user,
        ]);
    }

    #[Route('/admin-profile/edit', name: 'admin_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Patient) {
            throw $this->createAccessDeniedException('Admin must be a Patient user.');
        }

        $form = $this->createForm(PatientEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('admin_profile_dashboard');
        }

        return $this->render('admin_profile/edit.html.twig', [
            'form' => $form->createView(),
            'admin' => $user,
        ]);
    }

    #[Route('/admin-profile/change-password', name: 'admin_profile_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Patient) {
            throw $this->createAccessDeniedException('Admin must be a Patient user.');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Password updated successfully.');
                return $this->redirectToRoute('admin_profile_dashboard');
            }
        }

        return $this->render('admin_profile/changepassword.html.twig', [
            'form' => $form->createView(),
            'admin' => $user,
        ]);
    }
}
