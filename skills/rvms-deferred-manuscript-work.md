# Deferred Manuscript Work — Diagrams, ERD, Data Dictionary

> **What this is.** Everything the 2026-08 manuscript↔system audit found in the
> figures and the data model, written down so it can be executed later without
> re-doing the investigation. Deliberately separated from the rest of the audit
> because those findings are sentences to add in Word, while these are diagram
> edits and a table build — a different kind of work, on a different schedule.
>
> **When to do it.** After testing and troubleshooting, per the project lead's
> sequencing (2026-08). Also gated on the capstone instructor's pending format
> update, which may change how figures and tables must be presented.
>
> **Why it is written now.** Finding these needed the code, the .drawio and the
> .docx side by side. Applying them needs Word and diagrams.net. Doing the
> finding early and the applying late is the cheaper order, and it means the
> later session starts from a checklist rather than from scratch.

---

## Status

| Part | Investigation | Application |
|---|---|---|
| A — Diagrams (Figures 2–5) | ✅ done, exact edits below | ⏳ deferred |
| B — ERD (Figure 6) | ✅ scope defined | ⏳ deferred — figure is still a placeholder |
| C — Data dictionary | ✅ schema verified against migrations | ⏳ deferred — one decision needed first |

**Blocked on:** the capstone instructor's format update. It should not change any
of the *content* below — only how it is laid out — so nothing here needs to wait
for it except the final formatting pass.

---

## PART A — Diagram changes

Source file: `diagrams.drawio.xml` (4 pages: system architecture, context
diagram, data flow diagram, functional decomp diagram).

> ⚠️ **Every change here needs TWO steps:** edit the `.drawio`, then **re-export
> the figure and re-paste it into the .docx**. The manuscript holds flattened
> images, so editing the source alone changes nothing a reader sees. This is the
> easiest step to forget and the only one that makes the work invisible.

### A1 — Figure 2, System Architecture: spelling

| Current | Correct |
|---|---|
| `TRIGGGER NOTIFICATIONS` | `TRIGGER NOTIFICATIONS` |

Three G's. On the arrow between the backend and Firebase Cloud Messaging.

### A2 — Figure 3, Context Diagram: spelling

| Current | Correct |
|---|---|
| `Vehicle & Driver Recods` | `Vehicle & Driver Records` |

On the Agency Administrator → system data flow.

### A3 — Figure 5, FDD: add PASSWORD RECOVERY

`USER MANAGEMENT` currently decomposes into five leaves:

```
USER MANAGEMENT
 ├── USER LOGIN / AUTHENTICATION     (FR-01)
 ├── DRIVER REGISTRATION             (FR-03)
 ├── ACCESS REQUEST APPROVAL         (FR-03)
 ├── PROFILE MANAGEMENT              (FR-04)
 └── ROLE & AGENCY ACCESS CONTROL    (FR-02)
```

**FR-22 has no leaf.** The FDD claims to be a complete decomposition, so a whole
requirement missing from it is a real gap rather than a cosmetic one.

Add a sixth box in the same column (x ≈ -490), below `ROLE & AGENCY ACCESS
CONTROL` (y ≈ 280):

```
PASSWORD RECOVERY
```

### A4 — Figure 5, FDD: notification list management

`NOTIFICATION MANAGEMENT` currently has two leaves:

```
NOTIFICATION MANAGEMENT
 ├── SEND IN-APP NOTIFICATION
 └── SEND NOTIFICATION VIA FCM
```

FR-21 also grants administrators the ability to **mark notifications as read**
and **clear ones already read**. Both are implemented, tested, and promised in
Chapter 1 — neither appears in the FDD.

Add one box (or two, if the column has room):

```
MANAGE NOTIFICATION LIST
```
or
```
MARK AS READ
CLEAR READ NOTIFICATIONS
```

### A5 — Figure 4, DFD: data stores — DECISION, no change recommended

The DFD has 8 data stores; the database has 11 domain tables.

| DFD store | Table |
|---|---|
| D1 USERS | `users` |
| D2 VEHICLES | `vehicles` |
| D3 INSPECTIONS | `inspections` |
| D4 DAMAGE REPORTS | `damage_reports` |
| D5 REPAIR LOGS | `repair_logs` |
| D6 PM SCHEDULES | `pm_schedules` |
| D7 DISPATCHES | `dispatches` |
| D8 NOTIFICATIONS | `notifications` |
| — | `agencies` |
| — | `inspection_items` |
| — | `inspection_checklist_items` |

