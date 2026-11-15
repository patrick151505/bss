<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Permanent record of citizens removed from eb_citizen.
     *
     * Deleting a citizen copies the whole row here first, so the barangay
     * keeps an auditable trail of what was removed, why, and by whom.
     */
    public function up(): void
    {
        Schema::create('eb_deleted_citizens', function (Blueprint $table) {
            $table->id();

            // The id the citizen held in eb_citizen, kept so existing
            // references can still be traced back.
            $table->unsignedInteger('citizen_id')->index();

            // Denormalised for listing and searching without unpacking the
            // snapshot on every row.
            $table->string('fname', 100);
            $table->string('mname', 255)->nullable();
            $table->string('lname', 100);
            $table->string('suffix', 50)->nullable();
            $table->date('bday')->nullable();
            $table->string('contact', 100)->nullable();

            // The complete original row, so a record can be reconstructed.
            $table->json('snapshot');

            $table->text('reason');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('deleted_at');

            $table->timestamps();

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_deleted_citizens');
    }
};
