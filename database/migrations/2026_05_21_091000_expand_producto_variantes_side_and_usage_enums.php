<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsNativeEnumAlter()) {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE `producto_variantes`
    MODIFY `uso` ENUM('interior','exterior','mixto') NULL DEFAULT NULL,
    MODIFY `lado` ENUM('izquierdo','derecho','bilateral','central') NULL DEFAULT NULL
SQL);
    }

    public function down(): void
    {
        if (! $this->supportsNativeEnumAlter()) {
            return;
        }

        $hasExpandedUsageValues = DB::table('producto_variantes')
            ->whereIn('uso', ['mixto'])
            ->exists();

        $hasExpandedSideValues = DB::table('producto_variantes')
            ->whereIn('lado', ['bilateral', 'central'])
            ->exists();

        if ($hasExpandedUsageValues || $hasExpandedSideValues) {
            throw new RuntimeException(
                'No se puede revertir la expansión de ENUMs de producto_variantes porque existen registros con valores nuevos.'
            );
        }

        DB::statement(<<<'SQL'
ALTER TABLE `producto_variantes`
    MODIFY `uso` ENUM('interior','exterior') NULL DEFAULT NULL,
    MODIFY `lado` ENUM('izquierdo','derecho') NULL DEFAULT NULL
SQL);
    }

    private function supportsNativeEnumAlter(): bool
    {
        if (! Schema::hasTable('producto_variantes')) {
            return false;
        }

        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
