<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219160814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_plat DROP CONSTRAINT fk_e8775249ccd7e912');
        $this->addSql('ALTER TABLE menu_plat DROP CONSTRAINT fk_e8775249d73db560');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE plat ALTER photo DROP NOT NULL');
        $this->addSql('ALTER TABLE plat_allergene DROP CONSTRAINT fk_6fa44bbf4646ab2');
        $this->addSql('ALTER TABLE plat_allergene DROP CONSTRAINT fk_6fa44bbfd73db560');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT FK_6FA44BBF4646AB2 FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT FK_6FA44BBFD73DB560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_plat DROP CONSTRAINT FK_E8775249CCD7E912');
        $this->addSql('ALTER TABLE menu_plat DROP CONSTRAINT FK_E8775249D73DB560');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT fk_e8775249ccd7e912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT fk_e8775249d73db560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE plat ALTER photo SET NOT NULL');
        $this->addSql('ALTER TABLE plat_allergene DROP CONSTRAINT FK_6FA44BBFD73DB560');
        $this->addSql('ALTER TABLE plat_allergene DROP CONSTRAINT FK_6FA44BBF4646AB2');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT fk_6fa44bbfd73db560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT fk_6fa44bbf4646ab2 FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
