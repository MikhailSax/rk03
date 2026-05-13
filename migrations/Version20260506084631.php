<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506084631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE advertisement (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, code VARCHAR(255) DEFAULT NULL, place_number VARCHAR(50) DEFAULT NULL, address VARCHAR(500) DEFAULT NULL, sides JSON DEFAULT NULL, side_adescription VARCHAR(1000) DEFAULT NULL, side_bdescription VARCHAR(1000) DEFAULT NULL, side_aprice NUMERIC(10, 2) DEFAULT NULL, side_bprice NUMERIC(10, 2) DEFAULT NULL, side_aimage VARCHAR(255) DEFAULT NULL, side_bimage VARCHAR(255) DEFAULT NULL, INDEX IDX_C95F6AEEC54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE advertisement_booking (id INT AUTO_INCREMENT NOT NULL, advertisement_id INT NOT NULL, order_ref_id INT DEFAULT NULL, side_code VARCHAR(10) NOT NULL, client_name VARCHAR(255) NOT NULL, start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', comment VARCHAR(1000) DEFAULT NULL, INDEX IDX_340518D3A1FBF71B (advertisement_id), INDEX IDX_340518D3E238517C (order_ref_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE advertisement_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE advertisement_location (id INT AUTO_INCREMENT NOT NULL, advertisement_id INT NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, azimuth INT DEFAULT NULL, UNIQUE INDEX UNIQ_20FBFC83A1FBF71B (advertisement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE advertisement_side (id INT AUTO_INCREMENT NOT NULL, advertisement_id INT NOT NULL, code VARCHAR(10) NOT NULL, description VARCHAR(1000) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, night_image VARCHAR(255) DEFAULT NULL, INDEX IDX_C00300FDA1FBF71B (advertisement_id), UNIQUE INDEX uniq_advertisement_side_code (advertisement_id, code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE advertisement_type (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(150) NOT NULL, INDEX IDX_6F5C4C6112469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE banner (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, image VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', alt VARCHAR(255) DEFAULT NULL, sort INT NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, contact_name VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) NOT NULL, comment VARCHAR(1000) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reserved_until DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expired_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, order_ref_id INT NOT NULL, advertisement_id INT NOT NULL, side_code VARCHAR(10) NOT NULL, start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', price_snapshot NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_52EA1F09E238517C (order_ref_id), INDEX IDX_52EA1F09A1FBF71B (advertisement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_request (id INT AUTO_INCREMENT NOT NULL, advertisement_id INT NOT NULL, side_code VARCHAR(10) NOT NULL, contact_name VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) NOT NULL, comment VARCHAR(1000) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EA4E942FA1FBF71B (advertisement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, yandex_id BIGINT DEFAULT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) DEFAULT NULL, email VARCHAR(100) NOT NULL, phone VARCHAR(30) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649444F97DD (phone), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE advertisement ADD CONSTRAINT FK_C95F6AEEC54C8C93 FOREIGN KEY (type_id) REFERENCES advertisement_type (id)');
        $this->addSql('ALTER TABLE advertisement_booking ADD CONSTRAINT FK_340518D3A1FBF71B FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advertisement_booking ADD CONSTRAINT FK_340518D3E238517C FOREIGN KEY (order_ref_id) REFERENCES `order` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE advertisement_location ADD CONSTRAINT FK_20FBFC83A1FBF71B FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advertisement_side ADD CONSTRAINT FK_C00300FDA1FBF71B FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advertisement_type ADD CONSTRAINT FK_6F5C4C6112469DE2 FOREIGN KEY (category_id) REFERENCES advertisement_category (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09E238517C FOREIGN KEY (order_ref_id) REFERENCES `order` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09A1FBF71B FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_request ADD CONSTRAINT FK_EA4E942FA1FBF71B FOREIGN KEY (advertisement_id) REFERENCES advertisement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE advertisement DROP FOREIGN KEY FK_C95F6AEEC54C8C93');
        $this->addSql('ALTER TABLE advertisement_booking DROP FOREIGN KEY FK_340518D3A1FBF71B');
        $this->addSql('ALTER TABLE advertisement_booking DROP FOREIGN KEY FK_340518D3E238517C');
        $this->addSql('ALTER TABLE advertisement_location DROP FOREIGN KEY FK_20FBFC83A1FBF71B');
        $this->addSql('ALTER TABLE advertisement_side DROP FOREIGN KEY FK_C00300FDA1FBF71B');
        $this->addSql('ALTER TABLE advertisement_type DROP FOREIGN KEY FK_6F5C4C6112469DE2');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09E238517C');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09A1FBF71B');
        $this->addSql('ALTER TABLE product_request DROP FOREIGN KEY FK_EA4E942FA1FBF71B');
        $this->addSql('DROP TABLE advertisement');
        $this->addSql('DROP TABLE advertisement_booking');
        $this->addSql('DROP TABLE advertisement_category');
        $this->addSql('DROP TABLE advertisement_location');
        $this->addSql('DROP TABLE advertisement_side');
        $this->addSql('DROP TABLE advertisement_type');
        $this->addSql('DROP TABLE banner');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE product_request');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
