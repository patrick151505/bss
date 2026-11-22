<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * eb_address and eb_approval_status came from the old system with a plain
 * `id int NOT NULL` primary key and no AUTO_INCREMENT, so adding a new
 * zone/address failed with "Field 'id' doesn't have a default value".
 * Existing ids are kept; new rows continue after the highest one.
 */
return new class extends Migration
{
    private array $tables = ['eb_address', 'eb_approval_status'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || $this->isAutoIncrement($table)) {
                continue;
            }
            // A stray id 0 would block AUTO_INCREMENT; move it to the end first.
            if (DB::table($table)->where('id', 0)->exists()) {
                $next = (int) DB::table($table)->max('id') + 1;
                DB::table($table)->where('id', 0)->update(['id' => $next]);
            }
            DB::statement("ALTER TABLE `{$table}` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && $this->isAutoIncrement($table)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` INT(11) NOT NULL");
            }
        }
    }

    private function isAutoIncrement(string $table): bool
    {
        $col = DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'");
        return $col && str_contains(strtolower($col->Extra ?? ''), 'auto_increment');
    }
};
