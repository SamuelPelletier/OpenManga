<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218181039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mangas_parodies DROP FOREIGN KEY FK_FFC45F282F1BAF4');
        $this->addSql('DROP INDEX IDX_FFC45F282F1BAF4 ON mangas_parodies');
        $this->addSql('DROP INDEX `primary` ON mangas_parodies');
        $this->addSql('ALTER TABLE mangas_parodies CHANGE language_id parody_id INT NOT NULL');
        $this->addSql('ALTER TABLE mangas_parodies ADD CONSTRAINT FK_FFC45F26B3B296A FOREIGN KEY (parody_id) REFERENCES parody (id)');
        $this->addSql('CREATE INDEX IDX_FFC45F26B3B296A ON mangas_parodies (parody_id)');
        $this->addSql('ALTER TABLE mangas_parodies ADD PRIMARY KEY (manga_id, parody_id)');
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reset_password_request CHANGE requested_at requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE mangas_parodies DROP FOREIGN KEY FK_FFC45F26B3B296A');
        $this->addSql('DROP INDEX IDX_FFC45F26B3B296A ON mangas_parodies');
        $this->addSql('DROP INDEX `PRIMARY` ON mangas_parodies');
        $this->addSql('ALTER TABLE mangas_parodies CHANGE parody_id language_id INT NOT NULL');
        $this->addSql('ALTER TABLE mangas_parodies ADD CONSTRAINT FK_FFC45F282F1BAF4 FOREIGN KEY (language_id) REFERENCES parody (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_FFC45F282F1BAF4 ON mangas_parodies (language_id)');
        $this->addSql('ALTER TABLE mangas_parodies ADD PRIMARY KEY (manga_id, language_id)');
    }
}
