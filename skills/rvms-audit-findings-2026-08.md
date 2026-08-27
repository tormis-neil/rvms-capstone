# Manuscript ↔ System Audit — Findings and Disposition (2026-08)

> **Result: 0 system bugs.** Every capability the manuscript claims, the code
> implements. Every enumerated list matches the code word for word. All findings
> below are documentation that fell behind the code, concentrated in Chapter 1.
>
> **What was audited.** Chapters 1–4 of the .docx manuscript against the repo's
> source of truth, the four diagrams (.drawio), the migrations, the routes, the
> controllers, the Blade pages, the mobile screens, and the test suite.
>
> **Method.** Two directions. *Derivation* — does Chapter 4 follow from Chapters
> 1–3? *Implementation* — does the code match Chapter 4? Organised around the
> four objectives, per the capstone adviser's guidance ("follow the objectives"),
> so every finding traces to something the study set out to do.

---

## Scoreboard

| Area | Findings | System bugs |
|---|---|---|
| Chapter 1 — Introduction | 8 | 0 |
| Chapter 2 — RRL/RRS | 1 (optional) | 0 |
| Chapter 3 — Technical Background | 2 (optional) | 0 |
| Chapter 4 — Methodology | 6 | 0 |
| Diagrams (Figures 2–5) | 4 | 0 |
| Repo source of truth | 2 — **already fixed** | 0 |
| **Total** | **23** | **0** |

**Verified clean, no action:** all 22 FRs implemented · all 5 NFRs · 15 of 15
enumeration groups · 6 of 8 Chapter 3 version claims · every field named in
FR-05/FR-06 exists as a column · Figures 4 and 5 balance one-to-one on their
eight processes/functions.

---

## PART 1 — CHAPTER 1 (Introduction)

Chapter 1 is where nearly everything landed. Three capabilities were added to
the system after Chapter 1 was written and never made it back into it — the
exact inversion the project lead identified: **Chapter 4 must derive from
Chapter 1, not run ahead of it.**

### 1.1 — Three capabilities missing from Scope ¶1 🔴

`delete`, `remove`, `restore` → **0 matches** in all of Chapter 1.
`profile`, `own name`, `own account` → **0 matches**.
`password` → **1 match**, and only inside the Limitations sentence.

Yet FR-04 (self-service profile), FR-05/FR-06 and FR-22 (password recovery) are
all fully implemented and tested.

> ### ⚠️ REVISED 2026-08-27 — do NOT paste the three sentences below
>
> **Two of the three are now false.** They were written before the 19 August
> decisions, and the repo's Chapter 1 has since been rewritten to match the
> system:
>
> - **Sentence 1 (remove and restore records) — WITHDRAWN.** Delete and restore
>   no longer exist. Chapter 1 now states the opposite: records *"may be added
>   and updated but not deleted."*
> - **Sentence 2 (self-service profile) — still correct**, and already present in
>   the repo's Chapter 1.
> - **Sentence 3 (password recovery) — WRONG as written.** It says an
>   administrator may reset the password of *"a fellow Agency Administrator."*
>   They may not, deliberately: FR-22 and Chapter 1 both state that *"an Agency
>   Administrator cannot reset the password of another Agency Administrator"* —
>   such an administrator is recovered by a command run on the server.
>
> **What to do instead:** replace the .docx Chapter 1 *Scope and Limitations*
> section wholesale with the current text from
> `skills/rvms-source-of-truth.md`. It already carries the profile, password and
> no-deletion material in the correct wording, so there is nothing to compose —
> this is a copy, and it resolves 1.1, 1.3 and 1.4 in one pass.

*Original replacement text, kept only so the withdrawal is traceable:*

> ~~Agency Administrators will also be able to remove vehicle and driver records
> that are no longer in service and to restore them if removed in error…~~
> **(withdrawn — the feature was removed)**

> Authorized Drivers and Agency Administrators will also be able to update their
> own name, email address, and password without administrator approval.
> **(still correct)**

