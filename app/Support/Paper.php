<?php

namespace App\Support;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Support\Collection;

/**
 * Lets non-document printouts (blotter report, inventory release slip) use a
 * paper template from Documents → Paper Templates: its letterhead image,
 * paper size, orientation and margins — from either the template's latest
 * version or a pinned one (Settings → Print Paper).
 */
class Paper
{
    /** Paper sizes in inches (portrait), same set the document module uses. */
    private const SIZES = [
        'letter'      => [8.5, 11],
        'a4'          => [8.27, 11.69],
        'half_letter' => [5.5, 8.5],
        'long'        => [8.5, 13],
    ];

    /**
     * The paper chosen in Settings → Print Paper (null = plain built-in
     * header). With a $versionId the print stays on that version; without
     * one it follows the template's latest version.
     */
    public static function resolve(?int $templateId, ?int $versionId = null): ?array
    {
        if (! $templateId) {
            return null;
        }

        $template = DocumentTemplate::with('currentVersion')->find($templateId);
        if (! $template) {
            return null;
        }

        $pinned  = $versionId
            ? DocumentTemplateVersion::where('document_template_id', $template->id)->find($versionId)
            : null;
        $version = $pinned ?? $template->currentVersion;

        return self::fromVersion($template, $version, $pinned !== null);
    }

    public static function fromVersion(DocumentTemplate $template, ?DocumentTemplateVersion $ver, bool $pinned = false): ?array
    {
        if (! $ver) {
            return null;
        }

        [$w, $h] = self::SIZES[$ver->paper_size] ?? self::SIZES['letter'];
        if ($ver->orientation === 'landscape') {
            [$w, $h] = [$h, $w];
        }

        return [
            'id'      => $template->id,
            'name'    => $template->name,
            'version' => (int) $ver->version,
            'pinned'  => $pinned,
            'bg_url'  => $ver->paper_bg ? asset('storage/' . $ver->paper_bg) : null,
            'w'       => $w,                                   // inches
            'h'       => $h,                                   // inches
            // Margins in px (96 dpi) — the version's Content Padding.
            'top'     => (int) ($ver->padding_top    ?? 50),
            'right'   => (int) ($ver->padding_right  ?? 50),
            'bottom'  => (int) ($ver->padding_bottom ?? 20),
            'left'    => (int) ($ver->padding_left   ?? 50),
        ];
    }

    /**
     * Active paper templates with all their versions (newest first), for the
     * Settings dropdowns.
     */
    public static function options(): Collection
    {
        return DocumentTemplate::active()
            ->whereNotNull('current_version_id')
            ->with(['currentVersion', 'versions'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Turn the Settings picker into [template_id, version_id]: version null =
     * follow the latest. A version that doesn't belong to the template, or an
     * unknown template, falls back safely.
     */
    public static function parsePick($templateId, $version): array
    {
        $tplId = is_numeric($templateId) ? (int) $templateId : null;
        if (! $tplId || ! DocumentTemplate::whereKey($tplId)->exists()) {
            return [null, null];
        }

        $verId = is_numeric($version)
            && DocumentTemplateVersion::where('document_template_id', $tplId)->whereKey((int) $version)->exists()
            ? (int) $version
            : null;

        return [$tplId, $verId];
    }
}
