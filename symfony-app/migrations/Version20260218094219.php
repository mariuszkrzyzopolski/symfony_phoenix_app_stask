<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add phoenix_access_token column to users table
 */
final class Version20260218094219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phoenix_access_token column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add the column to existing users table
        $table = $schema->getTable('users');
        $table->addColumn('phoenix_access_token', 'text', [
            'notnull' => false,
            'default' => null
        ]);
    }

    public function down(Schema $schema): void
    {
        // Remove the column from users table
        $table = $schema->getTable('users');
        $table->dropColumn('phoenix_access_token');
    }
}
