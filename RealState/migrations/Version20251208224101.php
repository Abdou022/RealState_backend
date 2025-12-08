<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208224101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE house ADD CONSTRAINT FK_67D5399D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_67D5399D7E3C61F9 ON house (owner_id)');
        $this->addSql('ALTER TABLE offer ADD creator_id INT NOT NULL, ADD applicant_id INT NOT NULL');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E97139001 FOREIGN KEY (applicant_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_29D6873E61220EA6 ON offer (creator_id)');
        $this->addSql('CREATE INDEX IDX_29D6873E97139001 ON offer (applicant_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE house DROP FOREIGN KEY FK_67D5399D7E3C61F9');
        $this->addSql('DROP INDEX IDX_67D5399D7E3C61F9 ON house');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E61220EA6');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E97139001');
        $this->addSql('DROP INDEX IDX_29D6873E61220EA6 ON offer');
        $this->addSql('DROP INDEX IDX_29D6873E97139001 ON offer');
        $this->addSql('ALTER TABLE offer DROP creator_id, DROP applicant_id');
    }
}
