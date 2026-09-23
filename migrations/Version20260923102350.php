<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923102350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF346685E237E06 ON categories (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF34668989D9B62 ON categories (slug)');
        $this->addSql('CREATE TABLE post_category (post_id INTEGER NOT NULL, category_id INTEGER NOT NULL, PRIMARY KEY (post_id, category_id), CONSTRAINT FK_B9A190604B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B9A1906012469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B9A190604B89032C ON post_category (post_id)');
        $this->addSql('CREATE INDEX IDX_B9A1906012469DE2 ON post_category (category_id)');

        // --- DATA MIGRATION ---
        $this->addSql('INSERT INTO categories (name, slug) SELECT DISTINCT category, lower(category) FROM posts WHERE category IS NOT NULL AND category != ""');
        $this->addSql('INSERT INTO post_category (post_id, category_id) SELECT p.id, c.id FROM posts p JOIN categories c ON p.category = c.name WHERE p.category IS NOT NULL AND p.category != ""');

        $this->addSql('CREATE TEMPORARY TABLE __temp__posts AS SELECT id, title, content, updated_at, created_at, article_image, slug, meta_title, meta_description, status FROM posts');
        $this->addSql('DROP TABLE posts');
        $this->addSql('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content CLOB NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, article_image VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description CLOB DEFAULT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL)');
        $this->addSql('INSERT INTO posts (id, title, content, updated_at, created_at, article_image, slug, meta_title, meta_description, status) SELECT id, title, content, updated_at, created_at, article_image, slug, meta_title, meta_description, status FROM __temp__posts');
        $this->addSql('DROP TABLE __temp__posts');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_885DBAFA989D9B62 ON posts (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE post_category');
        $this->addSql('ALTER TABLE posts ADD COLUMN category VARCHAR(255) NOT NULL');
    }
}
