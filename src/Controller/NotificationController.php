<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/list', name: 'notifications_list', methods: ['GET'])]
    public function list(NotificationRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $patient = $this->getUser();

        $notifications = $repo->findByPatient($patient, 20);

        $data = array_map(function($n) {
            return [
                'id' => $n->getId(),
                'title' => $n->getTitle(),
                'message' => $n->getMessage(),
                'isRead' => $n->isRead(),
                'createdAt' => $n->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return new JsonResponse([
            'notifications' => $data,
            'unread' => $repo->countUnread($patient),
        ]);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function markRead(int $id, Request $request, NotificationRepository $repo, CsrfTokenManagerInterface $csrf): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $patient = $this->getUser();

        $token = $request->headers->get('X-CSRF-Token');
        if (! $csrf->isTokenValid(new CsrfToken('notifications', (string) $token))) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $ok = $repo->markAsRead($id, $patient);

        if (! $ok) {
            return new JsonResponse(['success' => false], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    #[Route('/read-all', name: 'notifications_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request, NotificationRepository $repo, CsrfTokenManagerInterface $csrf): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $patient = $this->getUser();

        $token = $request->headers->get('X-CSRF-Token');
        if (! $csrf->isTokenValid(new CsrfToken('notifications', (string) $token))) {
            return new JsonResponse(['updated' => 0, 'error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $updated = $repo->markAllRead($patient);

        return new JsonResponse(['updated' => $updated], Response::HTTP_OK);
    }

    #[Route('/count', name: 'notifications_unread_count', methods: ['GET'])]
    public function unreadCount(NotificationRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $patient = $this->getUser();

        return new JsonResponse(['unread' => $repo->countUnread($patient)]);
    }
}