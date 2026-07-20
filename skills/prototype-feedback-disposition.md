# RVMS — Prototype Testing Feedback Disposition

> **Purpose.** A for-the-record mapping of each piece of client prototype-testing
> feedback to a decision (implemented / already in scope / deliberately excluded)
> with rationale. Feeds Chapter 4 (requirements refinement) and Chapter 5
> (Recommendations for Future Enhancements). Keep in the repo as the traceable
> record of how client feedback shaped the backend plan.
>
> **Status: 3 of 4 agencies responded. BFP feedback PENDING** — this document
> will be updated when BFP responds. BFP is the only agency with the extended
> 14-item BLOWBAGETS checklist, so their input may surface an additional in-scope
> item the other three would not.

## 1. Summary of results (3 respondents: PNP, CDRRMO, CHO)

- **Overall satisfaction:** 5/5 ×2, 4/5 ×1 — no rating below 4.
- **Needs addressed (Q4):** every statement rated "Agree" by the majority; zero
  "Disagree." Two items drew a single "Neutral" each (damage-report detail;
  overall-needs) — no specific complaint attached.
- **Objectives met (Q5):** Objectives 1 & 2 unanimously "Agree"; Objectives 3 & 4
  each "Agree" ×2 / "Neutral" ×1. No "Disagree" anywhere.
- **Most useful (Q7):** vehicle-status monitoring, mobile PM tracking, "All."

**Read:** strongly positive. Most "suggestions" describe capabilities the system
already delivers or has planned — i.e., the feedback largely *validates* the
design rather than redirecting it.

## 2. Disposition of every suggestion

### Bucket A — Implemented (new work adopted from feedback)

| Feedback (verbatim source) | Decision | Where it lands |
|---|---|---|
| "record the total mileage upon arrival"; "mileage monitoring" (Q8/Q9) — CDRRMO confirmed the odometer is already recorded on their paper dispatch form | **Implemented** as optional `odometer_out` / `odometer_in` on `dispatches`; closing with a higher time-in reading updates the vehicle's current mileage (feeds mileage-based PM). Manual entry from the vehicle's own odometer — not GPS/IoT. | Manuscript: FR-15/FR-16 wording + `dispatches` data dictionary (2 rows). System: **Phase R6 (Dispatch)**. No diagram change (2 attributes on the existing Dispatch entity). |

### Bucket B — Already in scope / already built (feedback validates the design)

| Feedback | Already covered by |
|---|---|
| "Automated Maintenance Alerts" (Q9) | FR-21 notifications — PM Due Soon / Due reminders to admins and drivers (Phases R5 + R7). |
| "Store maintenance history with dates, service type, and provider" (Q9) | Repair Logs (FR-13: repair date, scope, parts, source, external shop) + PM completion fields (FR-14). |
| "Trip Logs" (Q9) | Dispatch records (FR-15/FR-16: vehicle, driver, mission, location, time out/in). |
| "easy access and monitoring of vehicle status" (Q7, liked most) | Vehicle Management + availability monitoring (FR-17/FR-18) — delivered in Phase R2. |
| "mobile app tracking feature for preventive maintenance" (Q7) | Driver PM reminders via FCM (FR-21, Phase R7). |

*No system or manuscript change required for Bucket B.*

### Bucket C — Deliberately excluded (out of scope → Recommendations / Future Work)

| Feedback | Decision & rationale |
|---|---|
| GPS tracking / "GPS tracking of each vehicle" (Q8) | **Excluded.** No study objective backs real-time location tracking; it is a separate telematics system. Already listed in Ch1 Scope & Limitations. |
| "Route optimization & Monitoring" (Q9) | **Excluded.** Same as above — routing/navigation is outside vehicle-maintenance management scope. |
| Fuel monitoring — "audiometer for fuel … monitoring" (Q9; "audiometer" read as odometer/fuel gauge) | **Excluded.** Fuel management is not among the four objectives. Added explicitly to Ch1 Scope & Limitations. |
| "Dispatch scheduling" (Q9) | **Excluded.** The system does dispatch *logging* (recording actual trips), not *scheduling* (planning future trips) — assignment stays with each agency's internal procedures, per Ch1 Scope & Limitations. |

These four are the **Chapter 5 "Recommendations for Future Enhancements"** feed.

## 3. Neutral marks — watch items (no action yet)

- **Damage-report detail (Q4, 1 Neutral).** Current `damage_reports` capture nature
  of damage, suspected parts, and an optional photo. No specific gap was named;
  revisit if BFP's response elaborates.
- **Objectives 3 & 4 (Q5, 1 Neutral each).** Dispatch/admin view and reports.
  Both modules (R6, R8) are not yet in the users' hands — the neutral likely
  reflects prototype-stage limits rather than a design gap.

## 4. Net effect on scope

- **System:** one adopted feature (dispatch odometer / mileage-on-arrival), landing
  in Phase R6. Everything else praised or suggested is already built or planned.
- **Manuscript:** Ch1 Scope & Limitations gains a fuel-monitoring exclusion and an
  odometer-capture note; Ch4 FR-15/FR-16 + the `dispatches` data dictionary gain the
  two optional odometer columns. No new FR (still 21), no new diagram, no new entity.
