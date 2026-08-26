# Defense Q&A Guide

> **What this is.** Every module explained the way it should be said out loud to
> a panel, plus the questions a panel is likely to ask and prepared answers to
> the hard ones. Written for the capstone defense, and usable as the script for
> the client walkthrough.
>
> **The one rule.** Every answer here is grounded in `INTERVIEW RESULTS.md` or in
> a decision that can be pointed at. A panel forgives *"we chose X because the
> BFP's form works that way."* It does not forgive *"it just seemed better."*
> Where a choice came from reasoning rather than from an interview, this document
> says so plainly — and so should the presenter. Inventing evidence is the one
> unrecoverable move.
>
> **Companion documents:** `INTERVIEW RESULTS.md` (the evidence),
> `rvms-source-of-truth.md` (the requirements), `rvms-demo-script.md` (the
> running order on the day).

---

## PART 1 — The modules

### Daily BLOWBAGETS Inspection · FR-09, FR-10

**The problem, from the interviews.** All four agencies inspect before
deployment, but *"the level of formality varies"*. Only the BFP has a form — a
paper **Motor Vehicle Daily Safety Checklist**. The PNP's drivers *"perform daily
BLOWBAGETS inspections"* with nothing recorded at station level. CDRRMO
*"conduct routine vehicle checks"*. CHO drivers *"perform practical inspections
while waiting for patient transport."* So three of four agencies had no record
that a vehicle was checked, and the one that did could not search it.

**Where the 14 items come from.** Straight off the BFP's own form, which the
interviews list item by item: Battery, Lights, Oil, Water, Brakes, Air, Gas,
Engine, Tires, Power Steering, Horn/Siren, Directional Signals, Hydraulic
System, Fire Pump.

The acronym itself accounts for ten — **B**attery, **L**ights, **O**il,
**W**ater, **B**rakes, **A**ir, **G**as, **E**ngine, **T**ires, **S**teering.
The BFP's form adds Horn/Siren and Directional Signals, giving the 12 every
agency sees, then Hydraulic System and Fire Pump, which only a fire truck has.

> Say it this way: *"We digitised the BFP's form, not the acronym. A CHO
> ambulance has no fire pump, so a CHO driver sees twelve items and a BFP driver
> sees fourteen."*

**The flow.**

```
DRIVER (phone)                        ADMIN (dashboard)
──────────────────────────            ──────────────────────────
New Inspection
  picks their vehicle
  marks each item OK / Has Issue
  remark REQUIRED on any flag
  Submit ─────────────────────────►   appears as a Pending row
                                      "N Pending Review" pill
                                 ◄──  View Checklist — what the
                                      driver actually sent
                                 ◄──  Review, and optionally set
                                      the vehicle's status
  My Vehicle shows the new  ◄──────   Reviewed
  status immediately
```

**Why each control exists.**

| Control | Basis |
|---|---|
| OK / Has Issue per item | The paper form is a per-item check; one pass/fail would lose which part failed |
| Remarks required on a flag | The GSO Motorpool finding, applied at source — they reported descriptions arriving incomplete |
| View Checklist | The admin judges what the driver *sent*, not a summary of it |
| Review + status in one action | A bad inspection should take a vehicle off the road in one step |
| Frequently Reported Issues | The paper ledger could not answer *"what keeps failing?"* |

**Likely question — *why can a driver not inspect a vehicle that is Not
Operational?*** Because BLOWBAGETS is a *pre-trip* check and a vehicle off the
road is not making a trip. Allowing it would store an all-OK checklist against a
vehicle the same system reports as broken — a contradiction in our own database.
The driver is refused with the reason and pointed at the damage report instead.

---

### Damage Reports · FR-11, FR-12

**The problem — the strongest evidence in the project.** It came from the **City
GSO Motorpool**, not from literature: damage descriptions submitted by agency
officers *are sometimes incomplete*, and their team *regularly discovers
additional defects during pre-inspection that were not in the original report*.
That is a documented failure in a real inter-office workflow, found in the field.

**The flow.** Driver files nature of damage, suspected parts, optional photo →
status Pending, date set automatically → admin reviews → status Reviewed, and
may set the vehicle's status → the driver's phone reflects it.

