<?php

namespace App\Services;

use App\Models\Pendiente;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PendienteSchemaService
{
    /**
     * @var array<string, bool>
     */
    protected array $resolvedColumns = [];

    public function hasColumn(string $column): bool
    {
        return $this->hasColumns([$column]);
    }

    public function hasColumns(array $columns): bool
    {
        $model = new Pendiente();
        $connection = $model->getConnectionName();
        $table = $model->getTable();

        foreach ($columns as $column) {
            $column = (string) $column;

            if (array_key_exists($column, $this->resolvedColumns)) {
                if (! $this->resolvedColumns[$column]) {
                    return false;
                }

                continue;
            }

            $cacheKey = sprintf(
                'schema-has-column:%s:%s:%s',
                $connection ?: config('database.default'),
                $table,
                $column,
            );

            $this->resolvedColumns[$column] = Cache::remember(
                $cacheKey,
                now()->addHours(6),
                function () use ($connection, $table, $column): bool {
                    try {
                        return Schema::connection($connection)->hasColumn($table, $column);
                    } catch (\Throwable) {
                        return false;
                    }
                },
            );

            if (! $this->resolvedColumns[$column]) {
                return false;
            }
        }

        return true;
    }
}
