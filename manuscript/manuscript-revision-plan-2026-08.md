# Manuscript Revision Plan — August 2026

Two decisions, applied to the system on 19 August and now needing to be applied
to the manuscript:

1. **Vehicle and driver records cannot be deleted.**
2. **An administrator may reset a driver's password, but not another
   administrator's.**

The code is already done: ten routes, four controller methods, two services, two
UI cards, the soft-delete columns and every `withTrashed()` are gone, and
`RecordsAreNotDeletableTest` asserts they stay gone. Suite green at 595 passing.

**The manuscript is now the only place that still describes the old system.**
Everything below is the work to fix that.

---

## PART 1 — Where each edit goes

Ordered by chapter, so you can work front to back.

| # | Chapter | Section | Edit | Size |
|---|---|---|---|---|
| 1 | **1** | Scope and Limitations — *scope* paragraph | Delete the clause about deleting and restoring records, if present | 1 sentence |
| 2 | **1** | Scope and Limitations — *limitations* paragraph | Narrow the password-recovery sentence to drivers only | 1 sentence |
| 3 | **1** | Scope and Limitations — *limitations* paragraph | **Add**: records are not deletable, and why | 3 sentences |
| 4 | **3** | Requirements Documentation → Functional Requirements | Replace the FR-05, FR-06 and FR-22 rows | 3 table rows |
| 5 | **3** | Requirements Documentation → Functional Requirements | **Add** the supporting narrative paragraph | 1 paragraph |
| 6 | **4** | Design of Software → Data Model → Data Dictionary | Remove the `deleted_at` row from the `vehicles` and `users` tables | 2 table rows |
| 7 | **4** | Design of Software → Data Model → ERD | No change — `deleted_at` was never drawn | — |
| 8 | **4** | Results / system screens | Re-screenshot Vehicles, Drivers and Profile if the old shots show delete buttons | 3 figures |

> **Item 8 is the one that gets forgotten.** A screenshot showing a red trash
> icon is a claim that the feature exists, and it will contradict your FR table.

---

## PART 2 — The revised Functional Requirements table

Complete, for pasting into Chapter 3. **Three rows changed** — FR-05, FR-06,
FR-22 — and they are marked. Everything else is as it was.

