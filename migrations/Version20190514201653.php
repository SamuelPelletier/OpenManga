<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190514201653 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author CHANGE name name VARCHAR(190) NOT NULL');
        $this->addSql('ALTER TABLE language CHANGE name name VARCHAR(190) NOT NULL');
        $this->addSql('ALTER TABLE manga ADD count_views INT NOT NULL');
        $this->addSql('ALTER TABLE parody CHANGE name name VARCHAR(190) NOT NULL');
        $this->addSql('ALTER TABLE tag CHANGE name name VARCHAR(190) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE language CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE manga DROP count_views');
        $this->addSql('ALTER TABLE parody CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE tag CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
