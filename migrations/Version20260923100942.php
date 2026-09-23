<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923100942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT \'draft\' NOT NULL');
        $this->addSql('UPDATE posts SET status = \'published\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__posts AS SELECT id, title, content, category, updated_at, created_at, article_image, slug, meta_title, meta_description FROM posts');
        $this->addSql('DROP TABLE posts');
        $this->addSql('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content CLOB NOT NULL, category VARCHAR(255) NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, article_image VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO posts (id, title, content, category, updated_at, created_at, article_image, slug, meta_title, meta_description) SELECT id, title, content, category, updated_at, created_at, article_image, slug, meta_title, meta_description FROM __temp__posts');
        $this->addSql('DROP TABLE __temp__posts');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_885DBAFA989D9B62 ON posts (slug)');
    }
}
