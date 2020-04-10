<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200131072329 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE package ADD updated_at DATETIME NOT NULL, CHANGE data_nadania data_nadania DATETIME DEFAULT NULL, CHANGE kod_kraju_przezn kod_kraju_przezn VARCHAR(255) DEFAULT NULL, CHANGE kod_rodz_przes kod_rodz_przes VARCHAR(255) DEFAULT NULL, CHANGE masa masa DOUBLE PRECISION DEFAULT NULL, CHANGE format format VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE package DROP updated_at, CHANGE data_nadania data_nadania DATETIME DEFAULT \'NULL\', CHANGE kod_kraju_przezn kod_kraju_przezn VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE kod_rodz_przes kod_rodz_przes VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE masa masa DOUBLE PRECISION DEFAULT \'NULL\', CHANGE format format VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
    }
}
