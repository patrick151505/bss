<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Version history for each template
        Schema::create('eb_document_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')
                  ->constrained('eb_document_templates')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('paper_bg')->nullable();
            $table->string('paper_size')->default('a4');
            $table->string('orientation')->default('portrait');
            $table->unsignedSmallInteger('padding_top')->default(160);
            $table->unsignedSmallInteger('padding_bottom')->default(72);
            $table->unsignedSmallInteger('padding_left')->default(72);
            $table->unsignedSmallInteger('padding_right')->default(72);
            $table->string('change_note')->nullable();   // optional note about what changed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Add current_version_id to templates (set after versions table exists)
        Schema::table('eb_document_templates', function (Blueprint $table) {
            $table->foreignId('current_version_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('eb_document_template_versions')
                  ->nullOnDelete();
        });

        // Replace snap_* columns on requests with a single version FK
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn([
                'snap_paper_bg',
                'snap_padding_top',
                'snap_padding_bottom',
                'snap_padding_left',
                'snap_padding_right',
            ]);
            $table->foreignId('template_version_id')
                  ->nullable()
                  ->after('remarks')
                  ->constrained('eb_document_template_versions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropForeign(['template_version_id']);
            $table->dropColumn('template_version_id');
            $table->string('snap_paper_bg')->nullable();
            $table->unsignedSmallInteger('snap_padding_top')->default(48);
            $table->unsignedSmallInteger('snap_padding_bottom')->default(72);
            $table->unsignedSmallInteger('snap_padding_left')->default(72);
            $table->unsignedSmallInteger('snap_padding_right')->default(72);
        });

        Schema::table('eb_document_templates', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
            $table->dropColumn('current_version_id');
        });

        Schema::dropIfExists('eb_document_template_versions');
    }
};
