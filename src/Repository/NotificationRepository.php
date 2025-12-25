<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Patient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[]
     */
    public function findByPatient(Patient $patient, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnread(Patient $patient): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.patient = :patient')
            ->andWhere('n.isRead = false')
            ->setParameter('patient', $patient)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAsRead(int $id, Patient $patient): bool
    {
        $em = $this->getEntityManager();
        $notification = $this->findOneBy(['id' => $id, 'patient' => $patient]);
        if (!$notification) {
            return false;
        }

        $notification->setIsRead(true);
        $em->persist($notification);
        $em->flush();

        return true;
    }

    public function markAllRead(Patient $patient): int
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $q = $qb->update(Notification::class, 'n')
            ->set('n.isRead', ':true')
            ->where('n.patient = :patient')
            ->andWhere('n.isRead = false')
            ->setParameter('true', true)
            ->setParameter('patient', $patient)
            ->getQuery();

        return $q->execute();
    }
}
