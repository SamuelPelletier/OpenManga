<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200725172700 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC17B6461');
        $this->addSql('ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC1A76ED395');
        $this->addSql('ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC17B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC1A76ED395');
        $this->addSql('ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC17B6461');
        $this->addSql('ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC17B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
    }
}
