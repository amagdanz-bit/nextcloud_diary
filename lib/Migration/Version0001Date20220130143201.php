<?php

declare(strict_types=1);

namespace OCA\Diary\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0001Date20220130143201 extends SimpleMigrationStep
{
    /**
     * @param Closure(): ISchemaWrapper $schemaClosure
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('diary')) {
            $table = $schema->createTable('diary');
            $table->addColumn('id', Types::STRING, ['length' => 74, 'notnull' => true]);
            $table->addColumn('uid', Types::STRING, ['length' => 64]);
            $table->addColumn('entry_date', Types::STRING, ['length' => 10]);
            $table->addColumn('entry_content', Types::TEXT, ['notnull' => false]);
            $table->setPrimaryKey(['id'], 'diary_user_id_date');
        }

        return $schema;
    }
}
