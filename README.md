# 🏛️ Barangay Management System (BMS)
> A comprehensive digital platform for barangay operations, citizen services, and local governance.

---

## 📌 Project Overview

**Barangay Management System** is a full-featured web application built to digitize and streamline day-to-day barangay operations — from citizen records and household profiling to health monitoring, blotter management, and budget compliance.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | **Laravel** (PHP) |
| Frontend | **Blade Templates** + **jQuery** |
| CSS Framework | **Tailwind CSS** |
| UI Theme | **Konrix** — Laravel Tailwind Admin Dashboard |
| Database | **MySQL** |
| QR Generation | Laravel QR package (e.g. `simplesoftwareio/simple-qrcode`) |

**Theme Reference:**
https://preview.codecanyon.net/item/konrix-laravel-tailwind-admin-dashboard-template/full_screen_preview/47148458

---

## 📁 Project Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── DashboardController.php
│       ├── CitizenController.php
│       ├── HouseholdController.php
│       ├── BlotterController.php
│       ├── HealthController.php
│       ├── InventoryController.php
│       ├── AttendanceController.php
│       ├── BudgetController.php
│       └── WebsiteController.php
├── Models/
│   ├── Citizen.php
│   ├── Household.php
│   ├── BlotterRecord.php
│   ├── HealthRecord.php
│   ├── InventoryItem.php
│   ├── AttendanceLog.php
│   └── Budget.php

resources/
└── views/
    ├── layouts/
    │   └── vertical.blade.php        ← Konrix base layout (DO NOT EDIT)
    ├── dashboard/
    │   └── index.blade.php           ← Demographics overview
    ├── citizens/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   ├── edit.blade.php
    │   └── show.blade.php            ← Includes QR code
    ├── households/
    ├── blotter/
    ├── health/
    ├── inventory/
    ├── attendance/
    ├── budget/
    └── website/

routes/
└── web.php                           ← All module routes defined here
```

---

## 🧩 Modules

### 1. 📊 Demographics Dashboard
**Route:** `/dashboard`
**Purpose:** Visual overview of barangay population data.
- Total citizens, households, male/female ratio
- Age group breakdown (charts)
- Senior citizens, PWDs, youth counts
- Charts powered by **Chart.js** via Konrix

---

### 2. 👤 Citizen Management
**Route:** `/citizens`
**Purpose:** Core registry of all barangay residents.
- Full CRUD (Create, Read, Update, Delete)
- Fields: name, birthdate, address, civil status, contact, ID photo, voter status, PWD, senior
- Search, filter, paginate
- Every citizen record auto-generates a unique QR code

> ⚠️ **This is the core module. Build this first — all other modules reference Citizens.**

---

### 3. 📱 QR Codes for Citizens/Residents
**Route:** `/citizens/{id}/qr`
**Purpose:** Generate and print QR codes linked to citizen profiles.
- QR encodes citizen ID or profile URL
- Printable QR card with name, photo, and barangay info
- Package: `simplesoftwareio/simple-qrcode`

---

### 4. 🏠 Household Profiling
**Route:** `/households`
**Purpose:** Group citizens into household units.
- Household head, address, number of members
- Link multiple citizens to one household
- Socioeconomic status tags (4Ps, indigent, etc.)

---

### 5. 🏥 Health Monitoring
**Route:** `/health`
**Purpose:** Track citizen health records and medical needs.
- Medical history per citizen
- Vaccination records
- PWD registry
- Senior citizen health monitoring
- Filter by condition, status, age

---

### 6. 📋 Blotter Management
**Route:** `/blotter`
**Purpose:** Record and manage incident/complaint logs.
- Incident date, type, complainant, respondent
- Status tracking (pending, resolved, dismissed),
- Hearing History   
- Narrative / remarks
- Print blotter report
- Settlement Agreement
- Document Generation

---

### 7. 📦 Inventory & Asset Tracking
**Route:** `/inventory`
**Purpose:** Track barangay-owned equipment and supplies.
- Item name, category, quantity, condition
- Assigned to / location
- Low stock alerts
- Asset depreciation tracking

---

### 8. 🗓️ Attendance Module
**Route:** `/attendance`
**Purpose:** Log attendance for barangay events, meetings, programs.
- Create events/sessions
- Mark attendance per citizen
- Export attendance sheets
- Integration with QR scan-in (optional phase 2)

---

### 9. 🎂 Birthday Features
**Route:** `/birthdays`
**Purpose:** Birthday tracking and notification for citizens.
- Today's / upcoming birthdays (7-day lookahead)
- Filter by senior citizens (60+)
- Birthday greeting card printout (optional)

---

### 10. 💰 Budgeting & Compliance Module
**Route:** `/budget`
**Purpose:** Monitor barangay fund allocation and spending.
Budgeting Features

✅ Annual Budget Planning

Create yearly budget per fund
Allocate budget per program/project
Track approved vs actual spending

✅ Expense Monitoring

Record disbursements
Attach receipts and supporting documents
Track remaining balances automatically

✅ Project Budget Tracking

Infrastructure projects
Health programs
Senior citizen activities
Youth and sports programs
Disaster preparedness projects

✅ Fund Management

General Fund
SK Fund
Disaster Risk Reduction and Management Fund (DRRMF)
Gender and Development (GAD) Fund
Compliance Features

✅ COA Compliance

Maintain digital audit trail
Track who created, modified, and approved records
Store supporting documents

✅ Budget Utilization Reports

Monthly reports
Quarterly reports
Annual reports

✅ Transparency Board

Publish approved projects
Show budget allocations
Show project status
Citizen view portal

✅ Document Repository

Resolutions
Ordinances
Financial reports
Procurement documents

✅ Compliance Reminders

Budget submission deadlines
Liquidation deadlines
Report filing deadlines
Dashboard

Show:
Total Budget
Total Spent
Remaining Budget
Budget Utilization %
Ongoing Projects
Upcoming Compliance Deadlines
Premium Feature Idea



---

### 11. 🌐 Website Integration
**Route:** `/website`
**Purpose:** Manage public-facing barangay website content.
- Announcements / news posts
- Officials list
- Downloadable forms
- Contact/location info

---

## 🗃️ Database — Key Tables

```sql
citizens          -- Core citizen registry
households        -- Household groupings
household_members -- Pivot: citizen ↔ household
blotter_records   -- Incident logs
health_records    -- Health tracking per citizen
inventory_items   -- Assets and supplies
attendance_events -- Events / sessions
attendance_logs   -- Citizen attendance per event
budgets           -- Budget allocations
expenses          -- Expense entries
posts             -- Website content/announcements
users             -- Admin accounts
```

---

## 🧱 Blade Layout Convention

All views extend the Konrix vertical layout:

```blade
@extends('layouts.vertical', [
    'title' => 'Page Title',
    'sub_title' => 'Module Name',
    'mode' => $mode ?? '',
    'demo' => $demo ?? ''
])

