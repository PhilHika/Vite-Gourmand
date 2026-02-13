<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213095307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE menu_plat (menu_id INT NOT NULL, plat_id INT NOT NULL, PRIMARY KEY (menu_id, plat_id))');
        $this->addSql('CREATE INDEX IDX_E8775249CCD7E912 ON menu_plat (menu_id)');
        $this->addSql('CREATE INDEX IDX_E8775249D73DB560 ON menu_plat (plat_id)');
        $this->addSql('CREATE TABLE plat_allergene (plat_id INT NOT NULL, allergene_id INT NOT NULL, PRIMARY KEY (plat_id, allergene_id))');
        $this->addSql('CREATE INDEX IDX_6FA44BBFD73DB560 ON plat_allergene (plat_id)');
        $this->addSql('CREATE INDEX IDX_6FA44BBF4646AB2 ON plat_allergene (allergene_id)');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT FK_6FA44BBFD73DB560 FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT FK_6FA44BBF4646AB2 FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD utilisateur_id INT NOT NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "utilisateur" (utilisateur_id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_8F91ABF0FB88E14F ON avis (utilisateur_id)');
        $this->addSql('ALTER TABLE commande ADD utilisateur_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD menu_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "utilisateur" (utilisateur_id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DCCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (menu_id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_6EEAA67DFB88E14F ON commande (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DCCD7E912 ON commande (menu_id)');
        $this->addSql('ALTER TABLE menu ADD image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE menu ADD regime_id INT DEFAULT NULL');

        // theme_id : ajout nullable → remplissage → NOT NULL
        $this->addSql('ALTER TABLE menu ADD theme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menu DROP regime');
        $this->addSql('INSERT INTO theme (libelle) SELECT \'Non défini\' WHERE NOT EXISTS (SELECT 1 FROM theme WHERE libelle = \'Non défini\')');
        $this->addSql('UPDATE menu SET theme_id = (SELECT theme_id FROM theme WHERE libelle = \'Non défini\' LIMIT 1) WHERE theme_id IS NULL');
        $this->addSql('ALTER TABLE menu ALTER COLUMN theme_id SET NOT NULL');

        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9335E7D534 FOREIGN KEY (regime_id) REFERENCES regime (regime_id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9359027487 FOREIGN KEY (theme_id) REFERENCES theme (theme_id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_7D053A9335E7D534 ON menu (regime_id)');
        $this->addSql('CREATE INDEX IDX_7D053A9359027487 ON menu (theme_id)');

        // role_id : ajout nullable → remplissage → NOT NULL
        $this->addSql('ALTER TABLE utilisateur ADD role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP roles');
        $this->addSql('INSERT INTO role (libelle) SELECT \'ROLE_USER\' WHERE NOT EXISTS (SELECT 1 FROM role WHERE libelle = \'ROLE_USER\')');
        $this->addSql('UPDATE utilisateur SET role_id = (SELECT role_id FROM role WHERE libelle = \'ROLE_USER\' LIMIT 1) WHERE role_id IS NULL');
        $this->addSql('ALTER TABLE utilisateur ALTER COLUMN role_id SET NOT NULL');

        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3D60322AC FOREIGN KEY (role_id) REFERENCES role (role_id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1D1C63B3D60322AC ON utilisateur (role_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_plat DROP CONSTRAINT FK_E8775249CCD7E912');
        $this->addSql('ALTER TABLE menu_plat DROP CONSTRAINT FK_E8775249D73DB560');
        $this->addSql('ALTER TABLE plat_allergene DROP CONSTRAINT FK_6FA44BBFD73DB560');
        $this->addSql('ALTER TABLE plat_allergene DROP CONSTRAINT FK_6FA44BBF4646AB2');
        $this->addSql('DROP TABLE menu_plat');
        $this->addSql('DROP TABLE plat_allergene');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8F91ABF0FB88E14F');
        $this->addSql('DROP INDEX IDX_8F91ABF0FB88E14F');
        $this->addSql('ALTER TABLE avis DROP utilisateur_id');
        $this->addSql('ALTER TABLE commande DROP CONSTRAINT FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP CONSTRAINT FK_6EEAA67DCCD7E912');
        $this->addSql('DROP INDEX IDX_6EEAA67DFB88E14F');
        $this->addSql('DROP INDEX IDX_6EEAA67DCCD7E912');
        $this->addSql('ALTER TABLE commande DROP utilisateur_id');
        $this->addSql('ALTER TABLE commande DROP menu_id');
        $this->addSql('ALTER TABLE menu DROP CONSTRAINT FK_7D053A9335E7D534');
        $this->addSql('ALTER TABLE menu DROP CONSTRAINT FK_7D053A9359027487');
        $this->addSql('DROP INDEX IDX_7D053A9335E7D534');
        $this->addSql('DROP INDEX IDX_7D053A9359027487');
        $this->addSql('ALTER TABLE menu ADD regime VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE menu DROP image_url');
        $this->addSql('ALTER TABLE menu DROP regime_id');
        $this->addSql('ALTER TABLE menu DROP theme_id');
        $this->addSql('ALTER TABLE "utilisateur" DROP CONSTRAINT FK_1D1C63B3D60322AC');
        $this->addSql('DROP INDEX IDX_1D1C63B3D60322AC');
        $this->addSql('ALTER TABLE "utilisateur" ADD roles JSON NOT NULL');
        $this->addSql('ALTER TABLE "utilisateur" DROP role_id');
    }
}
