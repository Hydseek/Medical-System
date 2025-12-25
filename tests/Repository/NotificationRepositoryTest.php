<?php

namespace App\Tests\Repository;

use App\Entity\Patient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationRepositoryTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    public function testCountUnreadAndMarkRead()
    {
        $patientRepo = $this->entityManager->getRepository(Patient::class);
        $patient = $patientRepo->find(1);

        // Create a test patient if one is not present so the test is deterministic
        if (null === $patient) {
            $patient = new Patient();
            $patient->setNom('Test');
            $patient->setPrenom('Patient');
            $patient->setEmail('test.patient+' . uniqid() . '@example.local');
            $patient->setPassword('password');
            $patient->setDateNaissance(new \DateTime('1990-01-01'));
            $patient->setAdresse('Test address');
            $patient->setTelephone('0000000000');
            $this->entityManager->persist($patient);
            $this->entityManager->flush();
        }

        $this->assertNotNull($patient, 'Patient should exist for tests');

        $notifRepo = $this->entityManager->getRepository(\App\Entity\Notification::class);

        $unreadBefore = $notifRepo->countUnread($patient);
        $this->assertIsInt($unreadBefore);

        // create a fresh unread notification for deterministic testing
        $notif = new \App\Entity\Notification();
        $notif->setPatient($patient);
        $notif->setTitle('Test');
        $notif->setMessage('Test message');
        $notif->setIsRead(false);
        $this->entityManager->persist($notif);
        $this->entityManager->flush();

        $first = $notif;

        $this->assertFalse($first->isRead(), 'New notification should be unread');

        $ok = $notifRepo->markAsRead($first->getId(), $patient);
        $this->assertTrue($ok, 'markAsRead should return true for valid id');
        $unreadAfter = $notifRepo->countUnread($patient);
        $expected = $unreadBefore > 0 ? $unreadBefore - 1 : 0;
        $this->assertEquals($expected, $unreadAfter);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
