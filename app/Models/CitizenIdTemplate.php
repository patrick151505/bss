<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenIdTemplate extends Model
{
    protected $table = 'eb_citizen_id_templates';

    protected $fillable = [
        'orientation_front', 'bg_front', 'html_front', 'layout_front',
        'orientation_back',  'bg_back',  'html_back',  'layout_back',
        'css_shared', 'js_shared',
    ];

    protected $casts = [
        'layout_front' => 'array',
        'layout_back'  => 'array',
    ];

    /**
     * Compile a visual layout (array of positioned elements) into the absolute-
     * positioned HTML that print.blade.php already knows how to fill. Each element
     * becomes one <div> placed with inline styles, so no shared CSS is required and
     * the print output is fully deterministic from the layout.
     *
     * Element shape:
     *   kind:     text | field | photo | qr
     *   text:     literal text or placeholder(s) like "ADDRESS: {{address}}"
     *   x,y,w:    position + width in card pixels (324x204 landscape)
     *   fontSize, bold, italic, align, color, underline
 *   cssId, cssClass: optional id / class for the template's custom CSS
     */
    /** Flags an element can be limited to ("Show only if" in the designer). */
    public const SHOW_IF = ['pwd', 'solo-parent', 'senior', 'voter'];

    public static function compileLayout(?array $elements): string
    {
        if (empty($elements)) {
            return '';
        }

        $html = '';
        foreach ($elements as $el) {
            $x = (float) ($el['x'] ?? 0);
            $y = (float) ($el['y'] ?? 0);
            $w = (float) ($el['w'] ?? 100);

            $style = "position:absolute;left:{$x}px;top:{$y}px;width:{$w}px;";

            $kind = $el['kind'] ?? 'field';

            // Optional CSS hooks set in the designer (id / class), so the
            // template's custom CSS can target this element.
            $cssId    = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($el['cssId'] ?? ''));
            $cssClass = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9_\- ]/', '', (string) ($el['cssClass'] ?? ''))));
            // "Show only if" (PWD / solo parent / senior / voter): the print page
            // hides .show-if-X unless the citizen carries the matching flag-X.
            $showIf = (string) ($el['showIf'] ?? '');
            if ($kind !== 'signature' && in_array($showIf, self::SHOW_IF, true)) {
                $cssClass = trim($cssClass . ' show-if-' . $showIf);
            }
            $attrs    = ($cssId !== '' ? ' id="' . $cssId . '"' : '')
                      . ($cssClass !== '' ? ' class="' . $cssClass . '"' : '');

            if ($kind === 'photo') {
                $h = (float) ($el['h'] ?? 62);
                $html .= '<div' . $attrs . ' style="' . $style . "height:{$h}px;overflow:hidden;\">"
                       . '<img src="{{photo_url}}" style="width:100%;height:100%;object-fit:cover;display:block;">'
                       . '</div>';
                continue;
            }

            if ($kind === 'qr') {
                $px = (int) round($w);
                $html .= '<div' . $attrs . ' style="' . $style . "height:{$px}px;\">{{qr_img_{$px}}}</div>";
                continue;
            }

            if ($kind === 'signature') {
                // The print view fills {{signature_img}} with the uploaded <img>.
                // We position it here via the dragged coordinates; the print JS
                // drives #sig-overlay / #sig-img-inner inside this box.
                $h = (float) ($el['h'] ?? 24);
                $html .= '<div id="sig-overlay" class="signature' . ($cssClass !== '' ? ' ' . $cssClass : '') . '" style="' . $style . "height:{$h}px;\">{{signature_img}}</div>";
                continue;
            }

            // text / field: style the text box
            // line-height must equal the designer's .id-el (1.15) or
            // multi-line text sits at a different height when printed.
            $fs   = (float) ($el['fontSize'] ?? 6);
            $style .= "font-size:{$fs}px;line-height:1.15;";
            $style .= 'text-align:' . ($el['align'] ?? 'left') . ';';
            if (! empty($el['bold']))      $style .= 'font-weight:bold;';
            if (! empty($el['italic']))    $style .= 'font-style:italic;';
            if (! empty($el['underline'])) $style .= 'text-decoration:underline;';
            if (! empty($el['color']))     $style .= 'color:' . preg_replace('/[^#a-zA-Z0-9]/', '', $el['color']) . ';';
            if (! empty($el['bg'])) {
                $pad = isset($el['pad']) ? max(0, (float) $el['pad']) : 3;
                $style .= 'background:' . preg_replace('/[^#a-zA-Z0-9]/', '', $el['bg']) . ';'
                       . "padding:{$pad}px;box-sizing:border-box;";
            }

            // Content is rendered as raw HTML (advanced mode): tags, class, style,
            // and {{placeholders}} all pass through as typed. This is an admin-only
            // designer (can:citizens.edit), so no escaping is applied.
            $content = (string) ($el['text'] ?? '');

            $html .= '<div' . $attrs . ' style="' . $style . '">' . $content . '</div>';
        }

        return $html;
    }
}
