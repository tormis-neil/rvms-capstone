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
>
> ---
>
> ## ⚠️ READ FIRST — the `manuscript/` folder was removed on 2026-08-27
>
> **Every path below beginning `manuscript/` no longer exists in the working
> tree.** The project lead removed the folder because it held several diverging
> copies of the same material — the data dictionary lived in `erd_model.py`,
> `model.json`, `rvms-erd.sql`, `ERD-MySQL-Workbench-Manual.md`, four `.docx`
> files and this document at once, and they had drifted apart. `README.md` in
> that folder was still asserting the withdrawn `deleted_at` decision on the day
> it was deleted, which is the failure in one line.
>
> **The manuscript-side authority is now exactly two files:**
>
> | File | Holds |
> |---|---|
> | `CLAUDE.md` | the ERD plan and the data dictionary |
> | `skills/rvms-source-of-truth.md` | Chapters 1/3/4 prose, FR-01–FR-22, NFR-01–NFR-05 |
>
> **The official manuscript itself is in Google Docs, held by the project lead.**
> It is not in this repository and never was.
>
> **What this document is still for.** The *findings* below are intact and were
> never the problem — the spelling errors in Figures 2 and 3, the FDD's missing
> `PASSWORD RECOVERY` leaf, the DFD data-store decision, the enumerations to
> reproduce verbatim. Read them as a checklist of what to fix. Ignore the file
> paths and the regeneration commands; the diagrams, the SQL and the FR/NFR
> tables are deliberately **out of scope until the system + manuscript audit**,
> which the lead will schedule once the system is finalised. At that point the
> comparison is three-way — Google Docs against these two repo files against the
> code — and anything needed from the deleted folder can be recovered with
> `git show 3fc5364:manuscript/<file>`.
>
> ---

---

## Status

| Part | Investigation | Application |
|---|---|---|
| A — Diagrams (Figures 2–5) | ✅ done, exact edits below | ⏳ deferred |
| B — ERD (Figure 6) | ✅ scope defined | ⏳ deferred — figure is still a placeholder · **B1 ✅ done 2026-08-27** |
| C — Data dictionary | ✅ schema verified against migrations | ⏳ deferred — the `deleted_at` decision is now SETTLED (reversed, see Part C) |

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

### ✅ B1 — `rvms-erd.sql` was three columns behind the migrations *(found 2026-08-26, FIXED 2026-08-27)*

> **Done.** All three columns were added to `rvms-erd.sql`, and to the per-table
> field tables in `ERD-MySQL-Workbench-Manual.md`, on 2026-08-27. The two stale
> `deleted_at` rows were removed from the manual at the same time.
> `python3 verify.py` now reports **ALL MATCH**. The file is safe to import into
> Workbench. The record below is kept so the fix is traceable.

**Originally: do this BEFORE anyone imports the file into Workbench.** `rvms-erd.sql` is
hand-maintained — nothing generates it — and it is the file the ERD is drawn
from. Three columns were added to the system after it was last touched
(`1e00352`), so a diagram drawn from it today would be wrong in three places:

| Table | Missing column | Added by |
|---|---|---|
| `repair_logs` | `receipt_path` | `59184a1` — proof of an external repair (FR-13) |
| `pm_schedules` | `completion_mileage` | `17b8b52` — odometer at service (FR-14) |
| `pm_schedules` | `completion_receipt_path` | `59184a1` — same document rule as repairs (FR-14) |

Reproduce with `python3 verify.py` in `manuscript/` — it prints the three as
`[DIFF] rvms-erd.sql …`. Note what it does NOT flag: `erd_model.py`, which feeds
`model.json` and the .docx data dictionary, is **already correct**. Only the
Workbench schema drifted, which is why the earlier "all match" reading was true
of the data dictionary and not of this file.

**No diagram consequence.** All three are attributes on entities that already
exist — no new entity, no new relationship, so the drawn shapes and lines are
unaffected. It matters only for the attribute lists inside the boxes, and for
`ERD-MySQL-Workbench-Manual.md`, whose per-table field tables need the same
three rows.

---

## PART C — Data dictionary

### The verified schema

**`backend/database/migrations/` is the authority, not this table.** The
`verify.py` checker that used to prove this automatically went with the
`manuscript/` folder on 2026-08-27; it compared copies of the dictionary that no
longer exist. `CLAUDE.md`'s data dictionary was confirmed to match the
migrations column-for-column on the day the folder was removed, and re-checking
it against the migrations is the first step of the audit task.

**11 domain tables**, manuscript-facing column counts including `id` and
timestamps (the three repo-only `vehicles` columns are excluded, as they must be
from the manuscript):

| Table | Columns | Notes |
|---|---|---|
| `agencies` | 10 | includes `license_expiry_warning_days` (configurable threshold) |
| `users` | 14 | no `deleted_at` — see the decision below |
| `vehicles` | 13 | no `deleted_at`; the database also carries 3 repo-only columns that stay OUT (see below) |
| `inspection_checklist_items` | 6 | no agency scope; does carry timestamps |
| `inspections` | 10 | |
| `inspection_items` | 5 | no timestamps |
| `damage_reports` | 13 | |
| `repair_logs` | 14 | includes `receipt_path` (added 2026-08 — FR-13) |
| `pm_schedules` | 21 | two configurable threshold columns; includes `completion_mileage`, `completion_external_shop_name` and `completion_receipt_path` (all added 2026-08 — FR-14) |
| `dispatches` | 15 | includes `odometer_out`, `odometer_in` |
| `notifications` | 11 | `type` is a **10-value** enum |