`inspection_items` and `inspection_checklist_items` are correctly absent: a DFD
models logical stores, and folding line items into D3 with a reference catalogue
left out is standard practice.

`agencies` is the arguable one, because FR-02 makes agency scoping a headline
property and the ERD puts `agencies` at the root of the model.

**Recommendation: leave the DFD at 8 stores.** Adding a D9 would require flows
from all eight processes into it, cluttering the diagram to depict something that
is an attribute rather than a process input. If asked at the defense:

> *"Agency is a scoping attribute carried on every record rather than a store
> that processes read from and write to, so it appears in the ERD rather than the
> DFD."*

Recorded here so the omission is a decision on the record, not an oversight.

### A6 — Balance check (do this while the diagrams are open)

Not a known defect — a verification worth performing once, because it is the
check panels most enjoy making:

- every data flow crossing the context-diagram boundary in Figure 3 must also
  appear in Figure 4 (Diagram 0), and vice versa;
- every process in Figure 4 should map to a function branch in Figure 5.

Figure 4's eight processes and Figure 5's eight top-level functions already
correspond one-to-one:

| DFD process | FDD branch |
|---|---|
| 1 Authenticate & Manage Account | User Management |
| 2 Manage Vehicle & Driver Record | Vehicle & Driver Record Management |
| 3 Process Inspection | Inspection Management |
| 4 Manage Damage & Repair | Damage & Repair Management |
| 5 Manage Preventive Maintenance | Preventive Maintenance Management |
| 6 Manage Dispatch | Dispatch Management |
| 7 Generate Dashboard & Report | Dashboard & Report Generation |
| 8 Send Notification | Notification Management |

---

## PART B — ERD (Figure 6)

The .docx currently contains the literal placeholder text `ERD heree……….`
above the Figure 6 caption. The surrounding narrative paragraph is written; only
the figure is missing.

**Entities to draw (11):** `agencies` · `users` · `vehicles` ·
`inspection_checklist_items` · `inspections` · `inspection_items` ·
`damage_reports` · `repair_logs` · `pm_schedules` · `dispatches` ·
`notifications`

**Relationships** — the authority is the ERD PLAN section of `CLAUDE.md`, which
matches the migrations. Points that are easy to draw wrongly:

- `agencies` is one-to-many to **every** other domain table except
  `inspection_checklist_items` and `inspection_items` (the catalogue is global;
  items inherit scope through their inspection).
- `users` appears in **four different roles** and each needs its own line:
  driver on `inspections` / `damage_reports` / `repair_logs` / `dispatches`
  (`driver_id`), reviewing admin on `inspections` / `damage_reports`
  (`reviewed_by`), assigned driver on `vehicles` (`assigned_driver_id`), and
  recipient on `notifications` (`user_id`).
- A driver may be the primary driver of **more than one** vehicle; each vehicle
  has **at most one** primary driver (Ch4 ERD, confirmed in design decision 7).
- `inspection_items` is the join between `inspections` and
  `inspection_checklist_items`.

**Framework tables are not drawn:** `personal_access_tokens`,
`password_reset_tokens`, `jobs`, `failed_jobs`, `cache`, `sessions`.

---

## PART C — Data dictionary

### The verified schema

The data dictionary in `CLAUDE.md` was checked column-by-column against the
migrations during the 2026-08 audit and matches, with the one open decision
below. **11 domain tables**, column counts including `id` and timestamps:

| Table | Columns | Notes |
|---|---|---|
| `agencies` | 10 | includes `license_expiry_warning_days` (configurable threshold) |
| `users` | 15 | includes `deleted_at` — see decision below |
| `vehicles` | 17 | includes `remarks`, `status_source`, `status_changed_at`, `deleted_at` |
| `inspection_checklist_items` | 4 | no timestamps, no agency scope |
| `inspections` | 9 | |
| `inspection_items` | 5 | no timestamps |
| `damage_reports` | 12 | |
| `repair_logs` | 12 | |
| `pm_schedules` | 17 | two configurable threshold columns |
| `dispatches` | 14 | includes `odometer_out`, `odometer_in` |
| `notifications` | 10 | `type` is a **10-value** enum |

### ✅ DECIDED (2026-08, project lead): `deleted_at` IS in the manuscript

`users.deleted_at` and `vehicles.deleted_at` are documented in the Chapter 4
data dictionary as ordinary columns.