**Why each control exists.**

- **Nature of damage and suspected parts as separate fields** — the Motorpool
  needs both: what is wrong, and what the driver thinks caused it.
- **Placeholders with worked examples** — added after the adviser consultation,
  aimed squarely at the incompleteness finding.
- **Photo optional** — a driver in the field may have no signal or no battery,
  and a report without a photo beats no report.

**Likely question — *why can a driver file a second report on a vehicle that is
already broken?*** Because a vehicle is usually off the road *because* it is
damaged. If the first report closed the door, the second fault — the one the
Motorpool told us they keep finding — would be unreportable. This is the one
place a restriction was deliberately not added, and the reason is the interview.

---

### Repair Logs · FR-13

**The problem.** The BFP keeps repair history on a physical **Motor Vehicle
Ledger Card**. The PNP has a Preventive Maintenance System that covers *repair
record logging only* — nothing before or after it. Nobody could answer *"what has
this truck cost us this year?"*

**Why each control exists.**

- **Three repair sources** — Internal Office, GSO Motorpool, External Repair
  Shop — because those are the three routes the agencies actually use.
- **Shop name required for External** — the record has to say who did the work.
- **Supporting document** — required for an external shop, accepted from the
  others. Public money leaving the LGU needs evidence; the GSO issues a job order
  rather than a receipt, so one is accepted without being demanded.
- **Cost optional** — not always known when the repair is logged, and a log with
  a blank cost beats no log.

**Likely question — *how is the average cost computed?*** Total of the recorded
costs divided by the number of repairs *that carry a cost*. Cost is optional, so
dividing by every repair would imply the blank ones were free.

---

### Preventive Maintenance Schedules · FR-14

**The problem.** No agency had scheduling software. The PNP's tool logs repairs
after the fact; the CDRRMO's software is for emergency-response analytics, not
fleet maintenance. Timing lived in people's heads.

**The intervals ARE from the interviews.** This is worth knowing precisely,
because it is the likeliest PM question and the evidence is strong:

| Agency | What they told us |
|---|---|
| PNP | Oil change every **3,000–5,000 km** |
| CHO | Mileage-based; common interval **every 5,000 km** |
| CDRRMO | Regular preventive maintenance schedules are followed |
| BFP | **No uniform mileage standard for all vehicles**; ambulances coordinate with LGU and CDRRMO personnel |

So the seeded 5,000 km is the CHO's stated common interval and sits inside the
PNP's stated range.

> **The BFP line is the important one.** *"No uniform mileage standard for all
> vehicles"* is precisely why the interval is a field the administrator fills in
> per schedule rather than a constant in the code. The variation the interviews
> found is the reason the system is configurable. Say that and the question
> answers itself.

**Why two types.** A CHO ambulance in constant use wears out its oil long before
a year passes — **mileage-based**. A CDRRMO rescue truck that sits in the
compound for months still has brake fluid absorbing moisture — **time-based**.
One rule cannot cover both.

**The four statuses.** Three are calculated and cannot be typed; only
**Completed** is set by hand.

```
MILEAGE-BASED                          TIME-BASED
mileage ≥ due            → Due         today ≥ due date          → Due
mileage ≥ due − window   → Due Soon    today ≥ due date − window → Due Soon
otherwise                → Upcoming    otherwise                 → Upcoming
```

There is no fifth status. A schedule sits at **Due** indefinitely until somebody
records the service — a maintenance job does not stop being needed because time
passed.

**Likely question — *is "Under Preventive Maintenance" the same as a schedule
being Due?*** No, and this catches people. Two different fields: the **schedule**
has Upcoming / Due Soon / Due / Completed; the **vehicle** has Operational /
Dispatched / Not Operational / Under Preventive Maintenance. They are not linked
automatically — the administrator sets the vehicle status when it goes in for
service and again when it comes back, which is what the approved workflow
specifies.

---

### Dispatch Logs & Availability · FR-15, FR-16, FR-17

