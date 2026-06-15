<?php

declare(strict_types=1);

namespace OCA\AdminPage\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1002Date20260606 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('adminpage_geocode_cache')) {
            $table = $schema->createTable('adminpage_geocode_cache');

            $table->addColumn('addr_hash', Types::STRING, [
                'notnull' => true,
                'length'  => 64,
            ]);
            $table->addColumn('lat', Types::DECIMAL, [
                'notnull'   => false,
                'precision' => 10,
                'scale'     => 7,
            ]);
            $table->addColumn('lng', Types::DECIMAL, [
                'notnull'   => false,
                'precision' => 10,
                'scale'     => 7,
            ]);
            $table->addColumn('display_name', Types::STRING, [
                'notnull' => false,
                'length'  => 255,
                'default' => null,
            ]);
            $table->addColumn('source', Types::STRING, [
                'notnull' => true,
                'length'  => 32,
                'default' => 'nominatim',
            ]);
            $table->addColumn('created_at', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->setPrimaryKey(['addr_hash']);
        }

        return $schema;
    }
}
