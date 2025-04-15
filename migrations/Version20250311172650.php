<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250311172650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga ADD price INT NOT NULL, ADD translation_from_id INT DEFAULT NULL, ADD creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E03D9C220F9 FOREIGN KEY (translation_from_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E0361220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_765A9E03D9C220F9 ON manga (translation_from_id)');
        $this->addSql('CREATE INDEX IDX_765A9E0361220EA6 ON manga (creator_id)');
        $this->addSql('ALTER TABLE payment ADD manga_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D7B6461 ON payment (manga_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D7B6461');
        $this->addSql('DROP INDEX IDX_6D28840D7B6461 ON payment');
        $this->addSql('ALTER TABLE payment DROP manga_id');
        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E03D9C220F9');
        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E0361220EA6');
        $this->addSql('DROP INDEX IDX_765A9E03D9C220F9 ON manga');
        $this->addSql('DROP INDEX IDX_765A9E0361220EA6 ON manga');
        $this->addSql('ALTER TABLE manga DROP price, DROP translation_from_id, DROP creator_id');
    }
}
