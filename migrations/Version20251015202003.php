<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015202003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_765A9E037339772D ON manga (is_old)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_765A9E03CC696D48 ON manga (is_blocked)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_765A9E03EB692E73 ON manga (is_corrupted)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649F85E0677 ON user (username)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D64976F5C865 ON user (google_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649FF54322E ON user (patreon_access_token)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D649F85E0677 ON user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D64976F5C865 ON user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D649FF54322E ON user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_765A9E037339772D ON manga
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_765A9E03CC696D48 ON manga
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_765A9E03EB692E73 ON manga
        SQL);
    }
}
