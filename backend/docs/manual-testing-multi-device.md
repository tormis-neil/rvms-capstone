# Multi-device & concurrency test plan (R10 sub-task 8)

The R7–R9 guide tested the system **one person at a time**. That is not how four agencies use
it. This plan is the opposite: several people, several devices, several connections, all at
once — and it deliberately *attacks* the places where that goes wrong, rather than hoping it
doesn't.

Everything here has a guard in the code already. **The point is to prove the guard fires when
real humans race, not when a test does.** Two of them (`DispatchGuard`'s row locks, the
single-`fcm_token` limitation) have never been exercised by anything but automated tests.

---

## Who and what you need

| | |
|---|---|
| **People** | 3 minimum — 2 acting as administrators, 1 as a driver. 4 is better |
| **Devices** | 2 laptops (or 2 browsers with **separate profiles** — not two tabs, or they share a session), 2 Android phones |
| **Accounts** | `bfp.admin@rvms.local`, `bfp.admin2@rvms.local`, one CHO admin, 2 BFP drivers |
| **Server** | One machine running `php artisan serve --host=0.0.0.0`, with `queue:work` and `schedule:work` in their own terminals |
| **Network** | Phones on the same Wi-Fi as the server (not `adb reverse` — you need two phones at once) |

Before you start: `php artisan rvms:doctor` → read every warning. Then `php artisan migrate`.

> **Record as you go.** For every section: what happened, and whether it matched **Expected**.
> A "no" is more useful than a "roughly". Section 7 is a table to fill in.

---

## 1. The dispatch race — two admins, one vehicle

This is the headline test. `DispatchGuard` locks the vehicle and driver rows inside a
transaction for exactly this reason, and no human has ever raced it.

| # | Do this | Expected |
|---|---|---|
| 1.1 | Both admins open **Dispatch Logs** on separate laptops. Both open the New Dispatch modal for **the same Operational vehicle** | Both modals open — the vehicle still reads Operational to both |
| 1.2 | Count down out loud and both click **Dispatch** at the same instant | **Exactly one succeeds.** The other is refused with a message naming the mission, location and time out |
| 1.3 | Both refresh | One dispatch exists. The vehicle reads **Dispatched** |
| 1.4 | The losing admin tries again | Still refused, same message — the vehicle is genuinely out |
| 1.5 | Repeat 1.1–1.2 five times on different vehicles | Never two dispatches on one vehicle. **Two would be the most serious bug in the system** |
| 1.6 | Same race, but on **one driver** with two different vehicles | One succeeds; the other is refused naming the driver |

**Why it matters:** two open dispatches on one vehicle means closing either one writes a return
status while the other is still running — the vehicle reads Operational while it is still in the
field. That breaks FR-18 outright.

---

## 2. Two admins on the same record

| # | Do this | Expected |
|---|---|---|
| 2.1 | Both admins open the **same pending damage report**'s Review & Assess modal | Both open |
| 2.2 | Admin A reviews it → Not Operational. Admin B (whose screen is now stale) submits their own review | No crash. Note exactly what B sees, and what the final vehicle status is |
| 2.3 | Both refresh | Both show **the same** review status and **the same** vehicle status |
| 2.4 | Repeat with a pending **inspection** | Same |
| 2.5 | Admin A changes a vehicle's status while B has the Update Status modal open on that vehicle. B submits | The confirmation names the status B *thinks* is current. Note whether B is warned it moved |

> 2.2 and 2.5 have no lock by design — a second review is not destructive, it just overwrites.
> The test is that the outcome is **consistent**, not that it is prevented. If B silently
> overwrites A with no sign, write that down; it is a finding worth discussing, not a crash.

---

## 3. Notifications with several admins

| # | Do this | Expected |
|---|---|---|
| 3.1 | Driver files a damage report | **Both** BFP admins' bells go up by one |
| 3.2 | Admin A clicks **Mark All as Read**. Admin B refreshes | B's unread count is **unchanged** — A only cleared their own |
| 3.3 | Admin A clicks **Clear Read** and confirms. B refreshes | B's list is **intact**. This is the multi-admin rule that made Clear Read scoped to a user id |
| 3.4 | While B is reading their notifications page, A triggers three new alerts. B refreshes | B sees all three; nothing A did removed anything of B's |
| 3.5 | CHO admin watches throughout | Their bell never moves for a BFP event |

---

## 4. One driver, two phones