| Code | Functional Requirement | Description |
|---|---|---|
| FR-01 | User Authentication | Allows users to securely log in using an email and password, verifies their credentials, identifies their role, and directs them to the appropriate platform. |
| FR-02 | Role-Based and Agency-Scoped Access | Restricts system access to two roles — Authorized Driver and Agency Administrator — and limits each Agency Administrator to records belonging only to their own agency. |
| FR-03 | Driver Account Registration and Approval | Allows an Authorized Driver to self-register an account by selecting their agency and submitting credentials; the account remains pending until the Agency Administrator of that agency approves or rejects it. Agency Administrators may also add driver accounts directly, which are active immediately. |
| FR-04 | Self-Service Profile Management | Allows Authorized Drivers and Agency Administrators to update their own name, email, and password without administrator approval. |
| **FR-05** ⬅ | **Vehicle Record Management** | **Enables Agency Administrators to add, update, and view vehicle records, including vehicle type, plate number, make, model, engine number, chassis number, current mileage, assigned agency, and assigned driver. Vehicle records are retained permanently so that the inspection, damage, repair, preventive maintenance, and dispatch history referring to them remains complete.** |
| **FR-06** ⬅ | **Driver Record Management** | **Enables Agency Administrators to add, update, and view driver records, including name, agency, license number, and license expiry date, and to assign a driver as the primary driver of one or more vehicles. Driver records are retained permanently so that the inspections and damage reports they submitted continue to identify their author.** |
| FR-07 | Assigned Vehicle Viewing | Allows Authorized Drivers to view the complete details and current operational status of the vehicle(s) assigned to them from the mobile application. |
| FR-08 | License Expiry Monitoring | Automatically detects driver licenses that are approaching expiry or have expired and alerts the Agency Administrator through a consolidated monitoring view. |
| FR-09 | Digital BLOWBAGETS Inspection | Allows Authorized Drivers to submit daily inspections using a standardized digital checklist of twelve items, with two additional items for BFP vehicles, marking each item OK or Has Issue, with remarks required for flagged items. |
| FR-10 | Inspection Monitoring | Allows Agency Administrators to review submitted inspection results, view inspection history per vehicle and per driver, and monitor frequently reported issues. |
| FR-11 | Damage Reporting | Allows Authorized Drivers to submit standardized damage reports containing the nature of damage, suspected defective parts, and an optional photo attachment, with the date reported set automatically. |
| FR-12 | Damage Report Management | Allows Agency Administrators to review submitted damage reports, mark them as Reviewed, and update the vehicle's operational status based on the assessment. |
| FR-13 | Repair Logging | Allows Agency Administrators to document repair activities, including scope of work, parts replaced, optional cost, repair source, assigned driver, and remarks, and to update vehicle status after the repair is logged. |
| FR-14 | Preventive Maintenance Scheduling | Allows Agency Administrators to configure mileage-based or time-based preventive maintenance schedules with a configurable Due Soon threshold, and to record completion details when the service is performed. |
| FR-15 | Dispatch Logging | Allows Agency Administrators to record vehicle dispatches with vehicle, driver, mission type, location, date and time out, and an optional odometer reading at time out, automatically setting the vehicle's status to Dispatched. |
| FR-16 | Dispatch Closing and Return Status | Allows Agency Administrators to close a dispatch by logging the date and time in, an optional odometer reading at time in, and the vehicle's return status, updating the vehicle's current mileage from the time-in reading. |
| FR-17 | Vehicle Availability Monitoring | Allows Agency Administrators to view the current operational status of all vehicles within their agency to support deployment decisions. |
| FR-18 | Vehicle Status Management | Maintains a single vehicle operational status — Operational, Dispatched, Not Operational, or Under Preventive Maintenance — that can be updated from any applicable module to keep one consistent value across the system. |
| FR-19 | Dashboard Summary | Displays real-time counts of vehicle statuses, total vehicles, total drivers, expiring licenses, and pending damage reports for the administrator's own agency. |
| FR-20 | Report Generation | Allows Agency Administrators to generate filtered, printable reports for inspections, damage reports, repair and maintenance history, preventive maintenance, dispatch logs, and vehicle status summaries. |
| FR-21 | Notification Services | Delivers in-app notifications to Authorized Drivers for preventive maintenance reminders and vehicle status updates, and to Agency Administrators for new damage reports, new driver access requests, flagged inspections, approaching or expired licenses, and due preventive maintenance; administrators may mark notifications as read and clear those they have already read. |
| **FR-22** ⬅ | **Administrator-Assisted Password Reset** | **Allows an Agency Administrator to set a new password for an Authorized Driver of their own agency who can no longer sign in, after confirming their own account password. The affected driver is notified that their password was reset and by whom. Password reset is performed within the system and communicated directly to the driver; the system does not send electronic mail, and an Agency Administrator cannot reset the password of another Agency Administrator.** |

> **FR-22's title changes** from "Password Recovery" to **"Administrator-Assisted
> Password Reset"** — the new wording is narrower than "recovery" implies.

---

## PART 3 — The supporting narrative (Chapter 3)

Your methodology deck requires the table to be "supported with a narrative."
Your draft has no such paragraph. Paste this immediately after the FR table:

> The functional requirements of the Rescue Vehicle Management System are
> outlined in Table 3. These requirements were derived from interviews with
> personnel of the four participating agencies and from a review of the paper
> forms and logbooks currently used to record vehicle inspections, damage
> reports, repairs, preventive maintenance, and dispatches. They are organized
> to follow the flow of work in the agencies themselves. FR-01 to FR-04 and
> FR-22 establish who may use the system and what each role may see, and are
> enabling requirements: they do not deliver an objective on their own, but no
> objective can be delivered without them, because the system serves four
> agencies from one database and must keep each agency's records separate.
> FR-05 to FR-08 address the first objective by consolidating vehicle and
> driver information that is presently kept on separate, non-searchable forms.
> FR-09 to FR-14 address the second objective, covering the daily inspection,
> the reporting and assessment of damage, the logging of repairs, and the
> scheduling of preventive maintenance. FR-15 to FR-18 address the third
> objective by recording dispatches and maintaining one shared vehicle status
> that every module reads and writes. FR-19 and FR-20 address the fourth
> objective through a live dashboard and six filtered, printable reports.
> FR-21 carries the alerts that make the preceding requirements timely rather
> than merely recorded. Together these requirements define the behaviour that
> the design, development, and testing phases of the project were measured
> against.