**The problem.** Availability monitoring was the least systematic thing found.
The BFP *texts* vehicle status to the LGU after each maintenance event. CDRRMO
personnel track it through *group chats and manual vehicle labelling*. CHO staff
confirm readiness *by sight*, because all three ambulances share one compound.
None of that survives a busy night.

**The flow.** Opening records vehicle, driver, mission, location, time out and an
optional odometer reading, and sets the vehicle to **Dispatched** automatically.
Closing records time in, the return status and an optional odometer reading; a
time-in reading higher than the stored mileage updates the vehicle.

**Two things to volunteer unprompted.**

- **Dispatched is owned by this module alone.** No other screen can set or clear
  it. A vehicle out on a mission cannot be silently re-statused from Repair Logs
  — the attempt is refused with the mission named.
- **Closing a dispatch keeps maintenance honest.** The time-in odometer feeds the
  vehicle's mileage, which feeds mileage-based PM. Nobody has to remember.

**Likely question — *why can you dispatch a driver who is not assigned to that
vehicle?*** Because the interviews say so. CHO *"uses a rotating driver
schedule"* with *"two primary drivers, 24/7 shifting"* and — explicitly —
*"drivers may operate multiple vehicles when necessary."* PNP assigns *"according
to operational requirements."* Blocking a non-primary pairing would break a
workflow taken directly from the interviews. The form says the pairing is not the
primary one and says it is allowed: informed, not prevented.

**Likely question — *why does an expired licence not stop a dispatch?*** These
are emergency vehicles. Refusing to dispatch during a fire or a medical call
would be worse than the paperwork problem it solves, and a system that does it
gets worked around within a week. FR-08 says the system *alerts* — not that it
withholds a vehicle. The administrator is shown the expiry and must confirm
explicitly, and the decision is recorded as theirs.

---

### Vehicle Management · FR-05, FR-18

**The problem.** All four agencies keep vehicle records, driver assignments and
licence dates in separate paper records that are not consolidated or searchable.

**The four statuses.** Operational · Dispatched · Not Operational · Under
Preventive Maintenance. **One field, one truth** — written from every module
through a single gate, so the vehicle row, the availability list, the dashboard
card and the printed report can never disagree.

**Likely question — *the agencies named six statuses. Why do you have four?***
The interviews recorded Operational, Deployed, Under Repair, Under Preventive
Maintenance, Serviceable and Unserviceable — different vocabularies for
overlapping states. Four agencies share one availability view, so agency-specific
labels would make it unreadable. *Deployed* is our **Dispatched**; *Under Repair*
is **Not Operational**; the BFP's *Serviceable / Unserviceable* are that agency's
terms for the same two poles. The scope records this explicitly.

**Likely question — *why can't a vehicle be deleted?*** Every inspection, damage
report, repair, PM schedule and dispatch points at a vehicle. Deleting one would
break the history the system exists to keep. A vehicle leaving service is
recorded through its status; a driver leaving is recorded by reassigning their
vehicles. This is a deliberate revision — deletion was removed after
re-examining it against the objectives.

---

### Drivers & Licence Monitoring · FR-06, FR-08

**How the status is decided.** Nothing is stored. It is derived every time it is
shown, from the expiry date and the agency's warning window:

```
expiry < today             → Expired
expiry ≤ today + window    → Expiring Soon
otherwise                  → Valid
```

The window is stored per agency and defaults to 30 days. Note the boundary: a
licence is **Expiring Soon on its own expiry date** and Expired the day after —
because a licence is valid *through* the date printed on it. A PM due date works
the opposite way, because *"due by"* and *"valid until"* mean different things.

**When it fires.** Daily at 06:00 to every administrator of the agency;
immediately whenever a licence is recorded that is already inside the window or
already expired; and **once per licence, not daily** — keyed on the driver and
that expiry date, so a renewal starts a fresh cycle.

**Likely question — *where does 30 days come from?*** Be straight: **the
interviews did not establish it.** They established that licence dates sit in
paper records that are not consolidated or searchable — that is the problem, not
the warning period. Do not dress a chosen default as a finding.

