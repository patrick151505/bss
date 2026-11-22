<?php

use App\Models\KioskSlide;
use App\Support\KioskImage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Pictures uploaded before resizing existed may be too large (or WebP) for
     * phone browsers to show. Convert each to a phone-friendly JPG once.
     * A picture that cannot be processed is left exactly as it was.
     */
    public function up(): void
    {
        foreach (KioskSlide::all() as $slide) {
            try {
                $new = KioskImage::optimize($slide->image);
                if ($new && $new !== $slide->image) {
                    $slide->update(['image' => $new]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function down(): void
    {
        // Originals are replaced; nothing to undo.
    }
};
