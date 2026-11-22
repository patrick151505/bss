<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Makes kiosk start-screen pictures safe for every screen.
 *
 * Pictures were stored exactly as uploaded (up to 5 MB, PNG/WebP/JPG). Desktop
 * browsers show those fine, but phone browsers — iPhones especially — refuse
 * to draw very large PNGs (about 5 megapixels and up) and older iPhones cannot
 * show WebP at all, so the start screen stayed dark on phones. Every picture is
 * turned into a JPG no larger than 1920px on its long side, which every browser
 * shows and which also loads much faster over mobile data.
 */
class KioskImage
{
    private const MAX_SIDE = 1920;
    private const QUALITY  = 82;

    /**
     * Optimize the picture at $path (relative to the default storage disk,
     * e.g. "public/kiosk/abc.png"). Returns the new path, the same path when
     * nothing needed changing, or null when the server cannot process images
     * (the original is then left untouched).
     */
    public static function optimize(string $path): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! Storage::exists($path)) {
            return null;
        }

        $full = Storage::path($path);
        $info = @getimagesize($full);
        if (! $info) {
            return null;
        }
        [$w, $h, $type] = $info;

        $orientation = 1;
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($full)['Orientation'] ?? 1);
        }

        // Already a modest JPG the right way up: nothing to do.
        if ($type === IMAGETYPE_JPEG && max($w, $h) <= self::MAX_SIDE && $orientation === 1 && filesize($full) <= 900 * 1024) {
            return $path;
        }

        $load = [
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG  => 'imagecreatefrompng',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
        ][$type] ?? null;
        if (! $load || ! function_exists($load)) {
            return null;
        }

        // Big camera photos need room to decode.
        $oldLimit = ini_get('memory_limit');
        @ini_set('memory_limit', '512M');

        try {
            $src = @$load($full);
            if (! $src) {
                return null;
            }

            // Turn phone photos the right way up.
            $src = match ($orientation) {
                3       => imagerotate($src, 180, 0),
                6       => imagerotate($src, -90, 0),
                8       => imagerotate($src, 90, 0),
                default => $src,
            };
            $w = imagesx($src);
            $h = imagesy($src);

            $scale = min(1, self::MAX_SIDE / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $dst = imagecreatetruecolor($nw, $nh);
            // Transparent parts become the kiosk's dark background, not black blotches.
            imagefill($dst, 0, 0, imagecolorallocate($dst, 11, 18, 32));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imageinterlace($dst, true);   // progressive: shows sooner on slow connections

            $newPath = preg_replace('/\.[^.\/]+$/', '', $path) . '.jpg';
            $ok = imagejpeg($dst, Storage::path($newPath), self::QUALITY);
            imagedestroy($src);
            imagedestroy($dst);

            if (! $ok) {
                return null;
            }
            if ($newPath !== $path) {
                Storage::delete($path);
            }

            return $newPath;
        } finally {
            @ini_set('memory_limit', $oldLimit);
        }
    }
}
