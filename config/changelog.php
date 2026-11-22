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
        'version' => '3.3',
        'date'    => '2026-11-22',
        'title'   => 'Kiosk on phones, with camera scan',
        'summary' => 'The kiosk fits a phone screen and can scan the Barangay ID with the phone camera.',
        'changes' => [
            ['type' => 'new', 'module' => 'Kiosk', 'text' => 'Log out from the kiosk: a small log-out icon at the top right of the kiosk screens asks to confirm, then signs the kiosk account out.'],
            ['type' => 'new', 'module' => 'Kiosk', 'text' => 'Scan with camera: on a phone or tablet, the Scan your ID step has a Scan with camera button that reads the QR code on the Barangay ID with the device camera — no USB scanner needed. At an event check-in, the camera opens again for the next resident. The USB scanner still works as before.'],
            ['type' => 'improved', 'module' => 'Kiosk', 'text' => 'The kiosk works on phones: the menu, ID scan, details check, document list, purpose, preview, events and queue number now fit the screen without anything cut off on the right, and the buttons at the bottom keep their labels on one line. Turning the phone sideways also works.'],
            ['type' => 'fixed', 'module' => 'Kiosk', 'text' => 'Start screen pictures and the barangay logo now show when the kiosk is logged in with a kiosk account (before, only admin accounts saw them). Pictures are also resized to a phone-friendly size when added in Settings → Kiosks, and pictures added before this update are converted automatically.'],
        ],
    ],
    [
        'version' => '3.2',
        'date'    => '2026-11-21',
        'title'   => 'Forgot password, safer login and blotter holds',
        'summary' => 'Forgot password now works, the login page is better protected against password guessing, and staff can put a blotter party on hold right from the case.',
        'changes' => [
            ['type' => 'fixed', 'module' => 'Login', 'text' => 'Forgot password on the login page now works: enter your email and get a link to set a new password (the same barangay-branded email as Reset password in Users). The page gives the same answer whether or not the email has an account, and limits how often it can be asked.'],
            ['type' => 'improved', 'module' => 'Login', 'text' => 'Stronger protection against password guessing: after 5 wrong passwords an account is locked on that device for 1 minute, and each lockout after that lasts longer (5 minutes, 15 minutes, then 1 hour). A correct login clears it. One device can also make at most 20 wrong attempts a minute across all accounts.'],
            ['type' => 'new', 'module' => 'Blotter', 'text' => 'Hold someone from a blotter case: on the case page, a complainant or respondent who is linked to a citizen record has a Hold button that stops them from being issued documents until the hold is lifted. The reason is filled in with the case number, the hold shows on the case, and the citizen’s Hold History links back to the case. Lift hold works from the case page too.'],
        ],
    ],
    [
        'version' => '3.1',
        'date'    => '2026-11-19',
        'title'   => 'Health printout, Print Paper page and a simpler raffle',
        'summary' => 'Print a health check-up on the barangay letterhead, choose every printout’s paper on its own Settings page, and run the raffle on one screen.',
        'changes' => [
            ['type' => 'new', 'module' => 'Kiosk', 'text' => 'Self-service kiosk for the barangay hall: a picture slideshow, then tap to start and choose Request a document or Register. Residents scan their Barangay ID with a USB QR scanner, pick the document and purpose, check a preview of the actual document, and get a queue number (K-001…). Every kiosk request waits for approval, and staff print it at the counter.'],
            ['type' => 'new', 'module' => 'Kiosk', 'text' => 'Check your details: right after scanning, the kiosk shows the resident’s record (name, birthday, address, civil status, mobile and email partly hidden, occupation, voter, groups, emergency contact) and asks “Are these your details?”. Tap Yes, all correct to continue, or Update my details to fix contact, civil status, occupation, address, voter/precinct or emergency contact. Name, birthday and sex are changed at the counter only.'],
            ['type' => 'new', 'module' => 'Kiosk', 'text' => 'Join an event: a third choice on the kiosk menu. Residents scan their Barangay ID and tap Join on today’s event (no details check, just 2 steps: Scan ID → Event) to check themselves in (shown as “kiosk” in the attendance list). The event’s areas and rules still apply, and the kiosk says why if someone can’t join or is already in. Events in the next two weeks show as “Check-in opens on …”. After a check-in the kiosk goes straight back to the event Scan your ID screen (after 8 seconds, or tap Next resident) and stays there, ready for the next person in line. That screen shows what the check-in is for (“Checking in for: Christmas Gift Giving · 8:00 AM · Covered Court · Raffle”, or a list when several events run today), and the menu card shows “Today: …”.'],
            ['type' => 'improved', 'module' => 'Kiosk', 'text' => 'Livelier kiosk background after Tap to start: soft colour shapes (blue, mint, peach, lilac) drift slowly behind every screen, with a light dot pattern at the edges and a few faint outline shapes. It is light on the tablet, and stays still on devices set to reduce motion.'],
            ['type' => 'new', 'module' => 'Events', 'text' => 'Allow check-in at the kiosk: a new option on the event form (Check-in & Raffle card). Only events with it turned on appear at the kiosk, on the event day or while reopened.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Detail Updates (Citizens menu, for users who can edit citizens): changes residents asked for at the kiosk, shown as on record → change to. Approve updates the citizen record and its activity log; Reject keeps it as it was. A bell notification and a menu badge show how many are waiting.'],
            ['type' => 'improved', 'module' => 'Kiosk', 'text' => 'The kiosk now needs a login: an account with the new Kiosk permission (Roles → Kiosk → use). Any tablet or computer can be the kiosk — just open the kiosk address and log in, no device pairing. A ready-made Kiosk role has only that permission, and accounts with it are kept on the kiosk screen — they can’t open any staff page. Kiosk screen can also be chosen as a user’s landing page.'],
            ['type' => 'new', 'module' => 'Users', 'text' => 'Reset password: a key button on each user in Users emails them a link to set a new password (works once, for 60 minutes). The email carries the barangay name, logo and address. Their old password keeps working until they set the new one. If email isn’t set up on the server, you get the link to copy and hand over instead. Needs the new Users → reset permission (Roles).'],
            ['type' => 'new', 'module' => 'Login', 'text' => 'Set a new password page for reset links, with show password and a match check. After saving, the login page says the password was reset.'],
            ['type' => 'fixed', 'module' => 'Login', 'text' => 'Remember me on the login page now works, keeping you signed in on that device (the kiosk tablet uses it to stay logged in).'],
            ['type' => 'improved', 'module' => 'Kiosk', 'text' => 'Modern kiosk design: a glass top bar with a 1-2-3-4 step guide, a greeting for the time of day, large gradient menu cards, an animated ID card on the scan screen with a live "Ready" status, document cards with fee badges, purpose choices with check marks and a bottom bar that shows your choice, a preview with icon summary, and a ticket-style queue number. The start screen shows a progress line per picture and a pulsing Tap to start button.'],
            ['type' => 'improved', 'module' => 'Kiosk', 'text' => 'The kiosk fits tablets held either way: in portrait the menu buttons stack, the document preview scrolls on its own so Yes, send request stays on screen, and the main buttons span the full width. Short landscape screens (1024 × 768) fit each step without scrolling, and the preview resizes when the tablet is turned.'],
            ['type' => 'new', 'module' => 'Settings', 'text' => 'Settings → Kiosks (Super Admin): the kiosk address with a Copy button, the accounts that can open the kiosk, and upload the start screen pictures with captions and order.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Offer at the kiosk switch on each document type. Kiosk requests show their queue number in the request list, with a From kiosk filter, and staff get a bell notification for each one.'],
            ['type' => 'improved', 'module' => 'Registrations', 'text' => 'Register at the kiosk: the same registration wizard, with a Cancel button and an automatic return to the kiosk start screen when left idle.'],
            ['type' => 'improved', 'module' => 'Registrations', 'text' => 'Privacy: rejecting an online registration now deletes the person’s photo and ID copies (the typed details and reason are kept). Approving moves the photo to the citizen instead of keeping a second copy.'],
            ['type' => 'fixed', 'module' => 'Security', 'text' => 'Uploaded-file folders can no longer be listed in a browser on any server setup.'],
            ['type' => 'fixed', 'module' => 'Barangay ID', 'text' => 'Barangay IDs now fit ID card printers: each side prints on its own CR80 card page with no margins, instead of one tall sheet with both sides.'],
            ['type' => 'improved', 'module' => 'Households', 'text' => 'The household list now takes three quarters of the page, with household cards side by side; the selected household opens in the right quarter.'],
            ['type' => 'improved', 'module' => 'Households', 'text' => 'New Household, Add Family and Add Member now open with the list of citizens not yet in a household, page by page — pick right away or type to narrow it down. Citizens already in a family no longer appear.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Activity tab on the citizen page: everything recorded about the person — profile edits (each field from old to new), photo, tags, hold, archive, Barangay ID, and their documents, health check-ups and blotter cases — grouped by day, with who did it and when. Shown to users who can see the Activity Logs.'],
            ['type' => 'new', 'module' => 'Barangay ID', 'text' => 'Add the ID photo while creating a Barangay ID: Take photo with the webcam or phone camera, or Upload photo. It is saved as the citizen’s profile photo and printed on the ID.'],
            ['type' => 'new', 'module' => 'Barangay ID', 'text' => 'Sign with pen: the citizen can sign on screen with a signature pad, pen tablet, touchscreen or mouse, and the signature goes straight onto the ID. Uploading a photo of the signature still works.'],
            ['type' => 'new', 'module' => 'Barangay ID', 'text' => 'Print setup on the ID print page: ID card printer or plain paper (with cut lines), front + back, front only or back only, and a fine-tune to move or resize the card if the printer is slightly off.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'Prescription (reseta) for medicines not available at the health station: write the medicine, quantity and how to take it on the Medicines step (or when editing), then Print reseta on half letter, A5, letter or A4. Nothing comes out of stock.'],
            ['type' => 'new', 'module' => 'Health', 'text' => 'Print a check-up record (patient, vital signs with flags, consultation, medicines given and signature lines) with the Print button on the check-up page.'],
            ['type' => 'new', 'module' => 'Settings', 'text' => 'Print Paper now has its own page in Settings. Choose the paper template (and version) for the Blotter Report, Inventory Release Slip, Document Fee Summary and Health Check-up Record in one place.'],
            ['type' => 'improved', 'module' => 'Settings', 'text' => 'The General Info page no longer includes the Print Paper choices.'],
            ['type' => 'new', 'module' => 'Settings', 'text' => 'Registration Links (Super Admin only): create a link or QR code residents use to register themselves online, with an optional expiry date and registration limit. Links can be turned off or deleted at any time.'],
            ['type' => 'improved', 'module' => 'Citizens', 'text' => 'The online registration pages now open in light mode, with a Dark mode / Light mode button at the top. The choice is remembered on that phone or computer and is separate from the staff theme.'],
            ['type' => 'fixed', 'module' => 'Citizens', 'text' => 'A registration whose PWD / Solo parent / Senior ID was marked Not valid no longer shows that group as Yes: the Registrations list shows it crossed out in red, and the details say “No — ID marked Not valid”.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Reviewing an online registration: every field can be corrected before approving (for example a wrong precinct number), with Save changes only or Approve. Corrections are recorded in the activity log.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'ID check on approval: each Senior / PWD / Solo parent ID sent with a registration must be marked Verified or Not valid (with an optional note). A Not valid ID means the citizen is added without that group; the check and who made it are saved in the citizen’s note.'],
            ['type' => 'new', 'module' => 'System', 'text' => 'Notifications in the top-bar bell: staff who approve citizens get a notification when someone registers online (with Senior / PWD / Solo parent shown). Click it to open the registration; it clears for everyone once the registration is approved or rejected. An open page checks for new ones every minute and shows a pop-up.'],
            ['type' => 'improved', 'module' => 'Citizens', 'text' => 'The online registration form is now a step-by-step wizard with large text, large fields and big buttons, made easy for senior citizens: About you, Address, Contact, Groups & proof, Photo & emergency contact, then a Review page before sending.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Online registration asks for proof: ticking Senior citizen (shown only for ages 60+), PWD or Solo parent requires a picture of the front and back of that ID. Staff see the ID pictures on the review page; they are kept private and never shown publicly.'],
            ['type' => 'new', 'module' => 'Citizens', 'text' => 'Online Registrations under Citizen: review what residents sent through a registration link, see possible duplicates (same name and birthday), then Approve to add them to the citizens list or Reject with a reason. Unapproved registrations never appear in citizen lists, events or households.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'When a document prints the person’s photo (like Barangay Clearance), the request form shows the current photo with Retake photo and Upload new — for residents and walk-ins. The new photo is saved to the person’s profile and printed on the document.'],
            ['type' => 'new', 'module' => 'Ordinances', 'text' => 'Add your own category to an ordinance or resolution: choose “+ Add new category…” in the Category list and type the name. It is then listed with the other categories, including in the filter on the list page.'],
            ['type' => 'new', 'module' => 'Ordinances', 'text' => 'Activity Logs has an “Ordinances & Resolutions” filter, and each entry links to its measure.'],
            ['type' => 'improved', 'module' => 'Dashboard', 'text' => 'The Activity dashboard no longer shows Budget: the disbursed card, voucher charts and recent vouchers were removed, and budget actions are left out of the activity feed, charts and staff counts. An Upcoming Events card takes the budget card’s place.'],
            ['type' => 'new', 'module' => 'System', 'text' => 'A notification pops up at the top right after every add, edit or delete — titled Created, Updated or Deleted — and form problems or failed saves show as a red notification.'],
            ['type' => 'improved', 'module' => 'System', 'text' => 'A loading bar at the top of the page shows while a page opens, a form saves or a search runs.'],
            ['type' => 'new', 'module' => 'Documents', 'text' => 'Document types can use the “Latest” paper template version: it always follows the template’s current version and switches automatically when a new version is saved. Pinning a specific version still works.'],
            ['type' => 'new', 'module' => 'Birthdays', 'text' => 'Pick a single day: choose a month, then a day (e.g. April → 20) from the day bar, which shows how many celebrants each day has. The list, summary and export follow the day, and previous / next move one day at a time.'],
            ['type' => 'fixed', 'module' => 'Birthdays', 'text' => '“+N more” on the Today and Next 7 days cards now shows everyone celebrating that day right there, with Show less to collapse it.'],
            ['type' => 'improved', 'module' => 'Birthdays', 'text' => 'Birthdays page redesigned: a month strip with each month’s count, quarter buttons, previous / next, and a summary of celebrants, male / female and milestones.'],
            ['type' => 'new', 'module' => 'Birthdays', 'text' => 'Today and Next 7 days cards with photos, the age each person turns, and a call button.'],
            ['type' => 'new', 'module' => 'Birthdays', 'text' => 'Milestone birthdays are marked: turning 18 (can register to vote), 60 (senior citizen) and 80, 85, 90, 95, 100 (Centenarians Act cash gift).'],
            ['type' => 'improved', 'module' => 'Birthdays', 'text' => 'The list is grouped by day, filters apply as soon as you pick them, it shows 50 at a time so big months open quickly, and phones get a card view.'],
            ['type' => 'improved', 'module' => 'Users', 'text' => 'Only the Super Admin can give, change or remove a user’s role. Other staff who manage users can still add users and edit their details.'],
            ['type' => 'improved', 'module' => 'Users', 'text' => 'Super Admin accounts can only be edited or deactivated by a Super Admin, and a Super Admin cannot remove the role from their own account.'],
            ['type' => 'improved', 'module' => 'Ordinances', 'text' => 'The log now shows what changed: title, number, status, dates, votes, authors, amends/repeals and the signed copy, each from old to new. Removing a timeline entry is logged too.'],
            ['type' => 'new', 'module' => 'Ordinances', 'text' => 'Change history on each ordinance or resolution page (for users who can see the Activity Logs), with a link to its full history.'],
            ['type' => 'improved', 'module' => 'Events', 'text' => 'The raffle opens straight to the draw screen from the event page (Open Raffle). Staff must be logged in and allowed to edit events.'],
            ['type' => 'improved', 'module' => 'Events', 'text' => 'Removed the PIN, the shareable link and the separate Display and Controller screens. To show the draw on a projector, open the raffle on the laptop connected to it.'],
            ['type' => 'fixed', 'module' => 'Events', 'text' => 'A single raffle winner must now be on the event’s attendance list before it is saved.'],
        ],
    ],

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
