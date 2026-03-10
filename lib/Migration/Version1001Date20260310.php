<?php

declare(strict_types=1);

namespace OCA\AdminPage\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1001Date20260310 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('adminpage_public_links')) {
            $table = $schema->createTable('adminpage_public_links');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('token', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('org_id', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('label', Types::STRING, [
                'notnull' => false,
                'length' => 255,
                'default' => null,
            ]);
            $table->addColumn('enabled', Types::SMALLINT, [
                'notnull' => true,
                'default' => 1,
                'unsigned' => true,
            ]);
            $table->addColumn('expires_at', Types::DATETIME, [
                'notnull' => false,
                'default' => null,
            ]);
            $table->addColumn('created_by', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['token'], 'adminpage_publink_token');
            $table->addIndex(['org_id'], 'adminpage_publink_org');
        }

        return $schema;
    }
}
