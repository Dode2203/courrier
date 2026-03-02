<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223110952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE courriers DROP CONSTRAINT fk_2db37181f915cfe');
        $this->addSql('DROP INDEX idx_2db37181f915cfe');
        $this->addSql('ALTER TABLE courriers ADD nom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE courriers ADD prenom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE courriers DROP fichier_id');
        $this->addSql('ALTER TABLE fichiers ADD courrier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fichiers ADD CONSTRAINT FK_969DB4AB8BF41DC7 FOREIGN KEY (courrier_id) REFERENCES courriers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_969DB4AB8BF41DC7 ON fichiers (courrier_id)');
        $this->addSql('ALTER TABLE utilisateurs ADD adresse VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE courriers ADD fichier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE courriers DROP nom');
        $this->addSql('ALTER TABLE courriers DROP prenom');
        $this->addSql('ALTER TABLE courriers ADD CONSTRAINT fk_2db37181f915cfe FOREIGN KEY (fichier_id) REFERENCES fichiers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_2db37181f915cfe ON courriers (fichier_id)');
        $this->addSql('ALTER TABLE utilisateurs DROP adresse');
        $this->addSql('ALTER TABLE fichiers DROP CONSTRAINT FK_969DB4AB8BF41DC7');
        $this->addSql('DROP INDEX IDX_969DB4AB8BF41DC7');
        $this->addSql('ALTER TABLE fichiers DROP courrier_id');
    }
}
