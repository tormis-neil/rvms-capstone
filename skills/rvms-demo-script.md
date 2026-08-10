# Demo Script & Functional Acceptance Test

> **One script, all three tiers.** The system behaves identically whether it is
> deployed, running off a laptop hotspot, or connected by USB cable — only the
> way the phone reaches the server changes. The few things that genuinely differ
> are listed in [Tier differences](#tier-differences) and nowhere else.
>
> **This document does three jobs at once:**
> 1. the running order for the client and panel demos;
> 2. the **functional acceptance test** — every step names the requirement it
>    proves and the exact result to expect;
> 3. the checklist the members use when testing on their own devices.
>
> **Get the system running first** using the runbook for whichever tier you are
> on. This document assumes both logins already work.
>
> | Tier | Runbook |
> |---|---|
> | 1 — Deployed | *(to be written)* |
> | 2 — Laptop hotspot | `rvms-demo-tier2-hotspot.md` |
> | 3 — USB cable | `rvms-demo-tier3-usb.md` |

---

<a name="tier-differences"></a>
## Tier differences — the only things that change

| | Tier 1 · Deployed | Tier 2 · Hotspot | Tier 3 · USB |
|---|---|---|---|
| Phone can move around the room | ✅ freely | ✅ freely | ❌ tethered by cable |
| Push notification banners | ✅ always | ✅ if phone has data | ⚠️ only if phone has data |
| Panel can open the dashboard on their own device | ✅ yes | ❌ no | ❌ no |
| Needs venue internet | ✅ yes | ❌ no | ❌ no |

**Everything else in this document is identical in all three tiers.**

> **When push banners are unavailable** (Tier 2/3 with no mobile data), the alert
> is still created and still appears in the app's **Alerts** tab, because it is
> stored in your own database. Show it there and say so:
> *"The alert is delivered and stored. The pop-up banner needs Google's servers,
> which needs internet this room does not have."*
> That is accurate, and it is a stronger answer than being caught out by it.

---

## Roles — assign these before you start

Two-device demos fail when one person tries to do everything.

| Role | Holds | Job |
|---|---|---|
| **Driver** | The phone | Follows the phone steps. Says nothing else. |
| **Administrator** | The laptop / projector | Follows the laptop steps. |
| **Narrator** | Nothing | Explains what is happening and why it matters. Reads the *"Say this"* lines. |

The narrator is the important one. Without it, the audience watches two people
click in silence.

---

## Accounts used

| Who | Email | Password |
|---|---|---|
| BFP administrator | `bfp.admin@rvms.local` | `password` |
| BFP second administrator | `bfp.admin2@rvms.local` | `password` |
| BFP driver | `ramon.villanueva@rvms.local` | `password` |
| CHO administrator *(for the isolation test)* | `cho.admin@rvms.local` | `password` |

**Vehicle used throughout:** **ABC-1234**, Isuzu FTR 850 fire truck, assigned to
Ramon Villanueva, starting **Operational** at **45,230 km**.

---

# THE SCRIPT

Seven acts, in narrative order. Each row: **what to do** → **what you should
see**. If what you see does not match, that is a failed acceptance test — write
it down and carry on; do not stop the demo to debug.

**Total running time: 20–25 minutes.** A short version is at the end.

---

## ACT 1 — Access and identity · 4 minutes

*Proves FR-01, FR-02, FR-03, FR-04 and NFR-02.*

> **Narrator, say this:** *"Four agencies share one system, but no agency can see
> another's records. That separation is the first thing we built and it is
> enforced on every single screen."*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 1.1 | Admin | Open the site. Log in as `bfp.admin@rvms.local` | Dashboard opens; top bar reads **Bureau of Fire Protection** with the BFP logo | FR-01 |
| 1.2 | Admin | Sign out. Try to log in with the **driver** account `ramon.villanueva@rvms.local` | **Refused**, with a message to use the mobile app | FR-01 |
| 1.3 | Admin | Log back in as the BFP admin. Note the vehicles listed | **ABC-1234** and **EFG-4532** — BFP's two vehicles | FR-05 |
| 1.4 | Admin | Sign out. Log in as `cho.admin@rvms.local`. Open **Vehicles** | **BFP's vehicles are gone.** Only CHO's appear | **FR-02** ⭐ |
| 1.5 | Admin | Sign out, log back in as the BFP admin | Back to BFP's fleet | FR-02 |
| 1.6 | Driver | Open the app, log in as `ramon.villanueva@rvms.local` | Home screen: greeting, assigned vehicle card | FR-01, FR-07 |
| 1.7 | Driver | Go to **Profile**, change your own name, save | Saves with no approval step; new name appears | FR-04 |

> ⭐ **Step 1.4 is a headline moment.** Pause on it. Say: *"Same system, same
> database, same screen — and BFP's fleet has simply disappeared. That is
> Functional Requirement 2, and it is why four agencies can share one system."*

**Optional — driver self-registration (FR-03).** If you have time: register a new
driver on the phone's Sign Up screen → it says *waiting for approval* → on the
laptop, **Drivers → Access Requests** → Approve → the new driver can now log in.
*(Adds ~2 minutes.)*

---

## ACT 2 — The records · 3 minutes

*Proves FR-05, FR-06, FR-07, FR-08.*

> **Narrator, say this:** *"Today each agency keeps vehicle records, driver
> assignments and licence dates on separate paper forms that are not
> consolidated or searchable. This replaces all of it."*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 2.1 | Admin | **Vehicles** → click the **eye** icon on ABC-1234 | Full details: type, plate, make, model, engine no., chassis no., **45,230 km**, assigned driver | FR-05 |
| 2.2 | Admin | Use the filter bar — search a plate, filter by status | Table narrows immediately | FR-05 |
| 2.3 | Admin | **Drivers** — look at the three cards at the top | Live counts: Valid / **Expiring Soon** / Expired | **FR-08** |
| 2.4 | Admin | Find a driver whose licence expiry shows in orange | That date is inside the agency's warning window | FR-08 |
| 2.5 | Driver | On the phone, tap the **assigned vehicle** card | Same vehicle, same plate, same status as the laptop shows | **FR-07** |

> **Narrator:** *"The driver sees exactly what the administrator assigned. One
> database, two platforms."*

---

## ACT 3 — The incident ⭐ the core of the demo · 6 minutes

*Proves FR-09, FR-10, FR-11, FR-12, FR-18, FR-21.*

> **Narrator, say this:** *"This is a normal morning. The driver checks the truck
> before taking it out — the same BLOWBAGETS checklist the BFP already fills in on
> paper, only now it reaches the administrator instantly."*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 3.1 | Driver | **New Inspection** → choose ABC-1234 | **14 checklist items** appear | **FR-09** ⭐ |
| 3.2 | Narrator | — | *"Fourteen, because BFP vehicles add Hydraulic System and Fire Pump. The other three agencies see twelve."* | FR-09 |
| 3.3 | Driver | Mark **Brakes** as **Has Issue**, leave the remark blank, tap Submit | **Blocked** — the app demands a remark | FR-09 |
| 3.4 | Driver | Type `Unusual noise when braking`, submit | Success; appears in the driver's history | FR-09 |
| 3.5 | Admin | Open **Inspections & Damage** | A new **Today** row for ABC-1234, red **Has Issue**, marked **Pending** | **FR-10** ⭐ |
| 3.6 | Admin | Look at the bell (top right) | Unread count has risen; the new alert is listed with a blue dot | **FR-21** |
| 3.7 | Admin | Click **View Checklist** | Green ticks for OK items, red cross on Brakes, and the driver's exact wording | FR-10 |
| 3.8 | Driver | *(Tier 1, or data on)* — check the phone | Push banner arrived for the status change later in 3.10 | FR-21 |
| 3.9 | Admin | Click **Review** → set **Not Operational** → confirm | Confirmation dialog names current status, where it came from, and the new one; row flips to **Reviewed** | FR-10, FR-18 |
| 3.10 | Driver | Pull to refresh the phone home screen | Vehicle now reads **Not Operational** | **FR-18** ⭐ |
| 3.11 | Admin | Open **Vehicles** | ABC-1234 also reads Not Operational **here** | FR-18 |

> ⭐ **Steps 3.5 and 3.10 are the demo.** The driver acted on the phone and the
> administrator saw it on the web; the administrator decided on the web and the
> driver saw it on the phone. Say that out loud.

**Damage report (FR-11, FR-12)** — same shape, adds ~3 minutes:

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 3.12 | Driver | **Report Damage** → describe the damage → attach a photo → submit | Submits; appears in the driver's history | **FR-11** |
| 3.13 | Admin | **Inspections & Damage** → Damage section | New **Pending** report; bell count rises again | FR-12, FR-21 |
| 3.14 | Admin | Click **View** on the photo | The driver's photo displays | FR-11 |
| 3.15 | Admin | **Review & Assess** → confirm | Marked **Reviewed**, recording who and when | FR-12 |

---

## ACT 4 — Putting it right · 4 minutes

*Proves FR-13, FR-14 and the automatic status recalculation.*

> **Narrator:** *"The truck is off the road. Here is what happens next — and this
> is the part the agencies said they most need, because maintenance is what
> currently gets forgotten."*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 4.1 | Admin | **Repair Logs** → Add. Choose source **External Repair Shop** | A **shop name** box appears and is required | **FR-13** |
| 4.2 | Admin | Fill scope of work, parts, cost, save | Row added to the vehicle's repair history | FR-13 |
| 4.3 | Admin | **PM Schedules** — look at ABC-1234's schedule | A schedule with a service target and a **Due Soon threshold** | FR-14 |
| 4.4 | Narrator | — | *"The threshold is set by the administrator per schedule — it is not fixed in the program."* | FR-14 |
| 4.5 | Admin | Mark a due schedule **Completed** — date, source, parts, remarks | Moves to Completed; **no new schedule appears by itself** | FR-14 |
| 4.6 | Narrator | — | *"Each maintenance cycle is entered deliberately. The system never invents one."* | FR-14 |
| 4.7 | Admin | **Vehicles** → set ABC-1234 back to **Operational** | Status changes; confirmation dialog shown first | FR-18 |

---

## ACT 5 — Out on a mission · 4 minutes

*Proves FR-15, FR-16, FR-17, FR-18 and the automatic mileage update.*

> **Narrator:** *"Right now, availability is tracked by text message, group chat,
> or walking outside to look. This is the logbook that replaces that."*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 5.1 | Admin | **Dispatch Logs** → New Dispatch. Open the vehicle list | Only **Operational** vehicles are listed | FR-15, FR-17 |
| 5.2 | Admin | Choose mission **Others**, leave the detail blank, submit | **Refused** until you specify | FR-15 |
| 5.3 | Admin | Choose **Fire Response**, location, time out, odometer out `45230`. Submit | Dispatch opens | FR-15 |
| 5.4 | Admin | Open **Vehicles** | ABC-1234 now reads **Dispatched** — set automatically | **FR-15 → FR-18** ⭐ |
| 5.5 | Admin | Try to change its status from the Vehicles page | **Refused**, naming the mission, location and time out, pointing to Dispatch Logs | **FR-18** ⭐ |
| 5.6 | Narrator | — | *"A vehicle out on a mission cannot be quietly re-statused from another screen. Only closing the dispatch releases it."* | FR-18 |
| 5.7 | Driver | Refresh the phone | Vehicle reads **Dispatched** | FR-07, FR-18 |
| 5.8 | Admin | Close the dispatch: time in, odometer in **45680**, return status **Operational** | Dispatch closes | FR-16 |
| 5.9 | Admin | Open **Vehicles** | Status **Operational**, and mileage is now **45,680 km** | **FR-16 → FR-14** ⭐ |
| 5.10 | Narrator | — | *"Bringing the truck back updated its mileage by itself, and that mileage is what drives the maintenance reminders."* | FR-16 |

> ⭐ **Step 5.5 is worth pausing on.** It is the answer to *"what stops two
> administrators contradicting each other?"*

---

## ACT 6 — Oversight · 3 minutes

*Proves FR-19, FR-20 and NFR-05.*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 6.1 | Admin | Open the **Dashboard** | **8 live counts**: the four vehicle statuses, total vehicles, total drivers, expiring licences, pending damage reports | **FR-19** |
| 6.2 | Admin | Compare a count to the Vehicles page | They match exactly | FR-19, NFR-04 |
| 6.3 | Admin | **Reports** → pick a type, set a date range, generate | Only matching rows appear | **FR-20** |
| 6.4 | Narrator | — | *"Six report types: inspections, damage, repair and maintenance, preventive maintenance, dispatch, and vehicle status."* | FR-20 |
| 6.5 | Admin | Press **Ctrl + P** | Clean printable layout, stamped **Generated by: <your name>** and today's date | **FR-20** ⭐ |
| 6.6 | Admin | *(if time)* Open the dashboard in a second browser | Same layout and behaviour | NFR-05 |

---

## ACT 7 — When something goes wrong · 2 minutes

*Proves FR-21, FR-22 and the scheduled alerts.*

> **Narrator:** *"Two last things the agencies asked for: knowing when a licence
> is about to expire, and getting back in when someone forgets a password."*

| # | Who | Do this | ✅ Expected result | Proves |
|---|---|---|---|---|
| 7.1 | Admin | Run `php artisan rvms:license-alerts` | Bell count rises; a licence alert appears | **FR-08 → FR-21** |
| 7.2 | Admin | Open the bell | Unread items carry a blue dot and a tinted background | FR-21 |
| 7.3 | Admin | Click one, then **Mark all as read** | Count drops to zero | FR-21 |
| 7.4 | Admin | **Drivers** → key icon on Ramon → set a new password | Confirmation; the driver's sessions are ended | **FR-22** |
| 7.5 | Driver | On the phone, check **Alerts** | A **Password Reset** alert naming the administrator who did it | **FR-22 → FR-21** ⭐ |
| 7.6 | Driver | Log in with the new password | Works. The old password no longer does | FR-22 |

> ⭐ **Step 7.5 answers a question a panel will ask:** *"what stops an
> administrator quietly taking over an account?"* — **They cannot do it silently.
> The affected user is told, and by whom.**

---

# FUNCTIONAL COVERAGE CHECKLIST

Tick as you run it. All 22 requirements are covered by the seven acts.

| FR | Requirement | Act | ✓ |
|---|---|---|---|
| FR-01 | User Authentication | 1.1, 1.2, 1.6 | ☐ |
| FR-02 | Role-Based & Agency-Scoped Access | 1.4 ⭐ | ☐ |
| FR-03 | Driver Registration & Approval | 1 optional | ☐ |
| FR-04 | Self-Service Profile Management | 1.7 | ☐ |
| FR-05 | Vehicle Record Management | 2.1, 2.2 | ☐ |
| FR-06 | Driver Record Management | 2.3 | ☐ |
| FR-07 | Assigned Vehicle Viewing | 2.5, 5.7 | ☐ |
| FR-08 | License Expiry Monitoring | 2.3, 2.4, 7.1 | ☐ |
| FR-09 | Digital BLOWBAGETS Inspection | 3.1–3.4 ⭐ | ☐ |
| FR-10 | Inspection Monitoring | 3.5, 3.7, 3.9 | ☐ |
| FR-11 | Damage Reporting | 3.12, 3.14 | ☐ |
| FR-12 | Damage Report Management | 3.13, 3.15 | ☐ |
| FR-13 | Repair Logging | 4.1, 4.2 | ☐ |
| FR-14 | Preventive Maintenance Scheduling | 4.3–4.6, 5.9 | ☐ |
| FR-15 | Dispatch Logging | 5.1–5.4 | ☐ |
| FR-16 | Dispatch Closing & Return Status | 5.8, 5.9 | ☐ |
| FR-17 | Vehicle Availability Monitoring | 5.1 | ☐ |
| FR-18 | Vehicle Status Management | 3.10, 5.4, 5.5 ⭐ | ☐ |
| FR-19 | Dashboard Summary | 6.1, 6.2 | ☐ |
| FR-20 | Report Generation | 6.3, 6.5 | ☐ |
| FR-21 | Notification Services | 3.6, 7.1–7.3, 7.5 | ☐ |
| FR-22 | Password Recovery | 7.4–7.6 | ☐ |

| NFR | Quality attribute | Where | ✓ |
|---|---|---|---|
| NFR-01 | Performance | pages load without delay throughout | ☐ |
| NFR-02 | Security | 1.2, 1.4 — refusal and isolation | ☐ |
| NFR-03 | Usability | the driver completes the checklist unaided | ☐ |
| NFR-04 | Reliability | 3.10, 3.11, 6.2 — one status everywhere | ☐ |
| NFR-05 | Compatibility | 6.6 — second browser; the app on Android 8+ | ☐ |

---

# THE FIVE MOMENTS THAT MATTER

If time runs short, these five are the demo. Everything else is supporting.

| # | Moment | Step | Why it lands |
|---|---|---|---|
| 1 | **The agency wall** | 1.4 | BFP's fleet vanishes when CHO logs in |
| 2 | **Phone → web** | 3.5 | The driver's inspection appears on the admin's screen |
| 3 | **Web → phone** | 3.10 | The admin's decision appears on the driver's screen |
| 4 | **Dispatch owns the status** | 5.5 | The system refuses a contradiction and explains why |
| 5 | **Mileage updates itself** | 5.9 | Closing a dispatch feeds the maintenance reminders |

---

# SHORT VERSION — 8 minutes

When the slot is cut, run only: **1.1 → 1.4 → 3.1 → 3.4 → 3.5 → 3.9 → 3.10 →
5.3 → 5.4 → 5.5 → 6.1**

That still demonstrates FR-01, 02, 07, 09, 10, 15, 18, 19 and 21 — and all five
headline moments except the mileage update.

---

# IF SOMETHING FAILS MID-DEMO

1. **Do not debug in front of people.** Write down the step number and move on.
2. **Never apologise repeatedly.** One sentence: *"That one is not behaving —
   I will come back to it."* Then continue.
3. **If the phone loses the server:** the fix is in your tier runbook. Tier 3 is
   almost always `adb reverse tcp:8000 tcp:8000`.
4. **If the data is wrong** because a step went sideways, reset and restart the
   act rather than improvising.
5. **Afterwards:** every step you wrote down is a bug report. Bring them here.

---

## Notes

- Written against the seeded sample data. If a dedicated demo seeder is added
  later, the vehicle, mileages and thresholds above stay the same — that is the
  point of it.
- The manuscript's requirement wording is in `rvms-source-of-truth.md`; the
  audit that confirmed all 22 are implemented is in
  `rvms-audit-findings-2026-08.md`.