@section('content')
    <div class="grid grid-cols-12">
        <div class="col-span-12">
            {{-- Your content here --}}
        </div>
    </div>
@endsection

@section('script')
    <script>
    window.addEventListener('DOMContentLoaded', () => {
        $(document).ready(function () {
            // jQuery logic here
        });
    });
    </script>
@endsection
```

> ✅ Always include `@section('script')` at the bottom of every blade file for jQuery. Never use inline `<script>` tags outside this section.

---

## 🔁 Module Build Order (Recommended)

Build in this sequence to respect data dependencies:

```
1. Citizens          ← foundation
2. Households        ← depends on citizens
3. Dashboard         ← reads citizens + households
4. QR Codes          ← generated from citizens
5. Health Monitoring ← linked to citizens
6. Blotter           ← references citizens
7. Attendance        ← references citizens + events
8. Birthday Features ← reads citizens.birthdate
9. Inventory         ← standalone
10. Budgeting        ← standalone
11. Website          ← standalone
```

## 📦 Key Composer Packages

```bash
composer require simplesoftwareio/simple-qrcode   # QR Code generation
composer require barryvdh/laravel-dompdf           # PDF export (certificates, reports)
composer require maatwebsite/excel                 # Excel export (attendance, reports)
```

---

## 🧠 Claude Code Instructions (for AI-assisted development)

When using **Claude Code in VS Code**, prefix requests with module context:

```
"In the Citizens module, add a soft delete feature with a restore option.
 Use the existing CitizenController and citizens blade views.
 Follow the Konrix Blade layout pattern in starter.blade.php."
```

**Useful prompt patterns:**
- `"Generate migration + model + controller + resource routes for [module]"`
- `"Add search and filter to the [module] index blade using jQuery + AJAX"`
- `"Create a printable QR card blade for a citizen record"`
- `"Add Chart.js demographics chart to dashboard/index.blade.php"`

---

## 👥 User Roles (Phase 2)

| Role | Access |
|------|--------|
| Super Admin | Full access |
| Barangay Secretary | Citizens, Blotter, Certificates |
| Health Worker | Health Monitoring only |
| Treasurer | Budget & Compliance |
| SK Officer | Attendance, Events |

---

## 📄 License
Internal use — Barangay Government Unit. Not for redistribution.