**Why this paragraph earns its place:** it is your answer to *"why is profile
editing a requirement when it is not in the objectives?"* Naming FR-01–04 and
FR-22 as **enabling requirements** is a standard, defensible position, and it is
much stronger than either deleting working features or leaving the gap
unexplained.

---

## PART 4 — Scope and Limitations (Chapter 1)

### 4.1 Replace, in the limitations paragraph

> ~~"…password recovery is performed within the system, with an Agency
> Administrator setting a new password for an Authorized Driver of their agency
> **or for a fellow Agency Administrator**, and an Agency Administrator who **is
> the only administrator of their agency and** can no longer sign in must be
> assisted through a recovery command run on the server by the personnel
> maintaining it."~~

**with**

> "…password reset is performed within the system, with an Agency Administrator
> setting a new password only for an Authorized Driver of their own agency. An
> Agency Administrator cannot reset the password of another Agency
> Administrator; an Agency Administrator who can no longer sign in must be
> assisted through a recovery command run on the server by the personnel
> maintaining it."

### 4.2 Add to the limitations paragraph

> "Vehicle and driver records may be added and updated but not deleted. This is
> a deliberate constraint rather than an omission: every inspection, damage
> report, repair log, preventive maintenance schedule, and dispatch refers to a
> vehicle and a driver, and removing either would break the history that the
> system exists to preserve. A vehicle that leaves service is recorded through
> its operational status, and a driver who leaves the agency is recorded by
> reassigning their vehicles to another driver."

> **That last sentence is the important one.** Without it the obvious question
> is *"then how do you retire a truck?"*, and the answer needs to already be in
> the text rather than improvised at the defense.

---

## PART 5 — Data Dictionary (Chapter 4)

**Remove two rows**, both named `deleted_at`:

| Table | Row to delete |
|---|---|
| `vehicles` | `deleted_at` — TIMESTAMP, nullable, "Soft delete…" |
| `users` | `deleted_at` — TIMESTAMP, nullable, "Soft delete…" |

The column counts drop by one each: **`vehicles` 14 → 13**, **`users` 15 → 14**.
Total documented fields: **131 → 129**.

**The ERD needs no change** — `deleted_at` was never drawn on it.

> If you regenerate the data dictionary from the repository scripts, this
> happens automatically: `erd_model.py` reads the migrations, and `verify.py`
> will report the new counts.

---

## PART 6 — What the panel will ask, and the answers

**"Why can't you delete a vehicle?"**
Because five other kinds of record point at it. Deleting the truck would orphan
every inspection, damage report, repair, maintenance schedule and dispatch that
names it. A truck that leaves service is marked Not Operational — the record
stays, the history stays, and it stops appearing in dispatch selections.

**"But you can delete a notification. Why is that different?"**
A notification is a copy of an alert delivered to one person's inbox; the event
it describes is still recorded elsewhere. And only notifications already **read**
can be cleared, so nothing unseen is destroyed.

**"Why can an admin reset a driver's password but not another admin's?"**
Because resetting a peer hands over an account with the same authority as your
own, and the affected person would only find out afterwards. There is a clear
hierarchy — administrators over drivers — and a locked-out administrator is
restored by whoever maintains the server.

**"Isn't profile editing outside your objectives?"**
It is an enabling requirement. The system requires authentication, and any
system with accounts must let people manage them. See the Chapter 3 narrative.

---

## PART 7 — Checklist

- [ ] Ch 1 — narrow the password sentence (4.1)
- [ ] Ch 1 — add the no-deletion sentences (4.2)
- [ ] Ch 3 — replace FR-05, FR-06, FR-22 rows
- [ ] Ch 3 — retitle FR-22
- [ ] Ch 3 — add the supporting narrative
- [ ] Ch 4 — remove two `deleted_at` rows; update column counts
- [ ] Ch 4 — re-screenshot Vehicles, Drivers, Profile if delete buttons appear
- [ ] Check every other chapter for "delete", "restore", "Deleted Records"
- [ ] Confirm FR count still reads **FR-01 to FR-22** wherever it is stated
