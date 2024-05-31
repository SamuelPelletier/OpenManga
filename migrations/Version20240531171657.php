<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240531171657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga ADD external_id INT DEFAULT NULL, ADD external_token VARCHAR(255) DEFAULT NULL, ADD is_old TINYINT(1) DEFAULT 0 NOT NULL, ADD is_blocked TINYINT(1) DEFAULT 0 NOT NULL, ADD is_corrupted TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E039F75D7B0 ON manga (external_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_765A9E039F75D7B0 ON manga');
        $this->addSql('ALTER TABLE manga DROP external_id, DROP external_token, DROP is_old, DROP is_blocked, DROP is_corrupted');
    }
}
