<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250906094806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_favorite ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC17B6461
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC17B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC17B6461
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `PRIMARY` ON user_manga_read
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read DROP id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC17B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_read ADD PRIMARY KEY (user_id, manga_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_favorite MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `PRIMARY` ON user_manga_favorite
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_favorite DROP id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_manga_favorite ADD PRIMARY KEY (user_id, manga_id)
        SQL);
    }
}