The rule this follows: **a column is documented when a functional requirement
made it necessary, and left out when it exists only for the code's own
convenience.** FR-05 and FR-06 state that administrators may delete and restore
vehicle and driver records; that is impossible without a column marking which
records are deleted, so the requirement is what put it there. Identical
reasoning to `dispatches.odometer_out` / `odometer_in` (design decision 8),
which are in the manuscript because FR-15 and FR-16 name odometer readings — and
the opposite of the three columns below, which no requirement asked for.

Both are `TIMESTAMP`, nullable, default NULL. Suggested descriptions:

| Table | Column | Description |
|---|---|---|
| `users` | `deleted_at` | Set when an Agency Administrator deletes the driver; the record leaves every list, selection and login while the inspections and damage reports they submitted are retained and still identify them. Cleared on restore. |
| `vehicles` | `deleted_at` | Set when an Agency Administrator deletes the vehicle; it leaves every list and selection while its inspections, damage reports, repairs, preventive maintenance schedules and dispatches are retained and still resolve its plate number. Cleared on restore. |

### Columns deliberately EXCLUDED from the manuscript

These three are repo/code only and must **not** appear in the Chapter 4 data
dictionary. No functional requirement backs any of them, and the exclusion is a
recorded decision, not an oversight:

| Column | Why it exists | Decision |
|---|---|---|
| `vehicles.remarks` | Optional note on the most recent manual status change; the prototype's Update Status modal already had the field | 7 (amendment) |
| `vehicles.status_source` | Which module last wrote the status; powers the confirmation dialog | 9 |
| `vehicles.status_changed_at` | When the status was last written; paired with the above | 9 |

The ERD is unchanged by all three — they are attributes on an existing entity.

### Enumerations to reproduce exactly

Every one of these was verified against its model constant during the audit and
matches. Copy them verbatim; a paraphrase here is a defect.

| Column | Values |
|---|---|
| `vehicles.status` | Operational · Dispatched · Not Operational · Under Preventive Maintenance |
| `dispatches.mission_type` | Fire Response · Medical Response · Rescue Operation · Patrol · Administrative Travel · Others |
| `dispatches.return_status` | Operational · Not Operational · Under Preventive Maintenance *(no Dispatched — FR-16)* |
| `repair_logs.repair_source` | Internal Office · GSO Motorpool · External Repair Shop |
| `pm_schedules.pm_type` | Mileage-Based · Time-Based |
| `pm_schedules.status` | Upcoming · Due Soon · Due · Completed |
| `inspections.review_status` | Pending · Reviewed |
| `inspection_items.status` | OK · Has Issue |
| `damage_reports.status` | Pending · Reviewed |
| `users.role` | admin · driver |
| `users.status` | pending · active · rejected |
| `notifications.type` | PM_Reminder · Vehicle_Status_Update · New_Damage_Report · Inspection_Flagged · License_Expiring · License_Expired · PM_Due_Soon · PM_Due · New_Access_Request · **Password_Reset** |

`notifications.type` is the one most likely to be stale in any older draft — it
grew from 8 to 9 (`Inspection_Flagged`, 2026-07) to 10 (`Password_Reset`,
2026-08).

---

## Execution checklist

Work top to bottom; each line is independently completable.

- [ ] **A1** — `TRIGGGER` → `TRIGGER` (Figure 2)
- [ ] **A2** — `Recods` → `Records` (Figure 3)
- [ ] **A3** — add `PASSWORD RECOVERY` under USER MANAGEMENT (Figure 5)
- [ ] **A4** — add notification list management leaf (Figure 5)
- [ ] **A5** — no change; confirm the reasoning is understood for the defense
- [ ] **A6** — run the context↔DFD balance check
- [ ] **Re-export Figures 2, 3 and 5** and re-paste into the .docx ⚠️
- [ ] **B** — draw the ERD; replace the `ERD heree……….` placeholder
- [ ] **C** — build the data dictionary from `CLAUDE.md`; include `deleted_at` on
      `users` and `vehicles` (decided — see Part C)
- [ ] **C** — confirm the three excluded columns are absent
- [ ] **C** — check `notifications.type` lists all ten values
- [ ] Apply the capstone instructor's format update once received

## Sources

- `skills/rvms-source-of-truth.md` — FR/NFR wording, Ch1 and Ch3 text
- `CLAUDE.md` — ERD plan, data dictionary, design decisions 7–10
- `backend/database/migrations/` — the schema itself, which outranks both
