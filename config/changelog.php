<?php

/*
|--------------------------------------------------------------------------
| Version history — shown on Settings → Version & Updates
|--------------------------------------------------------------------------
|
| Newest release first. The first entry is the current version.
| To publish an update: add a new entry at the top. Each change has:
|   type   — 'new' | 'improved' | 'fixed'
|   module — the area of the system (used for the filter chips)
|   text   — one plain sentence for barangay staff
|
| Versions before 3.0 were reconstructed from the dated database changes
| and the git history, so they are summaries rather than full lists.
|
*/

return [

    [
        'version' => '3.0',
        'date'    => '2026-11-15',
        'title'   => 'Health check-ups, Ordinances and a better Documents page',
        'summary' => 'Two new modules (Health Check-ups and Ordinances & Resolutions), a redesigned Document Requests page with a Fee Summary, a reservation calendar for inventory, event eligibility rules, and finer role permissions.',
        'changes' => [
            // Health
            ['type' => 'new', 'module' => 'Health', 'text' => 'Health Check-ups: record libreng check-ups and walk-in consultations for citizens (search or scan the Barangay ID) or for people not in the records.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'Vital signs with live flags (e.g. High BP stage 2, Fever, Overweight) and BMI calculated automatically.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'Consultation notes: complaint, findings, advice, referral and follow-up date. The list shows follow-ups due in the next 7 days and high BP this month.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'Medicines given at a check-up come out of Inventory stock. For citizens, a released Inventory release is created automatically.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'New check-ups use a step-by-step wizard: Patient → Vital signs → Consultation → Medicines → Review, with a check on each step.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'Health tab on the citizen profile, and check-ups appear in All Transactions.'],
            // Ordinances
            ['type' => 'new', 'module' => 'Ordinances', 'text' => 'Ordinances & Resolutions: numbering per year, authors and sponsors, readings timeline, votes, veto, and amend / repeal links.'],
            ['type' => 'new', 'module' => 'Ordinances', 'text' => 'Upload the signed copy (PDF or photo); it is kept private to users who can view ordinances.'],
            // Documents
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Fee Summary on Document Requests: possible income by document type (collected, awaiting release, short), following the filters.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Printable Fee Summary report on the barangay letterhead chosen in Settings → Print Paper.'],
            ['type' => 'improved', 'module' => 'Documents', 'text' => 'Document Requests page redesigned: period bar, one summary strip, status tabs with counts, removable filter chips, shorter rows and a phone-friendly card view.'],
            ['type' => 'improved', 'module' => 'Documents', 'text' => '“Ready for release” now has its own color, so it no longer looks like “Pending”.'],
            // Inventory
            ['type' => 'new', 'module' => 'Inventory', 'text' => 'Reservation calendar for items like the ambulance, covered court or service vehicle, with buffer days and protection against double booking.'],
            ['type' => 'new', 'module' => 'Inventory', 'text' => '“Use for medicine” on categories: only items in these categories can be given at a Health check-up.'],
            // Events
            ['type' => 'new', 'module' => 'Events', 'text' => 'Event eligibility: age range, men or women only, groups (senior, PWD, solo parent, voter) and tags — enforced at check-in, bulk add and the raffle.'],
            // Roles
            ['type' => 'new', 'module' => 'Roles', 'text' => 'New “Fees” permission for Documents: decides who sees the Fee Summary and fees-collected figures.'],
            ['type' => 'improved', 'module' => 'Roles', 'text' => 'Inventory permissions split per area (items, categories, releases, rentals, schedule).'],
            ['type' => 'improved', 'module' => 'Roles', 'text' => 'Citizen permissions split: approve, tag, demographics, Barangay IDs, birthdays and more, each set separately.'],
            ['type' => 'improved', 'module' => 'Roles', 'text' => 'Health records are private: the Viewer role cannot see them, and only roles with “Dispense” can give medicines from stock.'],
            // Settings
            ['type' => 'new', 'module' => 'Settings', 'text' => 'Version & Updates page (this page) listing what is new in each version.'],
            ['type' => 'improved', 'module' => 'Settings', 'text' => 'Print Paper now also covers the Document Fee Summary.'],
            // Citizens
            ['type' => 'fixed', 'module' => 'Citizens', 'text' => 'Broken profile photos: links to photo files that were missing on the server were cleared (the links were backed up). Citizens without a photo show their initials.'],
        ],
    ],

    [
        'version' => '2.3',
        'date'    => '2026-10-27',
        'title'   => 'Holds, walk-ins and the live raffle',
        'summary' => 'Citizen holds, walk-in document requests, voiding, official print paper, a two-screen raffle and optional family income classification.',
        'changes' => [
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Hold: block a citizen from being issued documents until the hold is lifted. A reason is required.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Walk-ins: issue documents to people who are not registered residents, asking only for the details the document prints.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Void a released document with a reason; it stays on record and is left out of fee totals.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Each document type has its own color on lists and the dashboard.'],
            ['type' => 'new', 'module' => 'Settings', 'text' => 'Print Paper: choose the letterhead (and version) for the blotter report and inventory release slip.'],
            ['type' => 'new', 'module' => 'Events', 'text' => 'Live raffle on two screens (projector Display + phone Controller), prize claiming, and draws limited to selected zones.'],
            ['type' => 'new', 'module' => 'Events', 'text' => 'Events can be limited to selected zones / addresses, and closed events can be reopened.'],
            ['type' => 'new', 'module' => 'Households', 'text' => 'Optional family income classification (Poor to Rich), switched on in Settings.'],
        ],
    ],

    [
        'version' => '2.2',
        'date'    => '2026-09-22',
        'title'   => 'Citizen archive and record clean-up',
        'summary' => 'Safer archiving and faster citizen and household searches.',
        'changes' => [
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Archive with a reason, and a separate record of deleted citizens.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Verified date on citizen records.'],
            ['type' => 'improved', 'module' => 'Citizens', 'text' => 'Citizen details can now be searched and filtered on every field.'],
            ['type' => 'new', 'module' => 'Households', 'text' => 'Archive and restore households.'],
            ['type' => 'improved', 'module' => 'Households', 'text' => 'Faster family and household look-ups.'],
        ],
    ],

    [
        'version' => '2.1',
        'date'    => '2026-08-20',
        'title'   => 'Budget templates and rentals',
        'summary' => 'Chart of accounts and budget templates, plus borrowing and rental of barangay items.',
        'changes' => [
            ['type' => 'new', 'module' => 'Budget', 'text' => 'Chart of accounts, sub-programs, BBPF-02 fields on line items, and reusable budget templates.'],
            ['type' => 'new', 'module' => 'Inventory', 'text' => 'Rentals / borrowing: due dates, rental fee and deposit, partial returns and item condition.'],
        ],
    ],

    [
        'version' => '2.0',
        'date'    => '2026-08-18',
        'title'   => 'Ready for deployment',
        'summary' => 'Preparation for the live server.',
        'changes' => [
            ['type' => 'improved', 'module' => 'System', 'text' => 'Uploaded files are served correctly on the live server.'],
            ['type' => 'fixed', 'module' => 'Login', 'text' => 'The login form no longer shows demo credentials.'],
            ['type' => 'fixed', 'module' => 'Budget', 'text' => 'Cash advances page layout fixed.'],
        ],
    ],

    [
        'version' => '1.0',
        'date'    => '2026-07-15',
        'title'   => 'First release',
        'summary' => 'The base Barangay Smart System.',
        'changes' => [
            ['type' => 'new', 'module' => 'System', 'text' => 'Citizens, households, Barangay IDs, birthdays, blotter, documents & certificates, events & attendance, inventory, budget, users & roles, and activity logs.'],
        ],
    ],

];
