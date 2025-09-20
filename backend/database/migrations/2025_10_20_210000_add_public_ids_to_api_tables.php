<?php

use App\Support\PublicIdGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'tenants',
        'users',
        'clients',
        'tasks',
        'task_comments',
        'task_subtasks',
        'task_watchers',
        'task_types',
        'task_statuses',
        'task_automations',
        'manuals',
        'notifications',
        'teams',
        'roles',
        'files',
        'brandings',
        'statuses',
        'consents',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->ulid('public_id')->nullable()->unique();
            });

            DB::table($table)
                ->orderBy('id')
                ->whereNull('public_id')
                ->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['public_id' => PublicIdGenerator::generate()]);
                    }
                });

            $this->enforceNotNull($table);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropColumn('public_id');
            });
        }
    }

    private function enforceNotNull(string $table): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->ulid('public_id')->nullable(false)->change();
            });
        } catch (\Throwable $exception) {
            $connection = Schema::getConnection();
            $grammar = $connection->getQueryGrammar();
            $wrappedTable = $grammar->wrapTable($table);
            $wrappedColumn = $grammar->wrap('public_id');
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                DB::statement(sprintf(
                    'ALTER TABLE %s MODIFY %s CHAR(26) NOT NULL',
                    $wrappedTable,
                    $wrappedColumn
                ));

                return;
            }

            if ($driver === 'pgsql') {
                DB::statement(sprintf(
                    'ALTER TABLE %s ALTER COLUMN %s SET NOT NULL',
                    $wrappedTable,
                    $wrappedColumn
                ));

                return;
            }

            throw $exception;
        }
    }
};
