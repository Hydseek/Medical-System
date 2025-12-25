<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224212409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1BDA53C6E7927C74 ON medecin');
        $this->addSql('ALTER TABLE medecin DROP email, DROP mot_de_passe, DROP roles');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP INDEX idx_bf5476caa76ed395 ON notification');
        $this->addSql('CREATE INDEX IDX_BF5476CA6B899279 ON notification (patient_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('ALTER TABLE user CHANGE usernom usernom VARCHAR(180) NOT NULL, CHANGE mot_de_passe password VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649AD414310 ON user (usernom)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE medecin ADD email VARCHAR(255) NOT NULL, ADD mot_de_passe VARCHAR(255) NOT NULL, ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1BDA53C6E7927C74 ON medecin (email)');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA6B899279');
        $this->addSql('DROP INDEX idx_bf5476ca6b899279 ON notification');
        $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (patient_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA6B899279 FOREIGN KEY (patient_id) REFERENCES patient (id)');
        $this->addSql('DROP INDEX UNIQ_8D93D649AD414310 ON user');
        $this->addSql('ALTER TABLE user CHANGE usernom usernom VARCHAR(255) NOT NULL, CHANGE password mot_de_passe VARCHAR(255) NOT NULL');
    }
}
