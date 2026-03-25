<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325151845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD adresse_livraison VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD ville_livraison VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD pays_livraison VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP adresse_livraison');
        $this->addSql('ALTER TABLE commande DROP ville_livraison');
        $this->addSql('ALTER TABLE commande DROP pays_livraison');
    }
}
