# Barangay Smart System — Feature List

This list covers every feature that exists in the system today, checked against the actual code (pages, buttons, saving logic and permissions). Planned or "idea" features are not included.

**Not covered here:** the Budgeting module (budgets, vouchers, cash advances, suppliers, liquidation and related reports). The home dashboard also shows a few budget charts; those are left out as well.

---

## Who can do what (user roles)

The system comes with three roles. An administrator can also create new roles and choose exactly which permissions each one has.

| Role | What they can do |
|---|---|
| **Super Admin** | Everything, including users, roles and settings. |
| **Staff** | Can view every module. Can add and edit citizens, households, documents, blotter cases, ordinances and resolutions, health check-ups (without giving medicines from stock), events and tags, and export citizen lists. Cannot delete anything, archive/restore or put citizens on hold, void documents, change inventory, change settings, or manage users and roles. |
| **Viewer** | Can view every module except health records, but cannot add, change or delete anything. |
| **Custom roles** | Built by the administrator on the Roles page by ticking permissions per module (view / add / edit / delete, plus special ones like export, archive, hold and void). |

Extra control for documents: each document type can be limited to certain roles (for example, only some staff may issue a Barangay Clearance).

Note: Staff and Viewer can open the Users, Roles, Settings and Activity Log pages in read-only mode. If that is not wanted, remove those "view" permissions from the role.

---

## Modules

Status key: **Complete** = works end to end. **Partially working** = usable, but part of it is missing or has a known problem. **Unfinished** = only partly built.

### 1. Login & User Accounts
**Features**
- Log in and log out with email and password, with a "remember me" option.
- Forgot password (link on the login page): sends a barangay-branded email with a link to set a new password (works once, for 60 minutes).
- Each user can edit their own name, email and password (My Profile).
- Administrator can add users, assign their role, edit them, and switch them active or inactive.
- **Reset password** (key button in Users, needs the Users → reset permission): emails the user a link to set a new password. If email isn't set up, the admin gets the link to copy and hand over.
- **Only the Super Admin gives roles.** Other users with the Users permissions can add users and edit names, emails and passwords, but the role field is locked for them, and Super Admin accounts are locked too (no edit, no deactivate). A Super Admin cannot remove the Super Admin role from their own account.
- Each user can be given their own start page after login (for example, a document clerk can land directly on Document Requests).
- **Zones per account** (Super Admin, when adding or editing a user): tick one or more zones the account handles. The account then only sees citizens, households, Barangay IDs, birthdays, document requests, health check-ups, holds and blotter cases (any party from its zones) in those zones; dashboards and exports count only those zones; zone pickers list only its zones and it can only add residents there. Opening a record from another zone by link answers "not found". No zones ticked = all zones; the Super Admin always sees all. Events, inventory and online self-registrations are not limited by zone. A non-Super Admin who adds a user gives the new account their own zones. Changes are written to the activity log.
- **Inactive accounts cannot log in**: the login page says the account is deactivated, and the attempt is recorded in Login History.
- The login page shows why a login failed (wrong email or password, locked for a few minutes, or account deactivated).
- **Login History** (Login History button on Settings → Users, needs the Login History permission): every successful login (including "Remember me"), wrong password, attempt with an unknown email, lockout, blocked inactive account and log-out, with the date and time, account, device (e.g. "Chrome on Android") and IP address. Today's totals at the top; filter by what happened, account, date range, or search by name, email or IP; click an IP to see everything from it. The Users list shows each user's last login.

**Roles:** everyone (own profile); Super Admin or anyone with the Users permissions (managing users).

**Status: Partially working**
- Switching a user to "inactive" stops new logins, but a user who is already logged in stays logged in until they log out.
- The **public sign-up page is open**: anyone who finds it can create an account. A new account has no role, so it can only see the activity dashboard and its own profile, but the page should be turned off for a government system.
- Password reset only works once the server's email sending is set up.
- Two-step login (one-time codes) is switched on in a configuration file but has no screens, so it is not available.

---

### 2. Home Dashboard (Activity)
**Features**
- Totals: citizens, households, new citizens today and this month.
- Open blotter cases and new cases this week.
- Pending document requests and documents issued today.
- Events this month and upcoming events.
- "Today's activity" feed of what staff did.
- Weekly activity chart (last 7 days), actions per module (last 30 days), and actions per staff member.

