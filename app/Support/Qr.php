<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Qr
{
    /**
     * Generate a QR code as an inline SVG data URI — no network, no image
     * extension, and it scales cleanly at any print size.
     *
     * @param  string  $value  The data to encode.
     * @param  int     $size    Pixel size hint for the SVG viewport.
     */
    public static function svgDataUri(string $value, int $size = 200): string
    {
        $value = $value !== '' ? $value : ' ';

        $renderer = new ImageRenderer(
            new RendererStyle($size, 0),   // margin 0
            new SvgImageBackEnd()
        );

        $svg = (new Writer($renderer))->writeString($value);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