`users.fcm_token` is a single column — one device per driver. This is a **documented limitation**
(security audit T2/T3 area). The goal is to *demonstrate* it deliberately so nobody discovers it
in front of a panel.

| # | Do this | Expected |
|---|---|---|
| 4.1 | Sign the **same driver** into the app on phone A, then on phone B | Both show the same data |
| 4.2 | Admin changes that driver's vehicle status | **Only phone B** (the most recent sign-in) gets the banner |
| 4.3 | Open the Alerts tab on **phone A** | The notification **is there** — the stored row is per-user, not per-device. Only the push went to one phone |
| 4.4 | Sign out on phone B, then trigger another status change | Neither phone buzzes — sign-out clears the token by design |
| 4.5 | Sign in again on phone A, trigger another change | Phone A buzzes |

**Say this in the demo if asked:** the notification is never lost — only the banner goes to the
most recently registered handset. The agencies issue one phone per driver, so this matches how
they work.

---

## 5. Connectivity — the field conditions

Everything in §5 was fixed in R10 sub-task 1. This is where you prove it on real hardware.

| # | Do this | Expected |
|---|---|---|
| 5.1 | Airplane mode ON, then **cold start** the app (swipe it away first) | Opens. **No crash.** A clear "cannot reach the server" message |
| 5.2 | Airplane mode ON, app already open — background it, then return | **No crash**; the amber "Showing last loaded data" banner |
| 5.3 | Airplane mode ON, open **My Vehicle** | Says it cannot reach the server. **Never "No Vehicle Assigned"** — that would be a confident wrong answer |
| 5.4 | Airplane mode ON, open Inspections, Damage, Alerts | Same treatment on all three. None claims "you have no records" |
| 5.5 | Start an inspection, fill it in, turn airplane mode ON, submit | A clear failure message. The driver is **not** told it succeeded |
| 5.6 | Turn airplane mode OFF, submit again | Succeeds. Check the web: **exactly one** inspection, not two |
| 5.7 | Attach a photo to a damage report, turn airplane mode ON mid-upload, submit | Clear failure; retry after reconnecting works |
| 5.8 | Walk out of Wi-Fi range mid-session, come back, pull to refresh | Recovers without a restart |
| 5.9 | Airplane mode ON, then OFF. Trigger a status change from the web while the phone was offline | The banner arrives when the phone reconnects (FCM queues it) |

---

## 6. All four agencies at once

The isolation wall under real use rather than under a test.

| # | Do this | Expected |
|---|---|---|
| 6.1 | Sign in as BFP, PNP, CDRRMO and CHO admins on four browser profiles at the same time | All four work simultaneously |
| 6.2 | Each creates a vehicle at the same moment | Four vehicles, each in the right agency |
| 6.3 | Each opens **Reports → Vehicle Status Summary** simultaneously | Each sees **only their own** fleet |
| 6.4 | Each opens the dashboard | Four different sets of counts; no number includes another agency's records |
| 6.5 | BFP admin notes a PNP plate number from over someone's shoulder, then tries `/vehicles/<that id>` directly in the URL bar | **404.** Not 403 — 403 would confirm the record exists |
| 6.6 | Same trick on `/reports?type=inspections&vehicle_id=<foreign id>` | Refused with a validation error |
| 6.7 | With all four browsing, click around for two minutes | No page feels slow; nobody sees anybody else's data |

---

## 7. Report back

Fill this in — one line each is enough.

| Section | Result | Notes |
|---|---|---|
| 1. Dispatch race | ☐ pass ☐ fail | *How many times did you race it? Did two ever get through?* |
| 2. Same-record edits | ☐ pass ☐ fail | *What did the second admin see?* |
| 3. Multi-admin notifications | ☐ pass ☐ fail | |
| 4. Two phones | ☐ pass ☐ fail | *Confirm the row appeared on the phone that did NOT buzz* |
| 5. Connectivity | ☐ pass ☐ fail | *Any crash at all? Any "No Vehicle Assigned" while offline?* |
| 6. Four agencies | ☐ pass ☐ fail | |

Also worth capturing:

- **Any crash**, with what you were doing and a Logcat excerpt.
- **Any duplicate record** — two dispatches, two inspections from one submit, two identical alerts.
- **Anything slow** — which page, how many records it held.
- **Anything a second person saw that they should not have.**

---

## What this plan does not cover

Load testing beyond four concurrent users (the agencies have four administrators, not four
hundred), and the deployed server's own security — OS, MySQL exposure, TLS. Those belong to
deployment day; `rvms:doctor` covers the software half.
