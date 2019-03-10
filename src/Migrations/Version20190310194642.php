<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190310194642 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_D4DB71B55E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_languages (manga_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_C94392A87B6461 (manga_id), INDEX IDX_C94392A882F1BAF4 (language_id), PRIMARY KEY(manga_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mangas_parodies (manga_id INT NOT NULL, language_id INT NOT NULL, INDEX IDX_FFC45F27B6461 (manga_id), INDEX IDX_FFC45F282F1BAF4 (language_id), PRIMARY KEY(manga_id, language_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parody (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B44771AE5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mangas_languages ADD CONSTRAINT FK_C94392A87B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_languages ADD CONSTRAINT FK_C94392A882F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE mangas_parodies ADD CONSTRAINT FK_FFC45F27B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_parodies ADD CONSTRAINT FK_FFC45F282F1BAF4 FOREIGN KEY (language_id) REFERENCES parody (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BDAFD8C85E237E06 ON author (name)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mangas_languages DROP FOREIGN KEY FK_C94392A882F1BAF4');
        $this->addSql('ALTER TABLE mangas_parodies DROP FOREIGN KEY FK_FFC45F282F1BAF4');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE mangas_languages');
        $this->addSql('DROP TABLE mangas_parodies');
        $this->addSql('DROP TABLE parody');
        $this->addSql('DROP INDEX UNIQ_BDAFD8C85E237E06 ON author');
    }
}
