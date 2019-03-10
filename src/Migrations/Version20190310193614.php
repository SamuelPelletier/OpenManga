<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190310193614 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mangas_authors (manga_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_E9183DD87B6461 (manga_id), INDEX IDX_E9183DD8F675F31B (author_id), PRIMARY KEY(manga_id, author_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mangas_authors ADD CONSTRAINT FK_E9183DD87B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE mangas_authors ADD CONSTRAINT FK_E9183DD8F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E03F675F31B');
        $this->addSql('DROP INDEX IDX_765A9E03F675F31B ON manga');
        $this->addSql('ALTER TABLE manga DROP author_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE mangas_authors');
        $this->addSql('ALTER TABLE manga ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E03F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('CREATE INDEX IDX_765A9E03F675F31B ON manga (author_id)');
    }
}
