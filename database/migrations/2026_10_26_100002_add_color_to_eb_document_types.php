<?php

use App\Models\DocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A color per document type, so each type is easy to recognise on the
     * dashboard charts and in lists. Unique: no two types share a color.
     */
    public function up(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->unique()->after('prefix');
        });

        // Give existing types distinct colors from the preset palette, in
        // name order; past the palette, generate distinct hues.
        $palette = DocumentType::PALETTE;
        DB::table('eb_document_types')->orderBy('name')->pluck('id')
            ->each(function ($id, $i) use ($palette) {
                $color = $palette[$i] ?? DocumentType::generatedColor($i);
                DB::table('eb_document_types')->where('id', $id)->update(['color' => $color]);
            });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropUnique(['color']);
            $table->dropColumn('color');
        });
    }
};
