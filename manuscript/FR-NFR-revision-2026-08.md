# Functional Requirements — proposed revision (August 2026)

Drafted against two things at once: the **methodology lesson's** required format
(a table supported by a narrative, requirements framed as Input · Process ·
Output · Performance · Security) and **what the system actually does**, read from
the repository rather than from the previous draft.

Three decisions from the 19 August discussion are applied:

1. **Vehicle and driver records can no longer be deleted** — FR-05 and FR-06.
2. **An administrator may reset a driver's password, but not another
   administrator's** — FR-22.
3. Scope and Limitations follows both.

---

## Decision 1 — numbering stays as it is

The existing FR-01 … FR-22 order already groups correctly, and renumbering would
touch every chapter, the data dictionary, the demo script and the code comments
for no gain a panel would notice:

| Group | FRs | Objective served |
|---|---|---|
| Access and account management | FR-01 – FR-04, FR-22 | *enabling* — see the narrative |
| Vehicle and driver records | FR-05 – FR-08 | Objective 1 |
| Inspection and damage | FR-09 – FR-12 | Objective 2 |
| Repair and maintenance | FR-13, FR-14 | Objective 2 |
| Dispatch and availability | FR-15 – FR-18 | Objective 3 |
| Monitoring and reporting | FR-19, FR-20 | Objective 4 |
| Notifications | FR-21 | supports 2 and 3 |

The one imperfection is FR-22 sitting at the end, away from the access group it
belongs to. That is normal for a requirement added after the first draft, and it
is not worth renumbering twenty-one rows to fix.

---

## The three rows that change

### FR-05 — Vehicle Record Management

> **Was:** "…add, update, view, delete, and restore vehicle records… A deleted
> vehicle is removed from all lists and selections while its inspection, damage,
> repair, preventive maintenance, and dispatch history is retained…"

**Becomes:**

> Enables Agency Administrators to add, update, and view vehicle records,
> including vehicle type, plate number, make, model, engine number, chassis
> number, current mileage, assigned agency, and assigned driver. Vehicle records
> are retained permanently so that the inspection, damage, repair, preventive
> maintenance, and dispatch history referring to them remains complete.

### FR-06 — Driver Record Management

> **Was:** "…add, update, view, delete, and restore driver records… A deleted
> driver is removed from all lists and selections and their vehicle assignments
> are released…"

**Becomes:**

> Enables Agency Administrators to add, update, and view driver records,
> including name, agency, license number, and license expiry date, and to assign
> a driver as the primary driver of one or more vehicles. Driver records are
> retained permanently so that the inspections and damage reports they submitted
> continue to identify their author.

### FR-22 — Administrator-Assisted Password Reset

> **Was:** "…to set a new password for an Authorized Driver of their agency… and
> to set a new password for a fellow Agency Administrator of the same agency
> after confirming their own password."

**Becomes:**

> Allows an Agency Administrator to set a new password for an Authorized Driver
> of their own agency who can no longer sign in. The affected driver is notified
> that their password was reset and by whom. Password reset is performed within
> the system and communicated directly to the driver; the system does not send
> electronic mail, and an Agency Administrator cannot reset the password of
> another Agency Administrator.

**Title change:** "Password Recovery" → **"Administrator-Assisted Password
Reset"**, because the new wording is narrower than "recovery" implies.

---

## The narrative paragraph the lesson requires

The methodology deck asks for the table to be "supported with a narrative to
provide context, explanation, and clarity that a table alone cannot fully
convey." Suggested text:

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
> FR-05 to FR-08 address the first objective by consolidating vehicle and driver
> information that is presently kept on separate, non-searchable forms. FR-09 to
> FR-14 address the second objective, covering the daily inspection, the
> reporting and assessment of damage, the logging of repairs, and the scheduling
> of preventive maintenance. FR-15 to FR-18 address the third objective by
> recording dispatches and maintaining one shared vehicle status that every
> module reads and writes. FR-19 and FR-20 address the fourth objective through
> a live dashboard and six filtered, printable reports. FR-21 carries the
> alerts that make the preceding requirements timely rather than merely
> recorded. Together these requirements define the behaviour that the design,
> development, and testing phases of the project were measured against.

---

## Scope and Limitations — the matching edits

### In the limitations paragraph, replace

> "…password recovery is performed within the system, with an Agency
> Administrator setting a new password for an Authorized Driver of their agency
> **or for a fellow Agency Administrator**, and an Agency Administrator who **is
> the only administrator of their agency and** can no longer sign in must be
> assisted through a recovery command run on the server by the personnel
> maintaining it."

**with**

> "…password reset is performed within the system, with an Agency Administrator
> setting a new password only for an Authorized Driver of their own agency. An
> Agency Administrator cannot reset the password of another Agency
> Administrator; an Agency Administrator who can no longer sign in must be
> assisted through a recovery command run on the server by the personnel
> maintaining it."

### Add to the limitations paragraph

> "Vehicle and driver records may be added and updated but not deleted. This is
> a deliberate constraint rather than an omission: every inspection, damage
> report, repair log, preventive maintenance schedule, and dispatch refers to a
> vehicle and a driver, and removing either would break the history that the
> system exists to preserve. A vehicle that leaves service or a driver who
> leaves the agency is recorded through the vehicle's operational status and
> through the reassignment of that vehicle to another driver."

That last sentence matters — without it, the obvious question is *"then how do
you retire a truck?"* and the answer needs to already be in the text.

---

## ⚠️ The system currently does more than this draft claims

Delete and restore are **fully built** — API routes, web routes, controllers,
soft-delete columns, and tests:

```
DELETE  api/v1/drivers/{driver}            PATCH  api/v1/drivers/{driver}/restore
DELETE  api/v1/vehicles/{vehicle}          PATCH  api/v1/vehicles/{vehicle}/restore
DELETE  drivers/{driver}                   PATCH  drivers/{driver}/restore
DELETE  vehicles/{vehicle}                 PATCH  vehicles/{vehicle}/restore
```

The same is true of the co-administrator password reset.

**Removing these from the requirements without removing them from the system
creates exactly the mismatch this revision is meant to eliminate** — and it is
the kind a panel finds by clicking a button that no requirement describes.

Three options, in order of preference:

1. **Remove from both.** Delete the buttons, the routes, the controller methods
   and the tests; keep the `deleted_at` columns unused, or drop them by
   migration. The manuscript and the system then agree exactly.
2. **Keep both, and demo neither.** Least work, but the FRs still describe a
   system slightly smaller than the one that exists.
3. **Hide the buttons, keep the routes.** The worst of the three — the feature is
   still reachable by anyone who knows the URL, so the mismatch remains and is
   now also undocumented.

**Recommendation: option 1**, and it is worth doing while there is still time to
run the test suite afterwards.
