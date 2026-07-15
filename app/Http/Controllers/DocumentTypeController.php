<?php

namespace App\Http\Controllers;

use App\Models\DocumentField;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $types = DocumentType::withCount(['requests', 'fields'])
            ->with(['template' => fn($q) => $q->withCount('versions'), 'pinnedVersion'])
            ->orderBy('name')
            ->get();

        return view('documents.types.index', compact('types'));
    }

    public function create()
    {
        $templates = \App\Models\DocumentTemplate::active()
            ->with(['currentVersion', 'versions' => fn($q) => $q->orderByDesc('version')])
            ->orderBy('name')->get();
        return view('documents.types.edit', ['type' => new DocumentType(), 'templates' => $templates]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'short_name'           => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'is_paid'              => 'boolean',
            'fee'                  => 'nullable|numeric|min:0',
            'requires_approval'    => 'boolean',
            'template_body'        => 'nullable|string',
            'is_active'            => 'boolean',
            'document_template_id'         => 'nullable|exists:eb_document_templates,id',
            'document_template_version_id' => 'nullable|exists:eb_document_template_versions,id',
        ]);

        $data['is_paid']           = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['is_active']         = $request->boolean('is_active');
        $data['fee']               = $data['is_paid'] ? ($data['fee'] ?? 0) : 0;

        $type = DocumentType::create($data);

        $this->syncFields($type, $request);

        return redirect()->route('documents.types.edit', $type)
            ->with('success', 'Document type created successfully.');
    }

    public function edit(DocumentType $documentType)
    {
        $documentType->load(['fields', 'pinnedVersion']);
        $templates = \App\Models\DocumentTemplate::active()
            ->with(['currentVersion', 'versions' => fn($q) => $q->orderByDesc('version')])
            ->orderBy('name')->get();
        return view('documents.types.edit', ['type' => $documentType, 'templates' => $templates]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'short_name'           => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'is_paid'              => 'boolean',
            'fee'                  => 'nullable|numeric|min:0',
            'requires_approval'    => 'boolean',
            'template_body'        => 'nullable|string',
            'is_active'            => 'boolean',
            'document_template_id'         => 'nullable|exists:eb_document_templates,id',
            'document_template_version_id' => 'nullable|exists:eb_document_template_versions,id',
        ]);

        $data['is_paid']           = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['is_active']         = $request->boolean('is_active');
        $data['fee']               = $data['is_paid'] ? ($data['fee'] ?? 0) : 0;
        $documentType->update($data);
        $this->syncFields($documentType, $request);

        return redirect()->route('documents.types.edit', $documentType)
            ->with('success', 'Document type updated successfully.');
    }

    public function toggle(DocumentType $documentType)
    {
        $documentType->update(['is_active' => !$documentType->is_active]);

        $label = $documentType->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$documentType->name}\" {$label}.");
    }

    public function destroy(DocumentType $documentType)
    {
        if ($documentType->requests()->exists()) {
            return back()->with('error', 'Cannot delete: this type has existing requests.');
        }

        if ($documentType->paper_bg) {
            Storage::disk('public')->delete($documentType->paper_bg);
        }

        $documentType->delete();

        return redirect()->route('documents.types.index')
            ->with('success', 'Document type deleted.');
    }

    private function syncFields(DocumentType $type, Request $request): void
    {
        $type->fields()->delete();

        $fields = $request->input('fields', []);
        foreach ($fields as $i => $field) {
            if (empty($field['field_key']) || empty($field['field_label'])) continue;

            $options = null;
            if (($field['field_type'] ?? 'text') === 'select' && !empty($field['field_options'])) {
                $options = array_filter(array_map('trim', explode("\n", $field['field_options'])));
            }

            DocumentField::create([
                'document_type_id' => $type->id,
                'field_key'        => Str::slug($field['field_key'], '_'),
                'field_label'      => $field['field_label'],
                'field_type'       => $field['field_type'] ?? 'text',
                'column_width'     => max(1, min(12, (int) ($field['column_width'] ?? 12))),
                'field_options'    => $options ?: null,
                'default_value'    => $field['default_value'] ?? null,
                'is_required'      => !empty($field['is_required']),
                'sort_order'       => $i,
            ]);
        }
    }
}
