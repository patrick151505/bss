<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentTemplateController extends Controller
{
    public function index()
    {
        $templates = DocumentTemplate::with('currentVersion')
            ->withCount('documentTypes')
            ->orderBy('name')
            ->get();

        return view('documents.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('documents.templates.edit', [
            'template' => new DocumentTemplate(),
            'logs'     => collect(),
        ]);
    }

    // Copy an existing template (name, paper settings, background image) into
    // a brand-new independent template — a fast starting point for a variant
    // letterhead instead of re-uploading and reconfiguring from scratch.
    public function duplicate(DocumentTemplate $documentTemplate)
    {
        $source = $documentTemplate->currentVersion;

        $newTemplate = DocumentTemplate::create([
            'name'        => $documentTemplate->name . ' (Copy)',
            'description' => $documentTemplate->description,
            'paper_size'  => $source?->paper_size  ?? 'a4',
            'orientation' => $source?->orientation ?? 'portrait',
            'is_active'   => false,
        ]);

        $bgPath = null;
        if ($source?->paper_bg && Storage::disk('public')->exists($source->paper_bg)) {
            $ext      = pathinfo($source->paper_bg, PATHINFO_EXTENSION);
            $bgPath   = 'documents/templates/' . uniqid('tpl_') . '.' . $ext;
            Storage::disk('public')->copy($source->paper_bg, $bgPath);
        }

        $version = DocumentTemplateVersion::create([
            'document_template_id' => $newTemplate->id,
            'version'               => 1,
            'paper_bg'              => $bgPath,
            'paper_size'            => $source?->paper_size     ?? 'a4',
            'orientation'           => $source?->orientation    ?? 'portrait',
            'padding_top'           => $source?->padding_top    ?? 160,
            'padding_bottom'        => $source?->padding_bottom ?? 72,
            'padding_left'          => $source?->padding_left   ?? 72,
            'padding_right'         => $source?->padding_right  ?? 72,
            'change_note'           => "Duplicated from \"{$documentTemplate->name}\"",
            'created_by'            => Auth::id(),
        ]);

        $newTemplate->update(['current_version_id' => $version->id]);

        ActivityLog::record('created', 'DocumentTemplate', $newTemplate->id,
            "Duplicated \"{$documentTemplate->name}\" into \"{$newTemplate->name}\"",
            ['source_template_id' => $documentTemplate->id]
        );

        return redirect()->route('documents.templates.edit', $newTemplate)
            ->with('success', "Duplicated as \"{$newTemplate->name}\". Review and activate it when ready.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'paper_bg'    => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'paper_size'  => 'nullable|in:a4,letter',
            'orientation' => 'nullable|in:portrait,landscape',
            'padding_top'    => 'nullable|integer|min:0|max:500',
            'padding_bottom' => 'nullable|integer|min:0|max:500',
            'padding_left'   => 'nullable|integer|min:0|max:500',
            'padding_right'  => 'nullable|integer|min:0|max:500',
            'is_active'   => 'boolean',
            'change_note' => 'nullable|string|max:255',
        ]);

        $template = DocumentTemplate::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'paper_size'  => $data['paper_size']  ?? 'a4',
            'orientation' => $data['orientation'] ?? 'portrait',
            'is_active'   => $request->boolean('is_active'),
        ]);

        $bgPath = $request->hasFile('paper_bg')
            ? $request->file('paper_bg')->store('documents/templates', 'public')
            : null;

        $version = DocumentTemplateVersion::create([
            'document_template_id' => $template->id,
            'version'              => 1,
            'paper_bg'             => $bgPath,
            'paper_size'           => $data['paper_size']     ?? 'a4',
            'orientation'          => $data['orientation']    ?? 'portrait',
            'padding_top'          => $data['padding_top']    ?? 160,
            'padding_bottom'       => $data['padding_bottom'] ?? 72,
            'padding_left'         => $data['padding_left']   ?? 72,
            'padding_right'        => $data['padding_right']  ?? 72,
            'change_note'          => $data['change_note'] ?? 'Initial version',
            'created_by'           => Auth::id(),
        ]);

        $template->update(['current_version_id' => $version->id]);

        ActivityLog::record('created', 'DocumentTemplate', $template->id,
            "Created paper template \"{$template->name}\" (v1)",
            ['version' => 1]
        );

        return redirect()->route('documents.templates.edit', $template)
            ->with('success', 'Template created (v1).');
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $documentTemplate->load(['currentVersion', 'versions.creator']);
        $logs = ActivityLog::with('user')
            ->where('subject_type', 'DocumentTemplate')
            ->where('subject_id', $documentTemplate->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
        return view('documents.templates.edit', ['template' => $documentTemplate, 'logs' => $logs]);
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'paper_bg'    => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'paper_size'  => 'nullable|in:a4,letter',
            'orientation' => 'nullable|in:portrait,landscape',
            'padding_top'    => 'nullable|integer|min:0|max:500',
            'padding_bottom' => 'nullable|integer|min:0|max:500',
            'padding_left'   => 'nullable|integer|min:0|max:500',
            'padding_right'  => 'nullable|integer|min:0|max:500',
            'is_active'   => 'boolean',
            'change_note' => 'nullable|string|max:255',
        ]);

        // Update template meta (name, description, active) — not the paper settings
        $documentTemplate->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $request->boolean('is_active'),
        ]);

        // Determine the new paper_bg path
        $currentVersion = $documentTemplate->currentVersion;

        if ($request->hasFile('paper_bg')) {
            $bgPath = $request->file('paper_bg')->store('documents/templates', 'public');
        } else {
            // Keep same bg as current version
            $bgPath = $currentVersion?->paper_bg;
        }

        // Check if anything paper-related actually changed
        $newPaperSize  = $data['paper_size']     ?? $currentVersion?->paper_size     ?? 'a4';
        $newOrientation= $data['orientation']    ?? $currentVersion?->orientation    ?? 'portrait';
        $newPadTop     = $data['padding_top']    ?? $currentVersion?->padding_top    ?? 160;
        $newPadBottom  = $data['padding_bottom'] ?? $currentVersion?->padding_bottom ?? 72;
        $newPadLeft    = $data['padding_left']   ?? $currentVersion?->padding_left   ?? 72;
        $newPadRight   = $data['padding_right']  ?? $currentVersion?->padding_right  ?? 72;

        $paperChanged = $request->hasFile('paper_bg')
            || $newPaperSize   !== ($currentVersion?->paper_size   ?? 'a4')
            || $newOrientation !== ($currentVersion?->orientation  ?? 'portrait')
            || $newPadTop      !== ($currentVersion?->padding_top    ?? 160)
            || $newPadBottom   !== ($currentVersion?->padding_bottom ?? 72)
            || $newPadLeft     !== ($currentVersion?->padding_left   ?? 72)
            || $newPadRight    !== ($currentVersion?->padding_right  ?? 72);

        $canEditInPlace = $paperChanged && $currentVersion && !$currentVersion->isUsed();

        if ($canEditInPlace) {
            // No request has ever used this version yet — safe to overwrite it
            // instead of piling up an unused version for every tweak.
            if ($request->hasFile('paper_bg') && $currentVersion->paper_bg) {
                Storage::disk('public')->delete($currentVersion->paper_bg);
            }

            $currentVersion->update([
                'paper_bg'       => $bgPath,
                'paper_size'     => $newPaperSize,
                'orientation'    => $newOrientation,
                'padding_top'    => $newPadTop,
                'padding_bottom' => $newPadBottom,
                'padding_left'   => $newPadLeft,
                'padding_right'  => $newPadRight,
                'change_note'    => $data['change_note'] ?? $currentVersion->change_note,
            ]);

            $version = $currentVersion;
        } elseif ($paperChanged) {
            $nextVersion = ($documentTemplate->versions()->max('version') ?? 0) + 1;

            $version = DocumentTemplateVersion::create([
                'document_template_id' => $documentTemplate->id,
                'version'              => $nextVersion,
                'paper_bg'             => $bgPath,
                'paper_size'           => $newPaperSize,
                'orientation'          => $newOrientation,
                'padding_top'          => $newPadTop,
                'padding_bottom'       => $newPadBottom,
                'padding_left'         => $newPadLeft,
                'padding_right'        => $newPadRight,
                'change_note'          => $data['change_note'] ?? null,
                'created_by'           => Auth::id(),
            ]);

            $documentTemplate->update(['current_version_id' => $version->id]);
        }

        if ($canEditInPlace) {
            ActivityLog::record('updated', 'DocumentTemplate', $documentTemplate->id,
                "Updated v{$version->version} of \"{$documentTemplate->name}\" in place (not yet used by any request)" .
                ($data['change_note'] ?? '' ? ': ' . $data['change_note'] : ''),
                ['version' => $version->version]
            );
        } elseif ($paperChanged) {
            ActivityLog::record('updated', 'DocumentTemplate', $documentTemplate->id,
                "Saved new version v{$version->version} of \"{$documentTemplate->name}\"" .
                ($data['change_note'] ?? '' ? ': ' . $data['change_note'] : ''),
                ['version' => $version->version]
            );
        } else {
            ActivityLog::record('updated', 'DocumentTemplate', $documentTemplate->id,
                "Updated info for \"{$documentTemplate->name}\" (no new version created)"
            );
        }

        $message = $canEditInPlace
            ? "Template updated (v{$version->version})."
            : ($paperChanged ? 'Template saved as new version.' : 'Template info updated.');

        return redirect()->route('documents.templates.edit', $documentTemplate)
            ->with('success', $message);
    }

    public function setVersion(DocumentTemplate $documentTemplate, DocumentTemplateVersion $version)
    {
        abort_if($version->document_template_id !== $documentTemplate->id, 403);

        $documentTemplate->update(['current_version_id' => $version->id]);

        ActivityLog::record('updated', 'DocumentTemplate', $documentTemplate->id,
            "Switched active version to v{$version->version} of \"{$documentTemplate->name}\"",
            ['version' => $version->version]
        );

        return back()->with('success', "Switched to v{$version->version}.");
    }

    public function toggle(DocumentTemplate $documentTemplate)
    {
        $documentTemplate->update(['is_active' => !$documentTemplate->is_active]);

        $label = $documentTemplate->is_active ? 'activated' : 'deactivated';

        ActivityLog::record('updated', 'DocumentTemplate', $documentTemplate->id,
            "Template \"{$documentTemplate->name}\" {$label}"
        );

        return back()->with('success', "\"{$documentTemplate->name}\" {$label}.");
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        if ($documentTemplate->documentTypes()->exists()) {
            return back()->with('error', 'Cannot delete: template is used by ' . $documentTemplate->documentTypes()->count() . ' document type(s). Reassign them first.');
        }

        // Delete all version files
        foreach ($documentTemplate->versions as $v) {
            if ($v->paper_bg) {
                Storage::disk('public')->delete($v->paper_bg);
            }
        }

        $documentTemplate->delete();

        return redirect()->route('documents.templates.index')
            ->with('success', 'Template deleted.');
    }
}
