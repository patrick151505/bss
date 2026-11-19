<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The raffle now runs on one logged-in screen: no PIN-protected public page
     * and no live display/controller channel, so these columns are unused.
     */
    private array $columns = ['raffle_pin', 'raffle_pin_expires_at', 'raffle_live', 'raffle_live_ver'];

    public function up(): void
    {
        $existing = array_values(array_filter($this->columns, fn($c) => Schema::hasColumn('eb_event', $c)));
        if ($existing) {
            Schema::table('eb_event', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }

    public function down(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            if (!Schema::hasColumn('eb_event', 'raffle_pin')) {
                $table->string('raffle_pin', 6)->nullable()->after('allow_winner_repeat');
            }
            if (!Schema::hasColumn('eb_event', 'raffle_pin_expires_at')) {
                $table->datetime('raffle_pin_expires_at')->nullable()->after('raffle_pin');
            }
            if (!Schema::hasColumn('eb_event', 'raffle_live')) {
                $table->longText('raffle_live')->nullable()->after('raffle_pin_expires_at');
            }
            if (!Schema::hasColumn('eb_event', 'raffle_live_ver')) {
                $table->unsignedInteger('raffle_live_ver')->default(0)->after('raffle_live');
            }
        });
    }
};
