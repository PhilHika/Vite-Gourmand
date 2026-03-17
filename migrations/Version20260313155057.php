<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313155057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis ADD numero_commande VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE avis ALTER note TYPE SMALLINT USING note::smallint');
        $this->addSql('ALTER TABLE avis ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0CFFD611D FOREIGN KEY (numero_commande) REFERENCES commande (numero_commande) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8F91ABF0CFFD611D ON avis (numero_commande)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8F91ABF0CFFD611D');
        $this->addSql('DROP INDEX UNIQ_8F91ABF0CFFD611D');
        $this->addSql('ALTER TABLE avis DROP numero_commande');
        $this->addSql('ALTER TABLE avis ALTER note TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE avis ALTER description TYPE VARCHAR(50)');
    }
}
