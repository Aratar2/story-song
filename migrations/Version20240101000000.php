<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema with admin users, song requests and telegram dispatches.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B263ED9E7927C74 ON admin_users (email)');

        $this->addSql('CREATE TABLE song_requests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) NOT NULL, occasion VARCHAR(255) DEFAULT NULL, tone VARCHAR(255) DEFAULT NULL, story CLOB DEFAULT NULL, story_later BOOLEAN NOT NULL, created_at DATETIME NOT NULL)');

        $this->addSql('CREATE TABLE telegram_dispatches (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, request_id INTEGER NOT NULL, dispatched_at DATETIME NOT NULL, CONSTRAINT FK_1DFDC41A427EB8A5 FOREIGN KEY (request_id) REFERENCES song_requests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1DFDC41A427EB8A5 ON telegram_dispatches (request_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE telegram_dispatches');
        $this->addSql('DROP TABLE song_requests');
        $this->addSql('DROP TABLE admin_users');
    }
}