What is defensible is the reasoning. A month is the natural planning unit for an
office; it is long enough to notice, tell the driver and let them book an LTO
appointment before the licence lapses; and it is short enough that the list stays
actionable — set the window to six months and half the drivers are permanently
"Expiring Soon", at which point the flag means nothing and people ignore it.

Then offer the improvement before it is asked for: *"We are running UAT with all
four agencies and asking each how long a renewal actually takes them. If the
answer is consistently longer than thirty days, the deployed value changes — it
is one number per agency, stored in the database rather than written into the
code, precisely so that answer can be acted on."*

**Likely question — *why is there no screen to change it?*** There was, briefly,
and it was removed: nobody needed to change the number, and a setting nobody uses
is one more thing to explain for no return. What was genuinely missing was the
opposite — the *rule* was invisible, so an administrator seeing "Expiring Soon"
had no way to know what it meant. The Drivers page now states it in plain
language. The value is still per agency, so a deployment can still differ.

**The follow-up you will get — *then how do you actually change it?*** One
command at the server:

```
php artisan rvms:license-window              # shows all four agencies
php artisan rvms:license-window CDRRMO 45    # sets one, after confirming
```

It refuses anything outside 1–365 days, names the old and new window before
writing, and asks for confirmation. `php artisan rvms:doctor` prints all four
windows at handover so a mismatch is caught then rather than months later.

**Be honest if pressed on why this is a command and not a page.** The same
reasoning as `rvms:create-admin` and `rvms:reset-password`: it is deployment
configuration, changed once and by the person installing the system, not daily
work for an administrator. Putting it on a screen makes it reachable from the
internet and fat-fingerable during a demo, for a setting that should change
roughly never.

**And if a panelist finds the honest weakness** — *"so an agency cannot change
it themselves"* — agree, and say what it would take: it is a single column with
a single writer, so exposing it later is a form and a validation rule, not a
redesign. The reason it is not exposed today is that no agency asked for it,
and building an unused setting is how a system accumulates screens nobody
maintains. That is a defensible engineering judgement, not an oversight, and
saying so plainly is better than pretending the command is a feature.

---

### Reports · FR-20

**The problem.** Records existed on paper but could not be consolidated or
retrieved. Six report types, each filtered, each printable, each stamped with the
administrator who generated it and the date.

**Why the visuals differ from each other.** Because the data has different
shapes. A **ranking** answers *"which of these happens most often?"* — ordered
bars. A **composition** answers *"how does the whole divide up?"* — one stacked
bar totalling 100%. Using ranked bars for both was an earlier mistake of ours,
corrected after the adviser's feedback.

**Worth volunteering.** Every figure in a report summary is computed from the
*same records* that produce the table beneath it — never a second query. So a
filtered report cannot print a total the table visibly contradicts. On a document
carrying the administrator's name, that is not a cosmetic concern.

---

## PART 2 — The account questions

### How is an administrator account created?

**By a command run on the server** — `php artisan rvms:create-admin` — not from
any screen.

That is deliberate. An administrator sees every record in their agency, so the
account cannot be self-serve. A screen for creating one would be a way to mint an
administrator *from the internet*; a command requires someone already standing at
the server. Same reasoning as the password-recovery command.

If pushed: *"Before the command existed, 'provisioned' effectively meant 'created
by the installer and never again' — an agency appointing a second officer next
year had no supported path, and the workaround people reach for erases the
database. The command is what makes the design decision actually true."*

### Why does a driver need approval to create an account?

Because the sign-up screen is open, and **the system does not know who your
drivers are — the administrator does.** Without approval, anyone who reached the
app could create an account and see an agency's vehicles, assignments and
schedules.

A self-registered driver starts *pending*: they cannot sign in, and they appear
in an Access Requests list where their own agency's administrator approves or
rejects them. It is the cheapest possible identity check — the person who knows
the answer is the person asked.

And say why self-registration exists at all: it saves the administrator typing
every driver's details, while keeping the decision with them.

---

## PART 3 — Questions to expect

**"Fleet management software already exists. Why build this?"**
Commercial fleet software solves the problem one organisation has with its own
vehicles. This is four *independent* government agencies sharing one platform
where each administrator sees only their own records. No product has to solve
that, because no product's customer is four agencies who must not see each
other's data. Then add the two domain specifics: a BLOWBAGETS checklist taken
from the BFP's own form, and a GSO Motorpool workflow no generic product models.

