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

    // Ready-made certificate template snippets staff can copy into the editor.
    public function samples()
    {
        $samples = [
            [
                'name'  => 'Barangay Clearance',
                'desc'  => 'Modern clearance: green title, details table, photo, holder & captain signatures, QR.',
                'icon'  => 'mgc_certificate_line',
                'html'  => <<<'HTML'
<h1 style="text-align:center; color:#1a6b2e; letter-spacing:1px; margin:0 0 14px 0;">BARANGAY CLEARANCE</h1>

<table class="no-border" style="width:100%;">
  <tr>
    <td style="width:68%; vertical-align:top;">
      <p style="text-transform:uppercase; letter-spacing:0.5px;">To whom it may concern:</p>
      <p>This is to certify that the person whose name, photo and signature appears hereon is a bonafide resident of {{ brgy_name }}, Municipality of {{ city }}, {{ province }}.</p>
    </td>
    <td style="width:32%; vertical-align:top; text-align:center; padding-left:14px;">
      {{ profile_photo [130,150] }}
    </td>
  </tr>
</table>

<table class="no-border" style="width:100%;">
  <tr>
    <td style="width:68%; vertical-align:top;">
      <table class="no-border">
        <tr><td style="width:38%; text-transform:uppercase;">Name</td><td style="width:6%;">:</td><td><strong>{{ fullname }}</strong></td></tr>
        <tr><td style="text-transform:uppercase;">Address</td><td>:</td><td><strong>{{ address }}</strong></td></tr>
        <tr><td style="text-transform:uppercase;">Date of Birth</td><td>:</td><td><strong>{{ birthday }}</strong></td></tr>
        <tr><td style="text-transform:uppercase;">Purpose</td><td>:</td><td><strong>{{ purpose }}</strong></td></tr>
        <tr><td style="text-transform:uppercase;">Date Issued</td><td>:</td><td><strong>{{ date_full }}</strong></td></tr>
        <tr><td style="text-transform:uppercase;">Valid Until</td><td>:</td><td><strong>{{ expiry_6months }}</strong></td></tr>
      </table>
    </td>
    <td style="width:32%; vertical-align:top; padding-left:14px;">
      <table style="width:100%;">
        <tr><td style="text-align:center; font-size:0.8em; text-transform:uppercase; letter-spacing:0.5px; border:1px solid #ccc; border-bottom:none;">Signature of Holder</td></tr>
        <tr><td style="text-align:center; border:1px solid #ccc; border-top:none; height:40px;"><span style="font-family:'Brush Script MT','Segoe Script',cursive; font-size:22px;">{{ fullname }}</span></td></tr>
      </table>
    </td>
  </tr>
</table>

<p style="margin-top:10px;">This clearance is issued upon the request of the above-named person for whatever legal purpose it may serve.</p>

<table class="no-border" style="width:100%; margin-top:18px;">
  <tr>
    <td style="width:40%; vertical-align:bottom;">{{ qr_image [90] }}</td>
    <td style="width:60%; text-align:center; vertical-align:bottom;">
      <span style="font-family:'Brush Script MT','Segoe Script',cursive; font-size:26px;">{{ captain }}</span>
      <div style="border-top:1px solid #000; margin-top:2px; padding-top:3px;">
        <strong style="letter-spacing:0.5px;">{{ captain }}</strong><br>
        <span style="font-size:0.9em;">Punong Barangay</span>
      </div>
    </td>
  </tr>
</table>
HTML,
            ],
            [
                'name'  => 'Certificate of Indigency',
                'desc'  => 'Simple free certificate with signature.',
                'icon'  => 'mgc_heart_hand_line',
                'html'  => <<<'HTML'
<p style="text-align:center;"><strong>CERTIFICATE OF INDIGENCY</strong></p>
<p>&nbsp;</p>
<p>This is to certify that <strong>{{ fullname }}</strong>, {{ age }} years old, {{ civil_status }}, residing at {{ address }}, belongs to an indigent family in this barangay.</p>
<p>This certification is issued upon request for <strong>{{ purpose }}</strong>.</p>
<p>Given this {{ date_full }} at {{ brgy_name }}, {{ city }}.</p>
<p>&nbsp;</p>
<p style="text-align:right;"><strong>{{ captain }}</strong><br>Punong Barangay</p>
HTML,
            ],
            [
                'name'  => 'Certificate of Residency',
                'desc'  => 'Confirms length of residence.',
                'icon'  => 'mgc_home_4_line',
                'html'  => <<<'HTML'
<p style="text-align:center;"><strong>CERTIFICATE OF RESIDENCY</strong></p>
<p>&nbsp;</p>
<p>This is to certify that <strong>{{ fullname }}</strong>, {{ age }} years old, {{ civil_status }}, is a bona fide resident of {{ address }} since {{ year_stay }}.</p>
<p>This certification is issued upon request for <strong>{{ purpose }}</strong>.</p>
<p>Issued this {{ date_full }} at {{ brgy_name }}, {{ city }}, {{ province }}.</p>
<p>&nbsp;</p>
<p style="text-align:right;"><strong>{{ captain }}</strong><br>Punong Barangay</p>
HTML,
            ],
            [
                'name'  => 'Business Permit',
                'desc'  => 'Uses custom fields for business details.',
                'icon'  => 'mgc_shop_line',
                'html'  => <<<'HTML'
<p style="text-align:center;"><strong>BARANGAY BUSINESS PERMIT</strong></p>
<p style="text-align:center;">Control No. <strong>{{ doc_number }}</strong></p>
<p>&nbsp;</p>
<p>This is to certify that <strong>{{ business_name }}</strong>, a <strong>{{ business_type }}</strong> located at {{ business_address }}, owned and operated by <strong>{{ fullname }}</strong> of {{ address }}, is hereby granted permission to operate within this barangay, subject to existing rules and regulations.</p>
<p>Valid until {{ expiry_1year }}. Issued this {{ date_full }} at {{ brgy_name }}, {{ city }}, {{ province }}.</p>
<p>&nbsp;</p>
<p style="text-align:right;"><strong>{{ captain }}</strong><br>Punong Barangay</p>
HTML,
            ],
            [
                'name'  => 'Data Table (bordered)',
                'desc'  => 'Example of a bordered table for tabular data.',
                'icon'  => 'mgc_table_2_line',
                'html'  => <<<'HTML'
<p style="text-align:center;"><strong>SUMMARY OF DETAILS</strong></p>
<table>
  <thead>
    <tr><th>Field</th><th>Value</th></tr>
  </thead>
  <tbody>
    <tr><td>Name</td><td>{{ fullname }}</td></tr>
    <tr><td>Address</td><td>{{ address }}</td></tr>
    <tr><td>Purpose</td><td>{{ purpose }}</td></tr>
    <tr><td>Control No.</td><td>{{ doc_number }}</td></tr>
    <tr><td>Date Issued</td><td>{{ date_full }}</td></tr>
  </tbody>
</table>
HTML,
            ],
            [
                'name'  => 'Placeholder Test Sheet',
                'desc'  => 'Shows every placeholder at once — for QA.',
                'icon'  => 'mgc_bug_line',
                'html'  => <<<'HTML'
<p style="text-align:center;"><strong>PLACEHOLDER TEST — {{ date_full }}</strong></p>
<ul>
  <li>Full name: {{ fullname }}</li>
  <li>First / Middle / Last: {{ firstname }} / {{ middlename }} / {{ lastname }} {{ suffix }}</li>
  <li>Gender / Civil status: {{ gender }} / {{ civil_status }}</li>
  <li>Age / Birthday / Birthplace: {{ age }} / {{ birthday }} / {{ birthplace }}</li>
  <li>Address: {{ address }}</li>
  <li>Contact / Email / Occupation: {{ contact }} / {{ email }} / {{ occupation }}</li>
  <li>Year of stay: {{ year_stay }}</li>
  <li>Purpose: {{ purpose }}</li>
  <li>Control No. / OR No.: {{ doc_number }} / {{ or_number }}</li>
  <li>Barangay / City / Province: {{ brgy_name }} / {{ city }} / {{ province }}</li>
  <li>Captain / Issued by: {{ captain }} / {{ issued_by }}</li>
  <li>Date parts: {{ date_day }} · {{ date_day_th }} · {{ date_month }} · {{ date_year }}</li>
  <li>Expiry: {{ expiry_3months }} · {{ expiry_6months }} · {{ expiry_1year }}</li>
</ul>
HTML,
            ],
        ];

        // Small "how-to" formatting snippets — focused techniques staff can drop
        // into any template (tables, layout columns, signature blocks, etc.).
        $snippets = [
            [
                'name' => 'Bordered table',
                'desc' => 'A normal table with visible grid lines — good for data.',
                'icon' => 'mgc_table_2_line',
                'html' => <<<'HTML'
<table>
  <thead>
    <tr><th>Field</th><th>Value</th></tr>
  </thead>
  <tbody>
    <tr><td>Name</td><td>{{ fullname }}</td></tr>
    <tr><td>Address</td><td>{{ address }}</td></tr>
    <tr><td>Purpose</td><td>{{ purpose }}</td></tr>
  </tbody>
</table>
HTML,
            ],
            [
                'name' => 'Borderless table',
                'desc' => 'Add class="no-border" for an invisible table — great for layout.',
                'icon' => 'mgc_layout_line',
                'html' => <<<'HTML'
<table class="no-border">
  <tr>
    <td style="width:50%;">Left column content</td>
    <td style="text-align:right;">Right column content</td>
  </tr>
</table>
HTML,
            ],
            [
                'name' => 'Photo + signature layout',
                'desc' => 'Borderless 2-column layout: photo on the left, signature on the right.',
                'icon' => 'mgc_pic_line',
                'html' => <<<'HTML'
<table class="no-border">
  <tr>
    <td style="width:50%;">{{ profile_photo [100,120] }}<br>Applicant's Photo</td>
    <td style="text-align:right; vertical-align:bottom;"><strong>{{ captain }}</strong><br>Punong Barangay</td>
  </tr>
</table>
HTML,
            ],
            [
                'name' => 'Signature block',
                'desc' => 'Right-aligned signatory with title.',
                'icon' => 'mgc_signature_line',
                'html' => <<<'HTML'
<p>&nbsp;</p>
<p style="text-align:right;"><strong>{{ captain }}</strong><br>Punong Barangay</p>
HTML,
            ],
            [
                'name' => 'Signature over line',
                'desc' => "Real uploaded captain's signature above a line, name in bold, title below — centered.",
                'icon' => 'mgc_signature_line',
                'html' => <<<'HTML'
<p>&nbsp;</p>
<table class="no-border" style="width:auto; margin-left:auto; margin-right:auto; text-align:center;">
  <tr>
    <td style="text-align:center; padding:0;">
      <div style="min-height:52px; display:flex; align-items:flex-end; justify-content:center;">
        {{ captain_signature [180] }}
      </div>
      <div style="border-top:1px solid #000; margin-top:2px; padding-top:4px; min-width:220px;">
        <strong style="letter-spacing:0.5px;">{{ captain }}</strong><br>
        <span style="font-size:0.9em;">Punong Barangay</span>
      </div>
    </td>
  </tr>
</table>
HTML,
            ],
            [
                'name' => 'Centered title',
                'desc' => 'A bold, centered certificate heading.',
                'icon' => 'mgc_text_line',
                'html' => <<<'HTML'
<p style="text-align:center;"><strong>CERTIFICATE OF RESIDENCY</strong></p>
HTML,
            ],
            [
                'name' => 'Two columns of details',
                'desc' => 'Borderless table used to place two fields side by side.',
                'icon' => 'mgc_columns_3_line',
                'html' => <<<'HTML'
<table class="no-border">
  <tr>
    <td style="width:50%;"><strong>Date of Birth:</strong> {{ birthday }}</td>
    <td style="width:50%;"><strong>Age:</strong> {{ age }}</td>
  </tr>
  <tr>
    <td><strong>Civil Status:</strong> {{ civil_status }}</td>
    <td><strong>Gender:</strong> {{ gender }}</td>
  </tr>
</table>
HTML,
            ],
            [
                'name' => 'Bullet list',
                'desc' => 'A simple bulleted list of items.',
                'icon' => 'mgc_list_check_line',
                'html' => <<<'HTML'
<ul>
  <li>Name: {{ fullname }}</li>
  <li>Address: {{ address }}</li>
  <li>Purpose: {{ purpose }}</li>
</ul>
HTML,
            ],
            [
                'name' => 'Validity line',
                'desc' => 'Issue date with an expiry date.',
                'icon' => 'mgc_calendar_line',
                'html' => <<<'HTML'
<p>Issued this {{ date_full }}. Valid until {{ expiry_6months }}.</p>
HTML,
            ],
        ];

        return view('documents.types.samples', compact('samples', 'snippets'));
    }

    public function store(Request $request)
    {
        // Normalize before validation so the unique check runs on the stored form.
        if ($request->filled('prefix')) {
            $request->merge(['prefix' => strtoupper(trim($request->input('prefix')))]);
        }

        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'short_name'           => 'nullable|string|max:100',
            'prefix'               => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:eb_document_types,prefix'],
            'description'          => 'nullable|string',
            'is_paid'              => 'boolean',
            'fee'                  => 'nullable|numeric|min:0',
            'requires_approval'    => 'boolean',
            'allow_body_edit'      => 'boolean',
            'template_body'        => 'nullable|string',
            'is_active'            => 'boolean',
            'document_template_id'         => 'nullable|exists:eb_document_templates,id',
            'document_template_version_id' => 'nullable|exists:eb_document_template_versions,id',
        ], [
            'prefix.required' => 'A control number prefix is required.',
            'prefix.unique'   => 'That prefix is already used by another document type.',
            'prefix.regex'    => 'Prefix may only contain letters, numbers, and dashes.',
        ]);

        $data['is_paid']           = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['allow_body_edit']   = $request->boolean('allow_body_edit');
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
        // Normalize before validation so the unique check runs on the stored form.
        if ($request->filled('prefix')) {
            $request->merge(['prefix' => strtoupper(trim($request->input('prefix')))]);
        }

        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'short_name'           => 'nullable|string|max:100',
            'prefix'               => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:eb_document_types,prefix,' . $documentType->id],
            'description'          => 'nullable|string',
            'is_paid'              => 'boolean',
            'fee'                  => 'nullable|numeric|min:0',
            'requires_approval'    => 'boolean',
            'allow_body_edit'      => 'boolean',
            'template_body'        => 'nullable|string',
            'is_active'            => 'boolean',
            'document_template_id'         => 'nullable|exists:eb_document_templates,id',
            'document_template_version_id' => 'nullable|exists:eb_document_template_versions,id',
        ], [
            'prefix.required' => 'A control number prefix is required.',
            'prefix.unique'   => 'That prefix is already used by another document type.',
            'prefix.regex'    => 'Prefix may only contain letters, numbers, and dashes.',
        ]);

        $data['is_paid']           = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['allow_body_edit']   = $request->boolean('allow_body_edit');
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