**Roles:** every logged-in user.

**Status: Complete**

---

### 3. Citizen Records
**Features**
- Register residents with full details: name, birthday and birthplace, gender, civil status, address (zone, house number, street), contact details, occupation, year of stay, owner or renter (with the house owner's name for renters), photo (positioned in a square frame with a face guide: drag, zoom, rotate, center — the same tool is used on the ID and document request pages), emergency contact, and flags for voter, PWD, senior citizen and solo parent.
- Each citizen gets an automatic Citizen ID number (prefix and number of digits are set in Settings) and a unique QR code.
- **Register a minor** under a parent: the child automatically gets the parent's household and address.
- **Duplicate check** while adding, editing or registering a minor, across every record (archived citizens, walk-ins and other zones too):
  - Same first name, last name and birthday (ignoring capitals, extra spaces, dots and ñ/n) **cannot be saved**. The message names the existing record, and an archived match points to the Archive to restore it instead.
  - **Possible duplicates** are listed while typing: same birthday with a close name (a typo, "Ma." for "Maria", "Juan" for "Juan Carlos"), the same name with a different birthday, or a walk-in with the same name and birthday. Adding or registering a minor then needs the "This is a different person" tick. On Edit the list is shown for information only.
- **Settings → Citizen Fields**: choose which details are required on Add, Edit and Register Minor (middle name, suffix, citizenship, occupation, photo, year of stay, contact no., email, emergency contact fields) and whether contact no. or email must be unique across all records (the page shows how many are already shared). Name, birthday, birthplace, gender, civil status and zone are always required. An option makes the middle name part of the duplicate-person check. Changes are recorded in the Activity Log.
- Citizen list with live search (results update while typing), table view or card (grid) view, and filters for gender, civil status, zone, age range, voter, PWD, solo parent, senior, household role and tags. The list shows voters only by default. Applying filters closes the filter panel and shows a "Filtered by" row listing each active filter in plain words, each with an × to remove it, plus Clear all.
- Citizen profile page with a quick history of the person's documents, blotter cases and events.
- **Scan an ID on the Citizens page** (with a handheld scanner, anytime), **or click a citizen row or card and choose "All Transactions" in the action bar**, to open a pop-up with everything about that person: photo, Citizen ID number, voter/PWD/senior/solo parent badges, hold warning, household and ID validity, plus one timeline of their documents, blotter cases, events attended, raffle wins, items received or borrowed, schedule reservations, ID cards, holds and record changes. Tabs filter by type, each entry opens its record, and there are shortcuts to the full profile and to a new document request. Staff only see the sections their role allows.
- Shows clearly when a citizen's profile is locked or on hold.
- **Archive and restore**: move citizens out of the active list (for example, moved away or deceased) and bring them back later.
- **Hold**: block a citizen from being issued any documents until the hold is lifted. A reason is required and it is recorded.
- **Tags**: colored labels (for example "4Ps", "Indigent", "Scholar") that can be attached to citizens and used as filters.
- **Export** the citizen list to **CSV (Excel)** or **PDF**, with a choice of columns. It follows the filters currently applied.
- Approval status field on each citizen. Some features (event check-in, bulk attendance) only include citizens marked as approved.

**Roles:** each action has its own permission: view, create (also Register Minor), edit, **approve** (set the approval status; without it a new record is saved as Pending and edits keep the current status), **tag** (assign tags), export, archive (set inactive), restore, hold, and **demographics** (the dashboard). Viewer has view and demographics; Staff has everything except archive, restore and hold; Super Admin has all. On the Roles page these sit in one **Citizens** box with rows for Records, Barangay IDs, Birthdays, Households and Tags.

**Status: Complete**

---

### 3a. Pets
**Features**
- **Pets page** (Citizen → Pets): pets of residents shown as photo cards with name, kind and breed, owner and anti-rabies status. Counts per kind at the top (click a kind to show only those), plus Active pets, Pet owners and Anti-rabies due. Search by pet name, breed, color or owner; filter by status (Active, Lost, Deceased) and by anti-rabies due.
- **Add / edit a pet**: pick the owner by searching citizens (or open Add pet from the citizen's profile so the owner is filled in), kind of pet (Dog, Cat, Bird, Chicken / Rooster, Rabbit, Fish, Other — or type a new kind, saved for next time), name, breed, color/markings, sex, birth date, spayed/neutered, last anti-rabies shot, status and notes. **Save & add another** keeps the same owner.
- **Pet photo**: upload or take a photo, positioned in the square frame (drag, zoom, rotate) before saving.
- **Anti-rabies**: shots count as yearly; a pet is shown as due one year after its last shot, or when no shot is recorded.
- **Pet page**: photo, all details, anti-rabies status, the owner (links to the citizen) and the owner's other pets. Remove asks to confirm and deletes the photo; setting the status to Deceased or Lost keeps the record.
- **Citizen profile**: a Pets card with the citizen's pets and an Add pet button.
- Adding, editing and removing pets is written to the Activity Logs. Zone-limited accounts only see pets of owners in their zones.

**Roles:** Pets view / create / edit / delete on the Roles page. At setup, roles that could view, add or edit citizens got the same for pets; delete went to roles that can archive citizens.

**Status: Complete**

---

### 4. Demographics
**Features**
- Population totals: male/female, age groups (children, youth, adults, seniors), PWD and solo parents.
- Voter vs non-voter breakdowns by age group and by zone.
- Number of households and families, average household size, and people not yet assigned to a household.
- Charts per zone and by civil status.

**Roles:** needs the Demographics permission (in the Citizens box on the Roles page).

**Status: Complete**

---

### 5. Households & Families
**Features**
- Create households with address (zone, block, lot, street, house number).
- Add one or more families inside a household and add members to each family.
- **Citizen pickers open ready to pick**: New Household (household head), Add Family (family head) and Add Member list every citizen not yet in a household straight away, 8 per page with pages, with the count shown; typing narrows the list. Citizens already in a family are left out.
- Set or remove the household head, and remove members.
- Household list with search, summary counts and a details view.
- Archive and restore households.
- **Export** households to **CSV (Excel)**.
- **Family income classification (optional, off by default):** when switched on in Settings, staff with the *Family income* permission may record a family's monthly income, or just an estimated class if the amount is unknown. Families are grouped automatically from Poor to Rich (PIDS income clusters: income per family member compared with the poverty threshold set in Settings). Shown on family cards, in Demographics (counts, % poor and a breakdown by zone), as a filter on the citizen list, and in the citizen scan pop-up. Income is never required; families without it show as "Not assessed". Changes are logged without the amount.

**Roles:** view (all roles); add and edit (Staff, Super Admin); archive and restore (Super Admin).

**Status: Complete**

---

### 6. Barangay ID Cards
**Features**
- Generate a Barangay ID for a citizen, with a validity date (validity period is set in Settings).
- Print the ID card (front and back) with the citizen's photo, details and a scannable QR code.
- **ID photo while creating an ID**: on the Confirm Details step, **Take photo** (webcam or phone camera, front/back camera switch) or **Upload photo** (JPG/PNG); either way the photo opens in a square frame with a face guide to drag, zoom, rotate or center it before saving. Photos are resized in the browser before upload, saved as the citizen's profile photo (replacing the old one), and logged in Activity Logs. Shows a warning when the citizen has no photo yet.
- **Sign with pen** on the print page: the citizen signs in a pad on screen with a signature pad, pen tablet (pen pressure gives a natural line), touchscreen or mouse. Black or blue ink, thickness, undo and clear. The signature is saved as a clean transparent image trimmed to the ink and placed on the card, same as an uploaded signature.
- **Print setup** on the print page: *ID card printer* prints each side on its own CR80 card page (3.375 × 2.125 in, no margins); *Plain paper* prints at actual size on A4/Letter with cut lines. Front + back, front only or back only (for single-sided card printers). **Fine-tune fit** moves the card left/right and up/down in mm and adjusts the size in %, remembered on that computer.
- Upload or remove each citizen's signature for the card.
- **ID Template Designer**: upload front and back backgrounds and arrange where each detail appears, with a live preview using a real citizen. Printing follows the saved design exactly.
- Advanced styling options in the designer are limited to the Super Admin.
- Each item on the card (field, text, photo, QR, signature) can be given an **ID** and **Class** in the designer, so the Custom CSS can style that one item (e.g. `#full-name { … }`). Shows live in the designer and on the printed ID.
- **Sector fields**: PWD, Solo parent, Senior citizen (60+) and All sectors. Each prints its label only when it applies to the citizen, otherwise nothing.
- **Show only if**: any item can be limited to citizens who are PWD, a solo parent, a senior or a voter (e.g. a red "PWD" badge). It is hidden on everyone else's printed ID and marked with an amber tag in the designer.

**Roles:** separate Barangay ID permissions: view, generate, print, signature (add or remove), and template (the ID Template designer). Buttons and the sidebar link only appear with the matching permission.

**Status: Complete.** Cards print one citizen at a time; there is no batch printing of many IDs at once.

---

### 7. Birthdays
**Features**
- **Month strip**: all 12 months with the number of celebrants in each (following the filters), quarter buttons (Q1–Q4) and previous / next arrows.
- **Day bar**: under the month strip, pick a single day of that month (e.g. April 20); each day shows its number of celebrants. The list, summary and export follow the chosen day, and previous / next then move one day at a time (Feb 29 included).
- **Summary**: celebrants in the period, today, the next 7 days, milestones, and male / female split.
- **Today** and **Next 7 days** cards (whatever month is selected): photo, the age each person turns, area, a call button, and "+N more" on busy days.
- **Milestones** are marked: turning 18 (can register to vote), 60 (senior citizen) and 80, 85, 90, 95, 100 (Centenarians Act cash gift).
- The list is **grouped by day** (today's group highlighted) and shows **Turns N** with the birth year; names open the citizen profile. It loads 50 at a time with page numbers, and phones get a card view. A column picker (remembered per browser) adds gender, civil status, email, occupation, voter / precinct and emergency contact.
- Filter by voters (voters only by default), PWD, senior citizens, solo parents and tags. Filters apply as soon as they change and show as removable chips.
- **Export** to **CSV (Excel)** or **PDF**, with optional extra columns. The export follows the filters.

**Roles:** separate Birthdays permissions: view and export.

**Status: Complete**

---

### 8. Blotter (Incident Records)
**Features**
- Record incidents: case number, date filed, incident date and time, type, location, narrative, witnesses, attending officer and photos (several at a time).
- **Multiple complainants and respondents** per case, either picked from the citizen records or typed in for non-residents. Parties can be added or changed later.
- Case status: Pending, Ongoing, Scheduled, Settled, Referred, Dismissed or Closed, with settled date and "referred to".
- **Case actions**: hearings, summons, mediation, conciliation, investigation and others, each with schedule, venue, who conducted it, attendance of both sides, notes, photos and outcome. Changes to an action are kept as a version history.
- **Print** the blotter report on the barangay's official paper (letterhead chosen in Settings).
- **Hold a party from the case**: a complainant or respondent linked to a citizen record can be put on hold (or the hold lifted) right on the case page, blocking them from being issued documents. The reason is filled in with the case number and can be edited. The hold shows on the case and in the citizen's Hold History with a link back to the case, and both histories record it.
- Search and filter the case list.

**Roles:** view and print (all roles); add and edit (Staff, Super Admin); delete (Super Admin); placing or lifting a hold needs the citizens *hold* permission (Super Admin by default).

**Status: Complete**

---

### 9. Ordinances & Resolutions
**Features**
- Record the **ordinances and resolutions** passed by the Sangguniang Barangay: number, series year, title, summary, category (Peace & Order, Revenue, Environment and others), authors, co-authors and sponsors.
- The next number is suggested for each type and year, for example 2026-004. The same number can't be used twice for the same type.
- **Legislative steps**: an ordinance goes from Draft through first, second and third reading, then Approved, then In force. A resolution goes from Draft to Adopted to In force. Each step is saved on a **timeline** with its date, remarks and the user who recorded it. Votes (yes, no, abstain) can be entered on final passage.
- **Veto**: an ordinance on third reading can be marked Vetoed.
- **Amends and repeals**: a measure can amend or repeal earlier ones. A repealed measure changes to Repealed only once the new one is approved, and both pages link to each other. If the link is removed or the new measure is deleted, the old one is in force again.
- Posting dates and notes can be added to the timeline.
- **Signed copy**: upload a PDF or photo, view it on the page, or download it. The file is kept private, so only signed-in users who can view ordinances can open it.
- Ordinances and Resolutions tabs, search (by number, title or author), filters by status, year and category, and counts of ordinances, resolutions, measures in force and pending readings.
- **Activity log**: recording, editing (each changed field shown from old to new, including status, dates, votes, authors, amends/repeals and the signed copy), moving to the next step or veto, adding or removing timeline entries, and deleting are all logged. Each ordinance page has a **Change history** card (for users who can see the Activity Logs) with a link to its full history, and Activity Logs has an **Ordinances & Resolutions** filter.

**Roles:** view (all roles); add and edit (Staff, Super Admin); move the steps along, veto and add timeline entries (**Approve** permission, Super Admin); delete (Super Admin). Users without Approve always save new measures as Draft and can't change the status.

**Status: Complete**

---

### 10. Health Check-ups
**Features**
- Record a **check-up** for a citizen (libreng check-up or walk-in consultation). Find the citizen by name or contact number, or scan their Barangay ID. People not in the citizen records can be recorded as **walk-ins** with name, age and sex.
- **Type of check-up** (required): pick from the saved list (General consultation, Blood pressure check, Blood sugar check, Prenatal check-up, Child check-up, Immunization, Senior citizen check-up, Follow-up visit) or type a new type, which is saved for next time (an existing name in any letter case is reused). Shown on the check-up, the printed record and the list.
- **Filter the list** by type of check-up and by date: All dates, Today, 1 week, 1 month, or Custom (From–To), together with search and the High BP / Fever / High sugar / Follow-up filters.
- **Vital signs**: blood pressure, temperature, pulse, respiratory rate, oxygen saturation, blood sugar (fasting or random), weight and height, with **BMI calculated automatically**. Each reading gets a flag as you type, for example *High (stage 2)*, *Fever* or *Overweight*. The flags follow common adult screening guides and are not a diagnosis.
- **Consultation notes**: complaint, findings, advice or treatment, where the patient was referred, and a follow-up date.
- **Medicines from Inventory**: only items in a category marked **Use for medicine** (Inventory → Categories) can be picked. Pick items in stock and enter the quantity and how to take them. Stock is deducted when you save. For a citizen this also creates a released Inventory release, so it appears under Inventory → Releases and in the citizen's history. If there isn't enough stock, nothing is saved. Medicines can't be changed after saving; to fix a mistake, use a stock adjustment in Inventory.
- **Prescription (reseta)** for medicines not available at the station: write each medicine (generic name, strength, form — suggestions come from Inventory medicine items and earlier resetas), the quantity (e.g. #21) and how to take it (Sig). Saved with the check-up and can be changed later; nothing comes out of stock. The prescriber is **Attended by**, with optional PRC licence and PTR numbers (remembered from the staff member's last reseta). Anyone who can add or edit check-ups can write one (no Dispense permission needed).
- **Print reseta**: a prescription slip with the barangay header, patient name, age, sex, address and date, ℞ with the numbered medicines and Sig, follow-up date, and the signature line with licence and PTR numbers. Paper size: half letter (default), A5, letter or A4, remembered on that computer.
- **Save & next patient** button for busy check-up days.
- **Print** a check-up record: patient details, vital signs with their flags, consultation notes, medicines given, and signature lines for the health worker and the patient. It prints on the paper chosen in Settings → Print Paper (or the plain barangay header).
- **Check-up page**: the readings with their flags, the medicines given, and the patient's other check-ups so changes in BP, weight and so on can be compared.
- List with counts for today, this month, high BP this month and follow-ups in the next 7 days (click a count to filter). You can search, filter by date, or show only high BP, fever, high blood sugar or upcoming follow-ups.
- Citizen profile: a **Health** tab with all their check-ups and a "New check-up" button. Check-ups also appear in **All Transactions** on the Citizens page.
- Every change is recorded in the Activity Logs.

**Roles:** health records are private, so the **Viewer role cannot see them**. View, add and edit: Staff and Super Admin. **Dispense** (give medicines from stock): Super Admin. Delete: Super Admin. Deleting a check-up does not put the medicines back in stock.

**Status: Complete**

---

### 11. Documents & Certificates
**Features**
- **Document types** (for example Clearance, Indigency, Residency): name, short code, number prefix, color, fee or free, whether approval is needed, extra fields to fill in, and common purposes. Types can be switched on or off. A sample gallery helps set up new types.
- **Document text** with fill-in placeholders: the citizen's name, age, birthday, address, photo and QR code; the barangay name, city, province, captain's name and signature; and the document number, purpose and official receipt (OR) number. Staff can be allowed to adjust the wording per request.
- **Paper templates** (letterhead backgrounds): paper size, orientation and margins, with saved versions so an older design can be brought back. Each document type picks a template and either **Latest** (always the template's current version, switching automatically when a new version is saved) or a pinned version that stays fixed.
- **Requests**: pick a citizen and a document type, preview it, then save. Citizens on hold are blocked.
- **Walk-ins** (people who are not registered residents): a Walk-in button on the request page saves a minimal record (name, birthday, optional contact and ID presented, with Data Privacy consent) and gives them a reference number (e.g. WI-00125). They are not counted as residents anywhere else. The system reads each document's wording and asks only for the personal details that document prints (e.g. Clearance: birthplace, gender, civil status, address, resident since). Details are kept, so a return visit only asks for what is still missing. Duplicate walk-ins and registered residents are detected. Each document type can allow or refuse walk-ins.
- **Missing-details warning**: when a registered citizen's record lacks something the chosen document prints (e.g. no address), staff are warned before issuing, with a link to update the profile.
- **Scan the Barangay ID to start a request**: scan the QR code on the citizen's ID with a handheld scanner at any time on the page, with no need to click the search box first, even while typing in another field. The citizen is selected instantly, with no need to type or pick from a list. Staff can also search by name, contact number or email, or choose from recently added citizens.
- Request steps: approve, reject, release and print, with a count of how many times each document was printed.
- **Payments**: fee, amount paid, OR number, and clear status (free, unpaid, paid, short).
- **Void** a released document: a reason is required and the record is kept.
- **Fee Summary** on the Requests page: possible income by document type (collected, awaiting release, short) for the current filters, with a printable report.
- Request list with search, date range presets (Today is the default) and an advanced filter. Summary cards follow the filter.
- **Documents dashboard** with daily numbers and totals, color-coded by document type.

**Roles:** view (all roles); create and edit (Staff, Super Admin); fees — the Fee Summary and fees-collected figures (Staff, Super Admin; not Viewer); delete and void (Super Admin, or a custom role with those permissions). Each document type can also be limited to selected roles.

**Status: Complete.** Note: only voiding is written to the activity log. Creating, approving and releasing are not, although each request does store who created and approved it.

---

### 12. Events & Attendance
**Features**
- Create events with title, description, category, venue, date, start and end time, and cover photo. Events close automatically after the end time and can also be opened or closed by hand.
- **Who can attend**: open to all areas, or limited to selected zones/addresses (several can be picked). Optional **extra rules** on top: an age range (with quick picks such as Senior 60+, Adults 18+, Youth 15–30), men or women only, groups (senior citizen, PWD, solo parent, registered voter; "any of" or "all of" them), and citizen tags (any of the chosen tags). Ages are counted on the event date. The rules are enforced everywhere: QR and manual check-in refuse ineligible people with the reason (e.g. "this event is only for Senior or PWD"), the check-in search and bulk add list only eligible citizens, and the raffle draws only from eligible attendees. The event page shows the rules (e.g. "For: Age 60+ · Senior or PWD").
- **Check-in** by QR code or by searching the citizen's name. With a handheld or USB QR scanner, staff can scan the citizen's ID at any time on the event page, with no need to click the QR box first. Each scan shows a ✓ or ✗ message, and the attendance list updates straight away. Codes can also be typed in by hand.
- **Bulk attendance**: add many citizens at once using filters (zone, gender, age, voters, seniors, PWD, solo parents, birthday this month), with a preview before adding.
- Live attendance list, with removal of entries added by mistake.
- Summary numbers on the events list.

**Roles:** view (all roles); create and edit, including check-in (Staff, Super Admin); delete (Super Admin).

**Status: Complete.** Note: the attendance list cannot be exported or printed. Check-in by phone camera is not available.

---

### 13. Raffle / Lucky Draw (part of Events)
**Features**
- Turn on a raffle for any event; everyone checked in is in the draw.
- **One draw screen inside the system**: staff open it with **Open Raffle** on the event page. It needs a login and the edit-events permission. There is no PIN, shareable link or live second screen. To show the draw on a projector, open it on the laptop connected to the projector.
- Animated card carousel with a progress bar; nine cards on wide screens.
- **Presence check**: after each draw, staff confirm the person is present, or mark them not present and the system draws again.
- **Several winners in one draw** (up to 50). Absent people are removed before confirming, and all winners are saved together.
- **Grand prize mode**: gold theme, slower spin and a bigger celebration, one winner.
- **Area filter**: draw only from selected zones.
- Optional prize label per draw; winners cannot win twice unless allowed in the event settings.
- **Undo** a confirmed winner, with a required reason. Not allowed once the prize has been claimed.
- Cheerful sound effects with a mute button.
- **Winners page**: edit prizes one by one or many at once, mark prizes as claimed (records when and by whom), and **export** the winners list to **CSV (Excel)** or **PDF**.

**Roles:** view the winners list (all roles); open the draw screen, run draws and manage winners (Staff, Super Admin, or any role with the edit-events permission).

**Status: Complete.**

---

### 14. Inventory
**Features**
- **Categories** with icon and color. **Use for medicine** marks a category whose items can be given as medicine at a Health check-up.
- **Items**: name, code (SKU), photo, unit, description, stock count, low-stock warning level, whether releasing needs approval, and whether it can be rented. Items running low are flagged.
- **Stock in** (deliveries) with history, and **stock adjustments** for corrections.
- **Releases** (giving items to a citizen, for example relief goods): multiple items per release, purpose, approve or reject, mark as released, and **print a release slip** on the barangay's official paper.
- **Rentals / borrowing** (chairs, tents, equipment): borrow and due dates, rental fee and deposit, payment and deposit refund, release, return (including partial returns), condition when lent and when returned, and cancellation.
- **Schedule (reservation calendar)** for items such as the ambulance, the covered court or a service vehicle. An item ticked "Can be scheduled" appears on a month, week and list calendar, with each item in its own color (chosen on the item from swatches or a custom color, or automatic) and a filter by item. Each item also has **buffer days** (default 3, can be 0): days kept free after every reservation for return, cleaning or checking. The buffer is enforced when booking and shown as striped days on the calendar, and the reservation details show when the item is free again. Staff click a date (or drag across days or hours) to reserve it. A reservation records the item, quantity, start and end date and time, who it is for (a registered citizen, found by search or by scanning their ID, or a typed name), contact number, purpose, destination and notes. The form checks availability live and lists any bookings that clash. Double booking is blocked: overlapping reservations can never exceed the item's stock, and past dates cannot be booked. Reservations can be edited, marked completed, or cancelled with a reason, and these schedule actions are written to the activity log.

**Roles:** permissions are set separately for each area, so a role can, for example, book the ambulance without touching stock:
- **Items**: view, create, edit (details, picture), stock (stock-in and adjustments), delete (only items never used in a release, rental or reservation).
- **Categories**: view, create, edit, delete (only empty categories).
- **Releases**: view, create, approve (approve, reject, mark as released).
- **Rentals**: view, create, edit (release, return, payment, cancel).
- **Schedule**: view, create (book), edit (change, complete, cancel).

The sidebar, buttons and the citizen activity pop-up only show what the role allows. Viewer and Staff can view all five areas; Super Admin has everything.

**Status: Complete.** Note: inventory changes are not written to the activity log.

---

### 15. Settings
**Features**
- **General info**: barangay name, address, municipality, province, contact number, email, barangay and municipal logos, captain's name, position and signature.
- **Citizen ID format**: number prefix and number of digits, and how long Barangay IDs stay valid.
- **Print Paper** (its own page in Settings): choose the paper template from Documents → Paper Templates, and either follow its latest version or pin one, for each printout: Blotter Report, Inventory Release Slip, Document Fee Summary and Health Check-up Record. "None" uses the plain barangay header. Changes are written to the activity log.
- **Citizen Fields**: which citizen details are required or must be unique, and whether the duplicate check uses the middle name (see Citizen Records).
- **Zones & Addresses**: add, edit and switch off zones or subdivisions.
- **Officials**: list of barangay officials with position, description and display order.
- Shortcuts to Users, Roles, Paper Templates and the ID Template Designer.
- **Login History**: button on the Users page, see section 1.

**Roles:** view (all roles); edit (Super Admin). Zones need the address permissions.

**Status: Partially working.** The Officials list can be saved and edited but is not used anywhere else yet (not on documents, IDs or any public page).

---

### 16. Activity Logs (Audit Trail)
**Features**
- Record of actions taken in the system: who did it, when, what kind of record, and details.
- Filter by action, record type (including Ordinances & Resolutions), date range and staff member. Ordinance entries link to the measure.
- **Citizen Activity tab** (citizen view page, History card): every log entry about that citizen — profile edits with each field from old to new, photo, tags, hold, archive, Barangay ID release, and entries for their document requests, health check-ups (if the user can view health) and blotter cases — newest first, grouped by day, with the staff member and time, and links to the record. Latest 200 entries; link to the full Activity Logs.

**Roles:** anyone with the activity log permission (all three default roles can view).

**Status: Partially working.** Logged: citizens (including archive, restore, hold and exports), households, blotter, ordinances and resolutions, health check-ups, print paper, events and raffle, winners, ID generation, birthday exports, tags, officials, paper templates, settings and document voiding. Not logged: document creation, approval and release; inventory; users and roles; zones/addresses; document type changes; ID template changes.


### 17. Across the whole system
- **Toast notifications** (top-right) after every create, update and delete: titled Created / Updated / Deleted / Saved from the message, with a countdown that pauses on hover and a close button. Form validation problems show as one error toast listing them. Background saves that fail (no permission, session expired, too many tries, server error) show an error toast. Duplicate green success boxes on the page are hidden; red error boxes stay.
- **Loading bar** at the top of the page while a page opens, a form saves or a search runs.
- **Hidden from search engines** (robots.txt, noindex headers and meta tags).
- **Subscription plan** set per install in `.env` (`SUBSCRIPTION_PLAN`): **Basic** has every module except Budget; **Pro** includes Budget. On Basic the Budget menu, its box on the Roles page, the Budget landing page choice and the Budget activity-log filter are hidden, and Budget pages answer "not found". Budget permissions already given to roles are kept, so moving to Pro restores them. The current plan shows on Settings → Updates.

---

## In the code but not in README.md

- Document requests and certificates: document types, fees and OR numbers, approval, release, void, paper templates with versions, document dashboard and per-type access control.
- Scanning the citizen's Barangay ID QR code with a handheld scanner to start a document request or check someone in to an event.
- Barangay ID cards with a visual ID template designer.
- Citizen hold, archive and restore, minor registration, and tags.
- Raffle and lucky draw system (presence check, multi-winner, grand prize, area filter, undo, winner claiming and export).
- Event eligibility (areas, age, gender, senior/PWD/solo parent/voter, tags) and bulk attendance.
- Inventory releases with printed slips, rentals with deposits and returns, and a reservation calendar (for example the ambulance) that blocks double booking.
- Users and Roles management with custom roles and a per-user start page.
- Activity dashboard and activity logs.
- Zones and Addresses, and the officials list in Settings.
- Detailed demographics (voters by zone and age group, households and families).
- CSV and PDF exports for citizens, birthdays, households (CSV) and raffle winners.

## In README.md but not in the code

- **Health Monitoring** (medical history, vaccinations, health records): not built.
- **Website Integration** (announcements, downloadable forms, public site): not built.
- **Attendance export** (attendance sheets): not built.
- **Separate QR card page per citizen** (`/citizens/{id}/qr`): not built. The QR code is printed on the Barangay ID card instead.
- **Household socioeconomic fields** (4Ps, indigent): not built as fields. Citizen tags can be used for this.
- **Birthdays**: the birthday greeting card printout is not built.
- **Inventory**: asset depreciation and "assigned to / location" are not built.
- **Blotter**: separate settlement agreement and document generation are not built. Status "Settled" and the printed blotter report exist.
- **Roles** named Barangay Secretary, Health Worker and SK Officer do not exist. They can be created as custom roles.
- **Excel package** (maatwebsite/excel) is not installed. Exports are CSV files, which open in Excel.

## Broken, half-done, or screen-only

| Item | Problem |
|---|---|
| Deactivating a user | The user can still log in. |
| Public sign-up page | Anyone can create an account. It should be turned off or restricted. |
| Two-step login | Switched on in configuration but has no screens; not usable. |
| Officials list | Saved in Settings but not used anywhere. |
| Activity log coverage | Several modules do not record their actions (see module 14). |
| Password reset | Needs the server's email settings before it works. |
| Leftover theme demo pages | Template sample pages (UI kit, patients, projects, calendar and others) are still in the files but not linked or reachable. They can be removed. |