**"Show me that one agency cannot see another's data."**
Demonstrate rather than explain. Sign in as BFP, show the vehicles; sign in as
CHO, show that none of them appear. Then: every scoped query carries the agency
filter automatically, and requesting another agency's record by id returns *not
found* rather than *forbidden* — because "forbidden" would itself confirm the
record exists.

**"What happens when two administrators do the same thing at once?"**
An agency can have several administrators, so this will happen. Opening a
dispatch on the same vehicle twice is prevented — the second is refused by name.
The check runs twice: once for the friendly message, and again inside the
transaction with the rows locked, because two people can pass validation in the
same instant before either saves.

**"Why is a driver a user account rather than a separate table?"**
A driver must sign in to the mobile app, so a driver record and a login account
are one-to-one. Splitting them would duplicate name, email and agency across two
tables and create the possibility of them disagreeing. Administrators are the
same table with a different role and no licence fields.

**"Why no GPS tracking?"**
Answer with scope, not apology. Explicitly excluded — along with IoT diagnostics,
telematics, route optimisation and automated dispatch assignment. The system
digitises the agencies' *records*; odometer readings are typed by the
administrator from the vehicle's own odometer. GPS would require hardware in
every vehicle and a budget none of these offices has. It belongs in future
enhancements, and it should be listed there.

**"What if the internet goes down?"**
Both platforms require connectivity, and the scope says so. The honest version: a
driver with no signal sees *"cannot reach the server"* with a retry — not an
empty list, because a confident wrong answer is worse than an error. Offline
capture with later synchronisation is a genuine future enhancement, and saying so
is stronger than pretending it is handled.

**"How do you know it works?"**
Give the number — automated tests covering the requirements, the agency isolation
rules and the boundary conditions, run on every change. Then the better answer:
automated tests prove the *rules*, and user acceptance testing with the agencies
proves the *workflow*, which is why UAT is scheduled rather than assumed.

**"Who maintains this after you graduate?"**
Do not bluff. The deployment needs one scheduled task and nothing else running —
no mail server, no background worker — precisely because these offices have no IT
department. `rvms:doctor` checks a deployment is complete, and the handover
documentation is written. Long-term maintenance is an institutional decision, and
naming that honestly beats promising support that cannot be given.

---

## PART 4 — The traps

Questions where the honest answer is a limitation. Panels ask these to see
whether the proponents know their own system's edges. Rehearsed honesty scores
far better than improvised defensiveness.

| They ask | Say |
|---|---|
| *"What does your system NOT do?"* | Name three without hesitating: no GPS or telematics; no offline capture; no COA-formatted government forms. All three are written exclusions in the scope, not discoveries. |
| *"One driver, two phones?"* | Only the most recent device receives push notifications — one device token per driver. Known, documented, demonstrable. The stored alert still reaches them in the app. |
| *"Show me a vehicle's status history."* | Cannot. The status carries the reason for the *current* change and where it came from, not a change log. Say it plainly and put it in future enhancements — do not imply an audit trail that does not exist. |
| *"What if an administrator forgets their password?"* | Another administrator of the same agency cannot reset it — that was deliberately removed. A recovery command is run on the server. With a single administrator, that command is the path. |
| *"What was the hardest part?"* | A real answer beats a modest one: reconciling four agencies with genuinely different practices into one workflow — one keeps a formal checklist, one confirms readiness by sight — without building four systems. |
| *"What would you do differently?"* | Have one honest answer ready. Freezing the requirements earlier is a good one, and it is true. |

---

## Three habits for the room

1. **Answer from the interviews first, the code second.** *"The GSO Motorpool
   told us…"* outranks *"we implemented…"*.
2. **When a decision was reasoning rather than research, say so.** *"No
   requirement demanded it; we chose it because…"* is a strong sentence.
   Pretending otherwise is the one thing a panel will catch.
3. **If you do not know, say you will check.** Inventing an answer about your own
   system is the only unrecoverable move.
