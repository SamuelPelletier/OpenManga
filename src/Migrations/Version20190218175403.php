<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190218175403 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, link VARCHAR(255) NOT NULL, count_pages INT NOT NULL, published_at DATETIME NOT NULL, INDEX IDX_765A9E03F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_tags (manga_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D4A1DD177B6461 (manga_id), UNIQUE INDEX UNIQ_D4A1DD17BAD26311 (tag_id), PRIMARY KEY(manga_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_389B7835E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE symfony_demo_user (id INT AUTO_INCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_8FB094A1F85E0677 (username), UNIQUE INDEX UNIQ_8FB094A1E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E03F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE mangas_tags ADD CONSTRAINT FK_D4A1DD177B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_tags ADD CONSTRAINT FK_D4A1DD17BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E03F675F31B');
        $this->addSql('ALTER TABLE mangas_tags DROP FOREIGN KEY FK_D4A1DD177B6461');
        $this->addSql('ALTER TABLE mangas_tags DROP FOREIGN KEY FK_D4A1DD17BAD26311');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE manga');
        $this->addSql('DROP TABLE mangas_tags');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE symfony_demo_user');
    }
}