> ~~Agency Administrators will also be able to set a new password for an
> Authorized Driver of their agency and for a fellow Agency Administrator…~~
> **(withdrawn — administrators cannot reset each other's passwords)**

### 1.2 — Typos 🟠

| # | Section | Find | Replace |
|---|---|---|---|
| a | Objectives | `Specifically;` | `Specifically:` |
| b | Limitations | `will not send electronic main` | `will not send electronic mail` |
| c | Limitations | `a recovery common run on the server` | `a recovery command run on the server` |
| d | Limitations | `such as the BFPs "Serviceable"` | `such as the BFP's "Serviceable"` |

(b) and (c) are the ones to fix first — they are in a sentence about password
recovery that a panel is likely to read closely.

### 1.3 — The GPS/fuel-consumption sentence 🟠

Three problems in one place: the exclusion list closes at "or" and then has
", and fuel consumption monitoring" tacked on after it; "centered" should be
"entered"; and one sentence was split into two, losing the semicolon that made
the second half explain the first.

**DELETE both of these sentences:**

> The system will not include GPS tracking, GIS mapping, route navigation or
> optimization, IoT-based vehicle diagnostics, telematics integration, or
> automated dispatch recommendation features, and fuel consumption monitoring.
> Odometer readings are centered manually by the Agency Administrator from the
> vehicle's own odometer and are not captured automatically through any device
> integration.

**TYPE this single sentence in their place:**

> The system will not include GPS tracking, GIS mapping, route navigation, route
> optimization, IoT-based vehicle diagnostics, telematics integration, fuel
> consumption monitoring, or automated dispatch recommendation features;
> odometer readings are entered manually by the Agency Administrator from the
> vehicle's own odometer and are not captured automatically through any device
> integration.

### 1.4 — Stray conjunction in the dispatch scope sentence 🟡

| Find | Replace |
|---|---|
| `Administrative Travel, or Others), location, and date and time out, and an optional` | `Administrative Travel, or Others), location, date and time out, and an optional` |

### 1.5 — RVMS acronym never introduced 🟡

Chapter 1 writes the full name every time, so the abbreviation used later is
never defined.

| Find | Replace |
|---|---|
| `the development of the Rescue Vehicle Management System, a two-platform` | `the development of the Rescue Vehicle Management System (RVMS), a two-platform` |

---

## PART 2 — CHAPTER 2 (RRL/RRS)

### 2.1 — BLOWBAGETS never named ⚪ optional

`BLOWBAGETS` appears **0 times** in Chapter 2, though FR-09 is built entirely
around it and it is the system's most distinctive feature.

**This is defensible as written.** BLOWBAGETS is a local Philippine practice
rather than something in international fleet-management literature, and Chapter
2's closing paragraph already carries the argument: *"the reviewed systems do not
adequately address the specific… inspection procedures… observed among rescue
vehicle agencies in Calbayog City."*

**Optional strengthening** — one sentence in that closing paragraph:

> In particular, none of the reviewed systems digitize a standardized
> pre-operation safety checklist such as the BLOWBAGETS protocol already
> practiced by the Bureau of Fire Protection.

Chapter 2 is otherwise sound: it names the gap and lists exactly the modules
built, and its remarks on costly telematics hardware justify the Chapter 1
exclusions of GPS/telematics.

---

## PART 3 — CHAPTER 3 (Technical Background)

**Verified correct, no action:**

| Claim | Actual | |
|---|---|---|
| Laravel 11 (PHP 8.2+) | `laravel/framework ^11.31`, PHP 8.5.5 | ✅ |
| MySQL 8.0+ | MySQL Community Server **8.0.43** | ✅ |
| Bootstrap 5.3+ | `bootstrap@5.3.3` | ✅ |
| Android 8.0 (Oreo)+ | `minSdk = 26` | ✅ NFR-05 proven |
| Firebase Android SDK 32.0.0+ | Firebase BOM 34.4.0 | ✅ |

**XAMPP is correctly absent** from Chapter 3 (0 mentions). It is an installation
bundle, not a technology the system depends on, so migrating from it to
standalone PHP + MySQL 8 (2026-08) requires no manuscript change.

### 3.1 — Two version rows are years out of date ⚪ optional

Neither is false — both say "+" — but they understate the project.

| Find | Replace |
|---|---|
| `Kotlin 1.9+` | `Kotlin 2.x (2.3.20)` |
| `Compose BOM 2024.09.00+` | `Compose BOM 2026.03.01` |

---

## PART 4 — CHAPTER 4 (Methodology)

### 4.1 — A whole paragraph is missing 🔴

The repo has a paragraph mapping the Prototyping Model onto the Chapter 3
Schedule of Activities; the .docx does not. It is what ties the methodology back
to the Gantt chart.

**WHERE:** after the paragraph ending *"…reducing the risk of implementing
features that do not align with actual agency operations."*, before the
**Requirements Analysis** heading.

**INSERT:**

> Following this model, the development of the Rescue Vehicle Management System
> was organized into the phases reflected in the project's Schedule of Activities
> presented in Chapter 3. The Planning and Data Gathering phase corresponds to
> the requirements analysis phase, during which the proponents conducted
> interviews, reviewed existing records and forms, and analyzed the operational
> workflows of the participating agencies. The Prototype Design, Evaluation, and
> Refinement phase corresponds to prototype design and validation, in which the
> system architecture and an interactive prototype were designed and are being
> evaluated by the client agencies and the capstone adviser, whose feedback is
> used to refine the requirements and the prototype before development. The
> System Development and Implementation phase corresponds to the construction and
> integration of the system modules, while the Testing and Deployment phase covers
> unit, integration, and user acceptance testing, pilot testing, and the
> finalization of the system. The sections that follow present the outputs of the
> requirements analysis and design phases of this process.

### 4.2 — FR-06 contradicts itself 🔴 ⚠️ **SUPERSEDED 2026-08-27 — do NOT apply as written**

> **The replacement text below is now wrong and would introduce a false claim.**
> Delete and restore were removed from the system on 19 August
> (`2026_08_19_000001_drop_soft_deletes_from_vehicles_and_users`;
> `RecordsAreNotDeletableTest` keeps them gone). Adding *"delete, and restore"*
> to FR-06 would describe a feature that does not exist.
>
> **What to do instead:** take FR-05 and FR-06 verbatim from
> `skills/rvms-source-of-truth.md`, which was rewritten to match. Both now end
> with a retention sentence rather than a delete verb — FR-06 reads:
>
> > Enables Agency Administrators to add, update, and view driver records,
> > including name, agency, license number, and license expiry date, and to
> > assign a driver as the primary driver of one or more vehicles. Driver
> > records are retained permanently so that the inspections and damage reports
> > they submitted continue to identify their author.
>
> The full revised table is in `manuscript/manuscript-revision-plan-2026-08.md`
> Part 2, which is the authority for this change.

*Original finding, kept for the record:* its opening listed only three verbs,
while its own next two sentences described deletion and restoration. That
contradiction is now resolved in the other direction — the sentences went, not
the verbs.

### 4.3 — FR-15 missing preposition 🟡

| Find | Replace |
|---|---|
| `location, and date and time out, and an optional odometer reading time out` | `location, date and time out, and an optional odometer reading at time out` |

### 4.4 — FR-16 wording 🟡

**Replace the whole description with:**

> Allows Agency Administrators to close a dispatch by logging the date and time
> in, an optional odometer reading at time in, and the vehicle's return status
> (Operational, Not Operational, or Under Preventive Maintenance); the vehicle's
> current mileage is updated from the time-in odometer reading.

### 4.5 — FR-22 missing "to" 🟡

| Find | Replace |
|---|---|
| `sign in, and set a new password for a fellow` | `sign in, and to set a new password for a fellow` |

### 4.6 — System Architecture narrative lists dispatch twice 🟡

| Find | Replace |
|---|---|
| `dispatch logs, repair histories, preventive maintenance schedules, and dispatch records from which the system's reports are generated` | `dispatch logs, repair histories, and preventive maintenance schedules — the records from which the system's reports are generated` |

### 4.7 — Optional: soft delete in the Table 3 narrative ⚪ ⚠️ **WITHDRAWN 2026-08-27 — do NOT apply**

This asked for a clause about *"the removal and restoration of records that are
no longer in service"* to be added to the .docx. **That capability was removed on
19 August**, and the repo's Table 3 narrative no longer carries the clause —
it now says records are retained permanently instead. Adding it would put a
removed feature back into the manuscript.

Nothing to do. Superseded by `manuscript/manuscript-revision-plan-2026-08.md`.

---

## PART 5 — DIAGRAMS

⏸️ **Deferred by the project lead** to after testing and troubleshooting, and
partly gated on the capstone instructor's pending format update.

Full detail, including exact edits and the ERD/data-dictionary scope, is in
**`skills/rvms-deferred-manuscript-work.md`**. Summary:

| # | Figure | Change |
|---|---|---|
| A1 | 2 — System Architecture | `TRIGGGER` → `TRIGGER` |
| A2 | 3 — Context Diagram | `Recods` → `Records` |
| A3 | 5 — FDD | add `PASSWORD RECOVERY` under USER MANAGEMENT (FR-22 has no leaf) |
| A4 | 5 — FDD | add notification list management (FR-21's mark-read / clear have no leaf) |
| A5 | 4 — DFD | **no change** — 8 stores vs 11 tables is defensible; reasoning recorded |

⚠️ Every diagram change needs the figure **re-exported and re-pasted** into the
.docx. The manuscript holds flattened images.

---

## PART 6 — ALREADY FIXED (no action needed)

### Repo source of truth — corrected during the audit

Drift ran both ways. Two places where the .docx was ahead of the repo, now
matched (commit `8090d24`):

- the Table 3 narrative omitted password recovery from the access requirements;
- it omitted "inspections with flagged items" from the notification list.

### Decisions recorded

- ~~**`deleted_at` IS documented** in the Chapter 4 data dictionary~~
  ⚠️ **REVERSED 2026-08-27.** The rule is unchanged — a column is documented when
  a requirement made it necessary — but the requirement went: FR-05/FR-06 no
  longer permit deletion, and the columns were dropped from the database on
  19 August. `deleted_at` must appear **nowhere** in the Chapter 4 data
  dictionary. See `manuscript/manuscript-revision-plan-2026-08.md` item 6.
- **`vehicles.remarks`, `status_source`, `status_changed_at` stay OUT** — no FR
  backs them (design decisions 7 and 9).
- **DFD stays at 8 data stores** — agency is a scoping attribute, not a store.

---

## PART 7 — WHAT WAS VERIFIED CLEAN

Recorded so it is not re-investigated.

**All four objectives met**, each traced Ch1 → Ch2 → Ch3 → Ch4 → diagrams → code
→ tests.

**Every enumeration matches the code exactly** — 15 of 15 groups:

| Group | |
|---|---|
| BLOWBAGETS standard items | ✅ 12/12, exact names and order |
| BLOWBAGETS BFP-only items | ✅ 2/2 (Hydraulic System, Fire Pump) |
| Vehicle statuses | ✅ 4/4 |
| Return statuses | ✅ 3/3 (correctly excludes Dispatched) |
| Mission types | ✅ 6/6 |
| Repair sources | ✅ 3/3 |
| PM types / PM statuses | ✅ 2/2 and 4/4 |
| Inspection item / review statuses | ✅ 2/2 and 2/2 |
| Agencies | ✅ 4/4 |
| Report types | ✅ 6/6 |
| Dashboard counters | ✅ 8/8 |
| Browsers (NFR-05) | ✅ 3/3 |

*(Account statuses read 2/3 only because the value `rejected` belongs in the data
dictionary rather than prose — the manuscript correctly describes the action,
"approves or rejects".)*

**Other verified facts:** every field named in FR-05/FR-06 exists as a column ·
FR-20's "generated by / generated on" stamp is implemented
(`reports.blade.php:120-121`) · PM never auto-renews, as Chapter 1 promises ·
Figures 4 and 5 correspond one-to-one across all eight processes/functions.

---

## PART 8 — POST-AUDIT ADDITIONS (found after the audit closed)

> Kept separate so the audit above still reads as what it was on the day it was
> run. These are found later, by ordinary work, and belong on the same checklist.

### 8.1 — FR-08 in the .docx is a version behind the repo 🔴 *(added 2026-08-26)*

The repo's source of truth expanded FR-08 twice after the audit — once when the
warning window was confirmed as per-agency configuration, and once when the
licence state was surfaced at the moment of dispatch. Neither reached the .docx.

**Verified by reading the file**, not assumed: `RVMS-Chapter4-Draft-with-Data-Dictionary.docx`
currently ends FR-08 at *"…through a consolidated monitoring view."*

**Fix — Chapter 4, the Functional Requirements table, FR-08 Description.**
Replace the whole cell with the source-of-truth wording:

> Automatically detects driver licenses that are approaching expiry or have
> expired and alerts the Agency Administrator through a consolidated monitoring
> view. The number of days before expiry at which a license is flagged as
> approaching expiry is configured per agency, and a license that is approaching
> expiry or has expired is shown to the Agency Administrator when a driver is
> selected for dispatch.

Both added clauses describe behaviour that is built and tested — this is the
manuscript catching up to the system, not a new claim.

> **Test names corrected 2026-08-27.** This originally cited
> `LicenseWindowCommandTest`, which no longer exists: `rvms:license-window` was
> built and then reverted (`28b8b29`), and its test went with it. The surviving
> coverage is `DispatchLicenseWarningTest` (the licence state shown when a driver
> is selected for dispatch) and `LicenseWarningWindowTest` (the per-agency window
> itself — the column, the badges, the daily sweep). **The FR-08 replacement
> wording above is unaffected and still correct**: the window is genuinely
> per-agency data, it is simply set at deployment rather than through a screen,
> which is exactly the point finding 8.2 makes.

### 8.2 — Table 5 narrative overstates who sets the threshold 🟡 *(added 2026-08-26)*

The agencies-table narrative reads *"…so that each agency may set its own warning
period."* That implies an agency changes it themselves. They cannot, and
deliberately so: there is no screen for it. The value is seeded at 30 for all
four agencies, and a deployment that genuinely needed a different one would set
it in the database — not something an agency does. The sentence as written would
invite a panelist to ask where that screen is.

**Fix — Chapter 4, the narrative paragraph under Table 5.** Replace:

> That threshold is stored here rather than fixed in the program so that each
> agency may set its own warning period.

with:

> That threshold is stored here rather than fixed in the program so that a
> different warning period may be configured for each agency.

One clause, and it makes the sentence true without weakening it — the point
being made is still that the value is data rather than code.

**Data dictionary: no change.** The `license_expiry_warning_days` row already
reads *"Configurable per agency,"* which is accurate.

**No other manuscript impact.** No new column, no ERD change, no FR renumbering.

---

## EXECUTION CHECKLIST

Chapter 1 first — it holds the only 🔴 items and they are all in one paragraph.

**Chapter 1**
- [ ] 1.1 — replace Scope & Limitations wholesale from the source of truth 🔴 ⚠️ *revised — do not paste the original three sentences*
- [ ] 1.2a — `Specifically;` → `Specifically:`
- [ ] 1.2b — `electronic main` → `electronic mail`
- [ ] 1.2c — `recovery common` → `recovery command`
- [ ] 1.2d — `BFPs` → `BFP's`
- [ ] 1.3 — replace the GPS/odometer sentences
- [ ] 1.4 — remove the stray `and`
- [ ] 1.5 — introduce `(RVMS)`

**Chapter 3**
- [ ] 3.1 — Kotlin and Compose versions *(optional)*

**Chapter 4**
- [ ] 4.1 — insert the methodology paragraph 🔴
- [ ] 4.2 — FR-06: take the row verbatim from the source of truth ⚠️ *revised — the old "delete, and restore" replacement is withdrawn*
- [ ] 4.3 — FR-15
- [ ] 4.4 — FR-16
- [ ] 4.5 — FR-22
- [ ] 4.6 — System Architecture narrative
- [x] 4.7 — ~~soft delete in the Table 3 narrative~~ ⚠️ **withdrawn 2026-08-27 — nothing to do**

**Chapter 2**
- [ ] 2.1 — BLOWBAGETS sentence *(optional)*

**Post-audit additions** — see Part 8
- [ ] 8.1 — FR-08 description, replace the whole cell 🔴
- [ ] 8.2 — Table 5 narrative, one clause 🟡

**Deferred** — see `rvms-deferred-manuscript-work.md`
- [ ] Diagrams, ERD, data dictionary

**Code:** nothing from the audit itself — the system passed clean. The Part 8
additions also need no code — they bring the .docx in line with behaviour that
is already built and tested.