### ⚠️ SUPERSEDED (2026-08-27): `deleted_at` is NOT in the manuscript — the columns no longer exist

**This section previously recorded the opposite decision, and following it would
put two columns into the Chapter 4 data dictionary that the database does not
have.** It was written while delete and restore were still a feature. They are
not any more.

On **19 August** the project lead removed delete and restore altogether:
`2026_08_19_000001_drop_soft_deletes_from_vehicles_and_users` dropped both
columns, no model uses `SoftDeletes`, and `RecordsAreNotDeletableTest` asserts
they stay gone. FR-05 and FR-06 were rewritten to match and now read that vehicle
and driver records *"are retained permanently so that the … history referring to
them remains complete."* The reasoning is in
the 19 August revision plan, which was the authority for this change and said
plainly: remove the `deleted_at` row from the `vehicles` and `users` tables. That
plan lived in the now-deleted `manuscript/` folder; its outcome is already
carried by `CLAUDE.md` and the source of truth, and the original is recoverable
with `git show 3fc5364:manuscript/manuscript-revision-plan-2026-08.md`.

The documentation rule itself is unchanged and still correct — *a column is
documented when a functional requirement made it necessary, and left out when it
exists only for the code's own convenience.* What changed is the requirement: no
FR asks for deletion any more, so nothing justifies the column, and the column is
gone regardless.

**What Part C must do:** ensure `users.deleted_at` and `vehicles.deleted_at`
appear **nowhere** in the Chapter 4 data dictionary. They are still present in the
four `.docx` files (see the ⚠️ note at the end of this document) and were removed
from `CLAUDE.md`, `rvms-erd.sql` and `ERD-MySQL-Workbench-Manual.md` on
2026-08-27.

**The ERD needs no change** — `deleted_at` was never drawn on it.

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
- [x] **B1** — ✅ *done 2026-08-27* — three columns added to `rvms-erd.sql` and
      `ERD-MySQL-Workbench-Manual.md`; two stale `deleted_at` rows removed from the
      manual; `python3 verify.py` reports ALL MATCH
- [ ] **B** — draw the ERD; replace the `ERD heree……….` placeholder
- [ ] **C** — build the data dictionary from `CLAUDE.md`; **exclude** `deleted_at`
      on `users` and `vehicles` — the columns were dropped on 2026-08-19 (see Part C)
- [ ] **C** — confirm the three excluded columns are absent
- [ ] **C** — check `notifications.type` lists all ten values
- [ ] Apply the capstone instructor's format update once received

---

## ⚠️ THE FOUR `.docx` FILES ARE STALE — verified 2026-08-27

Every markdown and machine-readable source in the repo now matches the
migrations. **The Word files do not.** They were last built at `1e00352`, before
three schema changes landed, so they are the only remaining place describing a
system that no longer exists.

| File | Has `deleted_at` (dropped 2026-08-19) | Missing `receipt_path` / `completion_mileage` / `completion_receipt_path` |
|---|---|---|
| `RVMS-Chapter4-Data-Dictionary.docx` | — | ✗ all three |
| `RVMS-Chapter4-Draft-with-Data-Dictionary.docx` | ✗ 2 rows | ✗ all three |
| `RVMS-Chapter4-ERD-and-Data-Dictionary.docx` | ✗ 2 rows | ✗ all three |
| `RVMS-ERD-MySQL-Workbench-Manual.docx` | ✗ 2 rows | ✗ all three |

Reproduce with:

```bash
cd manuscript && python3 -c "
import zipfile,re
for f in ['RVMS-Chapter4-Data-Dictionary.docx','RVMS-Chapter4-Draft-with-Data-Dictionary.docx',
          'RVMS-Chapter4-ERD-and-Data-Dictionary.docx','RVMS-ERD-MySQL-Workbench-Manual.docx']:
    t=re.sub(r'<[^>]+>','',zipfile.ZipFile(f).read('word/document.xml').decode('utf8'))
    print(f, {k:t.count(k) for k in ['deleted_at','receipt_path','completion_mileage','completion_receipt_path']})
"
```

**Why they were left alone rather than rebuilt.** `model.json`, which the build
scripts read, is already correct — so `node build_dd_docx.js` would emit a
correct data dictionary. But `RVMS-Chapter4-Draft-with-Data-Dictionary.docx`
carries hand-written Chapter 4 narrative spliced in by `merge_dd_into_ch4.py`,
and rebuilding it risks losing edits made in Word that exist nowhere else.
Chapter 4 is also the part this document is explicitly gated on: the capstone
instructor's format update may change how these tables must be laid out, and
rebuilding them twice is wasted work.

**So this is Part C's job, done in one pass once the format update arrives** —
either by regenerating from `model.json` (fast, but re-check the narrative
survived) or by editing the six affected table rows in Word by hand (slower,
safer). Whichever route, the target is the same and is now unambiguous
everywhere else in the repo.

---

## Sources

- `skills/rvms-source-of-truth.md` — FR/NFR wording, Ch1 and Ch3 text
- `CLAUDE.md` — ERD plan, data dictionary, design decisions 7–10
- `backend/database/migrations/` — the schema itself, which outranks both
- *(removed 2026-08-27)* `manuscript/` — `verify.py`, `erd_model.py`,
  `rvms-erd.sql`, the figures and the `.docx` builds. Recover any of them with
  `git show 3fc5364:manuscript/<file>` if the audit task needs them.
