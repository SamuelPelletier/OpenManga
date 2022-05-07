<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200519171935 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, time_spent INT NOT NULL, count_mangas_read INT NOT NULL, count_mangas_download INT NOT NULL, points INT NOT NULL, bonus_points INT NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_manga_read (user_id INT NOT NULL, manga_id INT NOT NULL, INDEX IDX_60646EC1A76ED395 (user_id), INDEX IDX_60646EC17B6461 (manga_id), PRIMARY KEY(user_id, manga_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_manga_favorite (user_id INT NOT NULL, manga_id INT NOT NULL, INDEX IDX_74D9D6C3A76ED395 (user_id), INDEX IDX_74D9D6C37B6461 (manga_id), PRIMARY KEY(user_id, manga_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_manga_read ADD CONSTRAINT FK_60646EC17B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE user_manga_favorite ADD CONSTRAINT FK_74D9D6C3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_manga_favorite ADD CONSTRAINT FK_74D9D6C37B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE user_manga_read DROP FOREIGN KEY FK_60646EC1A76ED395');
        $this->addSql('ALTER TABLE user_manga_favorite DROP FOREIGN KEY FK_74D9D6C3A76ED395');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_manga_read');
        $this->addSql('DROP TABLE user_manga_favorite');
    }
}
