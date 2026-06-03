<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add 1C source references and raw JSON audit fields for advertisement imports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement ADD source_ref VARCHAR(36) DEFAULT NULL, ADD source_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement_side ADD source_ref VARCHAR(36) DEFAULT NULL, ADD source_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement_type ADD source_ref VARCHAR(36) DEFAULT NULL, ADD source_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement DROP source_ref, DROP source_data');
        $this->addSql('ALTER TABLE advertisement_side DROP source_ref, DROP source_data');
        $this->addSql('ALTER TABLE advertisement_type DROP source_ref, DROP source_data');
    }
}
