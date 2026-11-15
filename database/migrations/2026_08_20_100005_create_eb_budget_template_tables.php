<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The BBPF02 worksheet as editable data.
     *
     * A template holds the standard set of accounts a barangay lists under
     * each program. Opening a fiscal year copies the template into
     * eb_budget_line_items with blank amounts; the treasurer then types
     * amounts only on the rows they fund, and unfunded rows stay on the form.
     */
    public function up(): void
    {
        Schema::create('eb_budget_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('eb_budget_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('eb_budget_templates')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('eb_budget_programs')->cascadeOnDelete();
            $table->enum('object_class', ['PS', 'MOOE', 'FE', 'CO']);

            // The line item's own title, which is deliberately NOT the COA
            // account title — BBPF02 lists 5-01-02-100 six times as
            // "Honoraria - Administrative", "Honoraria - Lupon", etc.
            $table->string('name', 255);

            $table->string('object_code', 20)->nullable();
            $table->string('aip_code', 20)->nullable();
            $table->text('expected_result')->nullable();
            $table->text('performance_indicator')->nullable();
            // Optional heading within a sub-program ("Social Development",
            // "Quick Response Fund (QRF) 30%").
            $table->string('group_label', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('object_code')->references('code')->on('eb_coa_accounts')->nullOnDelete();
            $table->index(['template_id', 'program_id', 'object_class'], 'tpl_items_tpl_prog_cls_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_template_items');
        Schema::dropIfExists('eb_budget_templates');
    }
};
