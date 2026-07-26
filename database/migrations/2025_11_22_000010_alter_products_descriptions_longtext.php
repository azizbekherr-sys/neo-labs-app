<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // Use raw SQL to avoid doctrine/dbal dependency for column type change
            DB::statement('ALTER TABLE `products` MODIFY `description` LONGTEXT NULL');
            // These columns may not exist on very old schemas; guard each with try/catch
            try { DB::statement('ALTER TABLE `products` MODIFY `description_uz` LONGTEXT NULL'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE `products` MODIFY `description_ru` LONGTEXT NULL'); } catch (\Throwable $e) {}
        }
        // For PostgreSQL/SQLite, TEXT is already sufficiently large; no action required.
    }

    public function down(): void
    {
        // No-op: keeping LONGTEXT is safe; reverting is not necessary.
    }
};


