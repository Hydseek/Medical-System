<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251224233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove TOTP columns from patient when removing 2FA implementation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient DROP totp_secret, DROP totp_enabled');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patient ADD totp_secret VARCHAR(255) DEFAULT NULL, ADD totp_enabled TINYINT(1) NOT NULL');
    }
}
