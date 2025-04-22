<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190414164723 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof MySQLPlatform), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(190) NOT NULL, UNIQUE INDEX UNIQ_BDAFD8C85E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(190) NOT NULL, UNIQUE INDEX UNIQ_D4DB71B55E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, count_pages INT NOT NULL, published_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_authors (manga_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_E9183DD87B6461 (manga_id), INDEX IDX_E9183DD8F675F31B (author_id), PRIMARY KEY(manga_id, author_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_tags (manga_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D4A1DD177B6461 (manga_id), INDEX IDX_D4A1DD17BAD26311 (tag_id), PRIMARY KEY(manga_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_languages (manga_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_C94392A87B6461 (manga_id), INDEX IDX_C94392A882F1BAF4 (language_id), PRIMARY KEY(manga_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_parodies (manga_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_FFC45F27B6461 (manga_id), INDEX IDX_FFC45F282F1BAF4 (language_id), PRIMARY KEY(manga_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parody (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(190) NOT NULL, UNIQUE INDEX UNIQ_B44771AE5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(190) NOT NULL, UNIQUE INDEX UNIQ_389B7835E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mangas_authors ADD CONSTRAINT FK_E9183DD87B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_authors ADD CONSTRAINT FK_E9183DD8F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE mangas_tags ADD CONSTRAINT FK_D4A1DD177B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_tags ADD CONSTRAINT FK_D4A1DD17BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
        $this->addSql('ALTER TABLE mangas_languages ADD CONSTRAINT FK_C94392A87B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_languages ADD CONSTRAINT FK_C94392A882F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE mangas_parodies ADD CONSTRAINT FK_FFC45F27B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_parodies ADD CONSTRAINT FK_FFC45F282F1BAF4 FOREIGN KEY (language_id) REFERENCES parody (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        
        $this->addSql('ALTER TABLE mangas_authors DROP FOREIGN KEY FK_E9183DD8F675F31B');
        $this->addSql('ALTER TABLE mangas_languages DROP FOREIGN KEY FK_C94392A882F1BAF4');
        $this->addSql('ALTER TABLE mangas_authors DROP FOREIGN KEY FK_E9183DD87B6461');
        $this->addSql('ALTER TABLE mangas_tags DROP FOREIGN KEY FK_D4A1DD177B6461');
        $this->addSql('ALTER TABLE mangas_languages DROP FOREIGN KEY FK_C94392A87B6461');
        $this->addSql('ALTER TABLE mangas_parodies DROP FOREIGN KEY FK_FFC45F27B6461');
        $this->addSql('ALTER TABLE mangas_parodies DROP FOREIGN KEY FK_FFC45F282F1BAF4');
        $this->addSql('ALTER TABLE mangas_tags DROP FOREIGN KEY FK_D4A1DD17BAD26311');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE manga');
        $this->addSql('DROP TABLE mangas_authors');
        $this->addSql('DROP TABLE mangas_tags');
        $this->addSql('DROP TABLE mangas_languages');
        $this->addSql('DROP TABLE mangas_parodies');
        $this->addSql('DROP TABLE parody');
        $this->addSql('DROP TABLE tag');
    }
}
