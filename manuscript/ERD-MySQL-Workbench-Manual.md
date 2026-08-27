# How to Build the RVMS ERD in MySQL Workbench

**A step-by-step manual.** Follow it in order and you will end up with the exact
diagram in Figure 6 — 11 tables, 131 columns, 23 relationships — drawn by
MySQL Workbench itself.

> **Written for someone who has never used MySQL Workbench.** Every step says
> what to click and what you should see afterwards. If what you see does not
> match, stop and check [Troubleshooting](#troubleshooting) rather than
> carrying on.

---

## Before you start — which route do you want?

There are two ways to get this diagram. **Both give the same result.** Pick one.

| | **Route A — Import** | **Route B — Draw it yourself** |
|---|---|---|
| **Time** | About 10 minutes | About 2–3 hours |
| **How** | Run `rvms-erd.sql`, let Workbench draw it | Create all 11 tables by hand |
| **You learn** | Not much | How the database is actually put together |
| **Risk of typos** | None | Real — 131 columns to type |

**My honest recommendation:** do **Route A first** (10 minutes), so you have a
working diagram safely saved. Then, if you want to be able to say you built it
yourself — or if your panel asks you to demonstrate — do Route B with Route A's
result open beside you as the answer key.

- **Route A** is [Part 7](#route-a) at the end of this document.
- **Route B** is everything from Part 1 to Part 6.

---

## What you need

- [ ] **MySQL Workbench** installed *(free: dev.mysql.com/downloads/workbench)*
- [ ] The file **`rvms-erd.sql`** *(only for Route A, or as your answer key)*
- [ ] About 2–3 hours if you are drawing it by hand
- [ ] Figure 6 (the ERD image) open on screen or printed, to check against

> **You do NOT need MySQL running for Route B.** Drawing a model in Workbench is
> just drawing — it never touches a real database until you tell it to.

---

<a name="part1"></a>
## PART 1 — Create the model file

### Step 1.1 — Open MySQL Workbench

Launch it. You will see the home screen with a list of connections.

**Ignore the connections.** You are not connecting to anything.

---

### Step 1.2 — Create a new model

Click **File → New Model** (or press **Ctrl + N**).

**You should see:** a new tab named *MySQL Model*, with a section called
**Physical Schemas** and a default schema named `mydb`.

---

### Step 1.3 — Rename the schema

1. **Double-click** the `mydb` box under *Physical Schemas*
2. In the panel that opens at the bottom, change **Name** to:

   ```
   rvms
   ```

3. Press **Enter**

**You should see:** the box now reads `rvms`.

---

### Step 1.4 — Save immediately

**File → Save Model As…** and name it `rvms-erd.mwb`.

> ⚠️ **Press Ctrl + S every 10 minutes from now on.** Workbench does not
> auto-save, and losing an hour of typing is a miserable way to spend an evening.

---

### Step 1.5 — Set the notation to Crow's Foot

This is the notation used in Figure 6, so set it **now**, before you draw
anything.

1. Click the **Model** menu
2. **Model → Relationship Notation → Crow's Foot (IE)**
3. Click **Model** again
4. **Model → Object Notation → Workbench (Simplified)**

**Why:** *Crow's Foot* gives you the three-pronged "many" symbol. *Workbench
(Simplified)* makes each table box list its columns with their data types —
which is what Figure 6 shows.

---

### Step 1.6 — Open the diagram canvas

Scroll down in the *MySQL Model* tab to the **EER Diagrams** section and
**double-click** the **Add Diagram** icon.

**You should see:** a large blank white canvas with a vertical toolbar on the
left. This is where the diagram gets drawn.

**Find these three tools on the left toolbar** — you will use them constantly:

| Icon | Name | Shortcut | What it does |
|---|---|---|---|
| ➤ (arrow) | Pointer | **Esc** | Select and move things |
| ▦ (table) | Place a New Table | **T** | Creates a table |
| ✋ (hand) | Hand tool | **H** | Drag the canvas around |

> 💡 **The single most useful key: `Esc`.** After placing a table, Workbench
> stays in "table mode" and will keep dropping new tables every time you click.
> Press **Esc** to go back to the pointer.

---

<a name="part2"></a>
## PART 2 — How to create ONE table (learn this once)

Every one of the 11 tables is made the same way. Read this part carefully once,
then Part 3 is just repetition.

We will build `agencies` as the worked example.

---

### Step 2.1 — Place the table

1. Press **T** (or click the table icon)
2. **Click once** on the canvas, near the top-left

**You should see:** a small box named `table1`.

3. Press **Esc** so you don't accidentally create more.

---

### Step 2.2 — Open the table editor

**Double-click** the new box.

**You should see:** an editor panel open at the **bottom** of the window, with
tabs along its bottom edge: **Table · Columns · Indexes · Foreign Keys · Triggers
· Partitioning · Options**.

> 💡 **The editor panel is small by default.** Drag its top edge upward to make
> it taller — you will be living in it for the next two hours.

---

### Step 2.3 — Name the table

In the **Table** tab, set **Name** to:

```
agencies
```

> ⚠️ **Type the name exactly as written in this manual** — all lowercase, with
> underscores, no spaces. `agencies`, not `Agencies`. `inspection_checklist_items`,
> not `Inspection Checklist Items`. The names must match the real database.

---

### Step 2.4 — Add the columns

Click the **Columns** tab.

**You should see:** a grid with these column headings:

```
Column Name | Datatype | PK | NN | UQ | B | UN | ZF | AI | G | Default/Expression
```

Here is what the tick-boxes mean. **You only ever need four of them:**

| Box | Full name | Tick it when… |
|---|---|---|
| **PK** | Primary Key | it's the `id` column |
| **NN** | Not Null | the field is required |
| **UN** | Unsigned | the number can never be negative |
| **AI** | Auto Increment | it's the `id` column |

*(Ignore UQ, B, ZF, G entirely — this design never uses them.)*

**To add a column:**

1. Double-click the empty row under **Column Name**
2. Type the name, press **Tab**
3. Type the datatype, press **Enter**
4. Tick the boxes the spec table tells you to
5. A fresh empty row appears — repeat

**For `agencies`, enter these ten columns:**

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `code` | `VARCHAR(10)` | | ✔ | | | — |
| 3 | `name` | `VARCHAR(255)` | | ✔ | | | — |
| 4 | `location` | `VARCHAR(255)` | | | | | — |
| 5 | `contact_number` | `VARCHAR(50)` | | | | | — |
| 6 | `email` | `VARCHAR(255)` | | | | | — |
| 7 | `logo_path` | `VARCHAR(255)` | | | | | — |
| 8 | `license_expiry_warning_days` | `SMALLINT` | | ✔ | ✔ | | 30 |
| 9 | `created_at` | `TIMESTAMP` | | | | | — |
| 10 | `updated_at` | `TIMESTAMP` | | | | | — |

**You should see:** the box on the canvas grows, with a 🔑 key icon beside `id`.

✅ **That is one table done.** All eleven work exactly like this.

---

### Step 2.5 — A few typing rules that will save you pain

**ENUM columns — type the whole thing, exactly.** When a spec says:

```
ENUM('admin','driver')
```

type all of that into the Datatype box, including the brackets and the single
quotes. Do not put spaces after the commas.

**The four longest ENUMs in this design** — copy these carefully:

```
ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance')

ENUM('Internal Office','GSO Motorpool','External Repair Shop')

ENUM('Fire Response','Medical Response','Rescue Operation','Patrol','Administrative Travel','Others')

ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report','Inspection_Flagged','License_Expiring','License_Expired','PM_Due_Soon','PM_Due','New_Access_Request','Password_Reset')
```

**Default values.** Where the spec shows a default, type it into the
**Default/Expression** box:
- a number → type it plainly: `30`, `0`
- text → type it **with** the quotes: `'active'`, `'Pending'`, `'Operational'`
- a dash `—` → leave the box empty

**`created_at` and `updated_at`.** Every table ends with these two. They are
always `TIMESTAMP`, and **nothing is ticked** — no PK, no NN, no UN, no AI.

---

<a name="part3"></a>
## PART 3 — Create all 11 tables

> 🚨 **THE ORDER MATTERS. Do not skip ahead.**
>
> A table can only point at a table that already exists. If you try to build
> `inspections` before `vehicles` exists, the Referenced Table dropdown will be
> empty and you will think Workbench is broken.
>
> **The order below is already correct. Just work down the list.**

Place the tables on the canvas roughly where Figure 6 has them — the top row
first, the bottom row after. Don't fuss over the positions yet; Part 5 tidies
everything up.

For each table: place it (**T**, click, **Esc**), double-click it, name it, then
type its columns. **Foreign keys come later, in Part 4** — ignore the FK column
for now and just create the columns as ordinary `BIGINT` fields.

> `agencies` is listed again below so the list is complete. If you already built
> it in Part 2, tick it off and start at `users`.

### Table 1 of 11 — `agencies`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `code` | `VARCHAR(10)` |  | ✔ |  |  | — |
| 3 | `name` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 4 | `location` | `VARCHAR(255)` |  |  |  |  | — |
| 5 | `contact_number` | `VARCHAR(50)` |  |  |  |  | — |
| 6 | `email` | `VARCHAR(255)` |  |  |  |  | — |
| 7 | `logo_path` | `VARCHAR(255)` |  |  |  |  | — |
| 8 | `license_expiry_warning_days` | `SMALLINT` |  | ✔ | ✔ |  | 30 |
| 9 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 10 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

### Table 2 of 11 — `users`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `role` | `ENUM('admin','driver')` |  | ✔ |  |  | — |
| 4 | `name` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 5 | `email` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 6 | `email_verified_at` | `TIMESTAMP` |  |  |  |  | — |
| 7 | `password` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 8 | `status` | `ENUM('pending','active','rejected')` |  | ✔ |  |  | 'active' |
| 9 | `license_number` | `VARCHAR(50)` |  |  |  |  | — |
| 10 | `license_expiry_date` | `DATE` |  |  |  |  | — |
| 11 | `fcm_token` | `VARCHAR(255)` |  |  |  |  | — |
| 12 | `remember_token` | `VARCHAR(100)` |  |  |  |  | — |
| 13 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 14 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_users_agency_id` | `agency_id` | `agencies` | `id` | NO ACTION |

### Table 3 of 11 — `vehicles`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `assigned_driver_id` | `BIGINT` |  |  | ✔ |  | — |
| 4 | `type` | `VARCHAR(100)` |  | ✔ |  |  | — |
| 5 | `plate_number` | `VARCHAR(20)` |  | ✔ |  |  | — |
| 6 | `make` | `VARCHAR(100)` |  | ✔ |  |  | — |
| 7 | `model` | `VARCHAR(100)` |  | ✔ |  |  | — |
| 8 | `engine_number` | `VARCHAR(50)` |  |  |  |  | — |
| 9 | `chassis_number` | `VARCHAR(50)` |  |  |  |  | — |
| 10 | `current_mileage` | `INT` |  | ✔ | ✔ |  | 0 |
| 11 | `status` | `ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance')` |  | ✔ |  |  | 'Operational' |
| 12 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 13 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_vehicles_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_vehicles_assigned_driver_id` | `assigned_driver_id` | `users` | `id` | SET NULL |

### Table 4 of 11 — `inspection_checklist_items`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `name` | `VARCHAR(100)` |  | ✔ |  |  | — |
| 3 | `is_bfp_only` | `TINYINT(1)` |  | ✔ |  |  | 0 |
| 4 | `sort_order` | `SMALLINT` |  | ✔ | ✔ |  | 0 |
| 5 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 6 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

### Table 5 of 11 — `inspections`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `vehicle_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `driver_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 5 | `inspection_date` | `DATE` |  | ✔ |  |  | — |
| 6 | `review_status` | `ENUM('Pending','Reviewed')` |  | ✔ |  |  | 'Pending' |
| 7 | `reviewed_by` | `BIGINT` |  |  | ✔ |  | — |
| 8 | `reviewed_at` | `DATETIME` |  |  |  |  | — |
| 9 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 10 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_inspections_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_inspections_vehicle_id` | `vehicle_id` | `vehicles` | `id` | CASCADE |
| `fk_inspections_driver_id` | `driver_id` | `users` | `id` | CASCADE |
| `fk_inspections_reviewed_by` | `reviewed_by` | `users` | `id` | SET NULL |

### Table 6 of 11 — `inspection_items`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `inspection_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `checklist_item_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `status` | `ENUM('OK','Has Issue')` |  | ✔ |  |  | — |
| 5 | `remarks` | `TEXT` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_inspection_items_inspection_id` | `inspection_id` | `inspections` | `id` | CASCADE |
| `fk_inspection_items_checklist_item_id` | `checklist_item_id` | `inspection_checklist_items` | `id` | CASCADE |

### Table 7 of 11 — `damage_reports`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `vehicle_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `driver_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 5 | `nature_of_damage` | `TEXT` |  | ✔ |  |  | — |
| 6 | `suspected_parts` | `VARCHAR(255)` |  |  |  |  | — |
| 7 | `photo_path` | `VARCHAR(255)` |  |  |  |  | — |
| 8 | `date_reported` | `DATE` |  | ✔ |  |  | — |
| 9 | `status` | `ENUM('Pending','Reviewed')` |  | ✔ |  |  | 'Pending' |
| 10 | `reviewed_by` | `BIGINT` |  |  | ✔ |  | — |
| 11 | `reviewed_at` | `DATETIME` |  |  |  |  | — |
| 12 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 13 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_damage_reports_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_damage_reports_vehicle_id` | `vehicle_id` | `vehicles` | `id` | CASCADE |
| `fk_damage_reports_driver_id` | `driver_id` | `users` | `id` | CASCADE |
| `fk_damage_reports_reviewed_by` | `reviewed_by` | `users` | `id` | SET NULL |

### Table 8 of 11 — `repair_logs`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `vehicle_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `driver_id` | `BIGINT` |  |  | ✔ |  | — |
| 5 | `repair_date` | `DATE` |  | ✔ |  |  | — |
| 6 | `scope_of_work` | `TEXT` |  | ✔ |  |  | — |
| 7 | `parts_replaced` | `TEXT` |  |  |  |  | — |
| 8 | `cost` | `DECIMAL(10,2)` |  |  |  |  | — |
| 9 | `repair_source` | `ENUM('Internal Office','GSO Motorpool','External Repair Shop')` |  | ✔ |  |  | — |
| 10 | `external_shop_name` | `VARCHAR(255)` |  |  |  |  | — |
| 11 | `receipt_path` | `VARCHAR(255)` |  |  |  |  | — |
| 12 | `remarks` | `TEXT` |  |  |  |  | — |
| 13 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 14 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_repair_logs_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_repair_logs_vehicle_id` | `vehicle_id` | `vehicles` | `id` | CASCADE |
| `fk_repair_logs_driver_id` | `driver_id` | `users` | `id` | SET NULL |

### Table 9 of 11 — `pm_schedules`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `vehicle_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `service_target` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 5 | `pm_type` | `ENUM('Mileage-Based','Time-Based')` |  | ✔ |  |  | — |
| 6 | `interval_km` | `INT` |  |  | ✔ |  | — |
| 7 | `last_pm_mileage` | `INT` |  |  | ✔ |  | — |
| 8 | `due_mileage` | `INT` |  |  | ✔ |  | — |
| 9 | `due_date` | `DATE` |  |  |  |  | — |
| 10 | `due_soon_threshold_km` | `INT` |  |  | ✔ |  | — |
| 11 | `due_soon_threshold_days` | `SMALLINT` |  |  | ✔ |  | — |
| 12 | `status` | `ENUM('Upcoming','Due Soon','Due','Completed')` |  | ✔ |  |  | 'Upcoming' |
| 13 | `date_serviced` | `DATE` |  |  |  |  | — |
| 14 | `completion_mileage` | `INT` |  |  | ✔ |  | — |
| 15 | `completion_repair_source` | `ENUM('Internal Office','GSO Motorpool','External Repair Shop')` |  |  |  |  | — |
| 16 | `completion_external_shop_name` | `VARCHAR(255)` |  |  |  |  | — |
| 17 | `completion_receipt_path` | `VARCHAR(255)` |  |  |  |  | — |
| 18 | `completion_parts_replaced` | `TEXT` |  |  |  |  | — |
| 19 | `completion_remarks` | `TEXT` |  |  |  |  | — |
| 20 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 21 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_pm_schedules_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_pm_schedules_vehicle_id` | `vehicle_id` | `vehicles` | `id` | CASCADE |

### Table 10 of 11 — `dispatches`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `vehicle_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `driver_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 5 | `mission_type` | `ENUM('Fire Response','Medical Response','Rescue Operation', 'Patrol','Administrative Travel','Others')` |  | ✔ |  |  | — |
| 6 | `mission_other` | `VARCHAR(255)` |  |  |  |  | — |
| 7 | `location` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 8 | `time_out` | `DATETIME` |  | ✔ |  |  | — |
| 9 | `odometer_out` | `INT` |  |  | ✔ |  | — |
| 10 | `time_in` | `DATETIME` |  |  |  |  | — |
| 11 | `odometer_in` | `INT` |  |  | ✔ |  | — |
| 12 | `return_status` | `ENUM('Operational','Not Operational','Under Preventive Maintenance')` |  |  |  |  | — |
| 13 | `remarks` | `TEXT` |  |  |  |  | — |
| 14 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 15 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_dispatches_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_dispatches_vehicle_id` | `vehicle_id` | `vehicles` | `id` | CASCADE |
| `fk_dispatches_driver_id` | `driver_id` | `users` | `id` | CASCADE |

### Table 11 of 11 — `notifications`

| # | Column Name | Datatype | PK | NN | UN | AI | Default |
|---|---|---|---|---|---|---|---|
| 1 | `id` | `BIGINT` | ✔ | ✔ | ✔ | ✔ | — |
| 2 | `agency_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 3 | `user_id` | `BIGINT` |  | ✔ | ✔ |  | — |
| 4 | `type` | `ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report', 'Inspection_Flagged','License_Expiring','License_Expired', 'PM_Due_Soon','PM_Due','New_Access_Request','Password_Reset')` |  | ✔ |  |  | — |
| 5 | `title` | `VARCHAR(255)` |  | ✔ |  |  | — |
| 6 | `message` | `TEXT` |  | ✔ |  |  | — |
| 7 | `data` | `JSON` |  |  |  |  | — |
| 8 | `is_read` | `TINYINT(1)` |  | ✔ |  |  | 0 |
| 9 | `read_at` | `DATETIME` |  |  |  |  | — |
| 10 | `created_at` | `TIMESTAMP` |  |  |  |  | — |
| 11 | `updated_at` | `TIMESTAMP` |  |  |  |  | — |

**Foreign keys for this table** (Foreign Keys tab):

| FK Name | Column | Referenced Table | Referenced Column | On Delete |
|---|---|---|---|---|
| `fk_notifications_agency_id` | `agency_id` | `agencies` | `id` | CASCADE |
| `fk_notifications_user_id` | `user_id` | `users` | `id` | CASCADE |
---

### ✅ Checkpoint — end of Part 3

Before going further, count what is on your canvas.

- [ ] **11 table boxes**, all named in lowercase
- [ ] Every box has a 🔑 key icon next to `id`
- [ ] **No relationship lines yet** — that is correct, they come next

**Press Ctrl + S.**

---

<a name="part4"></a>
## PART 4 — Draw the 23 relationships

Here is the good news: **you do not draw the lines.** You tell Workbench which
column points at which table, and it draws the line for you, with the correct
crow's-foot symbols on both ends.

### Step 4.1 — How to add one foreign key

We will do `users.agency_id → agencies.id` as the worked example.

1. **Double-click** the `users` table
2. Click the **Foreign Keys** tab
3. Double-click the empty row under **Foreign Key Name** and type:

   ```
   fk_users_agency_id
   ```

4. In the same row, click the **Referenced Table** cell and choose `agencies`
   from the dropdown
5. The middle panel now lists every column in `users`. **Tick `agency_id`.**
6. In the right-hand panel, set **Referenced Column** to `id`
7. At the bottom right, set **On Delete** to what the spec says
   *(leave **On Update** as `NO ACTION` everywhere)*

**You should see:** a line appear on the canvas, joining `users` to `agencies`,
with a single bar at the `agencies` end and a crow's foot at the `users` end.

✅ **That is one relationship.** There are 23. They all work exactly like this.

---

### Step 4.2 — Identifying or non-identifying?

If Workbench asks, or if you use the relationship tools on the left toolbar
instead: **every relationship in this design is NON-IDENTIFYING** (Workbench
draws these with a **dashed** line).

**Why:** an "identifying" relationship means the child's primary key includes
the parent's key. Here every table's primary key is just its own `id`, so no
relationship is identifying. If a line comes out **solid**, you picked the wrong
one — see [Troubleshooting](#solid-line).

---

### Step 4.3 — Add all 23 foreign keys

Work down this list. Every FK is listed in the spec tables in Part 3 as well, so
you can also do them table by table as you go.

| # | In this table | Foreign Key Name | Column | Points to | On Delete |
|---|---|---|---|---|---|
| 1 | `users` | `fk_users_agency_id` | `agency_id` | `agencies` | NO ACTION |
| 2 | `vehicles` | `fk_vehicles_agency_id` | `agency_id` | `agencies` | CASCADE |
| 3 | `vehicles` | `fk_vehicles_assigned_driver_id` | `assigned_driver_id` | `users` | SET NULL |
| 4 | `inspections` | `fk_inspections_agency_id` | `agency_id` | `agencies` | CASCADE |
| 5 | `inspections` | `fk_inspections_vehicle_id` | `vehicle_id` | `vehicles` | CASCADE |
| 6 | `inspections` | `fk_inspections_driver_id` | `driver_id` | `users` | CASCADE |
| 7 | `inspections` | `fk_inspections_reviewed_by` | `reviewed_by` | `users` | SET NULL |
| 8 | `inspection_items` | `fk_inspection_items_inspection_id` | `inspection_id` | `inspections` | CASCADE |
| 9 | `inspection_items` | `fk_inspection_items_checklist_item_id` | `checklist_item_id` | `inspection_checklist_items` | CASCADE |
| 10 | `damage_reports` | `fk_damage_reports_agency_id` | `agency_id` | `agencies` | CASCADE |
| 11 | `damage_reports` | `fk_damage_reports_vehicle_id` | `vehicle_id` | `vehicles` | CASCADE |
| 12 | `damage_reports` | `fk_damage_reports_driver_id` | `driver_id` | `users` | CASCADE |
| 13 | `damage_reports` | `fk_damage_reports_reviewed_by` | `reviewed_by` | `users` | SET NULL |
| 14 | `repair_logs` | `fk_repair_logs_agency_id` | `agency_id` | `agencies` | CASCADE |
| 15 | `repair_logs` | `fk_repair_logs_vehicle_id` | `vehicle_id` | `vehicles` | CASCADE |
| 16 | `repair_logs` | `fk_repair_logs_driver_id` | `driver_id` | `users` | SET NULL |
| 17 | `pm_schedules` | `fk_pm_schedules_agency_id` | `agency_id` | `agencies` | CASCADE |
| 18 | `pm_schedules` | `fk_pm_schedules_vehicle_id` | `vehicle_id` | `vehicles` | CASCADE |
| 19 | `dispatches` | `fk_dispatches_agency_id` | `agency_id` | `agencies` | CASCADE |
| 20 | `dispatches` | `fk_dispatches_vehicle_id` | `vehicle_id` | `vehicles` | CASCADE |
| 21 | `dispatches` | `fk_dispatches_driver_id` | `driver_id` | `users` | CASCADE |
| 22 | `notifications` | `fk_notifications_agency_id` | `agency_id` | `agencies` | CASCADE |
| 23 | `notifications` | `fk_notifications_user_id` | `user_id` | `users` | CASCADE |

---

### Step 4.4 — The two that will confuse you

Look at rows 6 and 7. **`inspections` points at `users` twice.** Same for rows
12 and 13 on `damage_reports`.

**This is correct and deliberate — do not "fix" it.**

- `driver_id` → the driver who **submitted** the inspection
- `reviewed_by` → the administrator who **reviewed** it

Both are people, both come from the `users` table, but they are two different
**roles**, so they need two separate columns and two separate lines. It's the
same reason a school permission slip has a line for the student's name *and* a
line for the parent's signature.

**You should see:** two lines running between `inspections` and `users`. That is
what Figure 6 shows too — count them.

---

### ✅ Checkpoint — end of Part 4

- [ ] **23 relationship lines** on the canvas
- [ ] Every line is **dashed**, not solid
- [ ] Two lines between `inspections` and `users`
- [ ] Two lines between `damage_reports` and `users`
- [ ] Every FK column now shows a red ◆ diamond in its table box

**Press Ctrl + S.**

---

<a name="part5"></a>
## PART 5 — Make it presentable

Right now it is correct but it probably looks like a plate of noodles. This part
is what turns it into something you can put in a manuscript.

### Step 5.1 — Arrange the boxes like Figure 6

Figure 6 uses **two rows**, and it is worth copying because it keeps the lines
short:

**Top row** (the things other records point *at*):
```
agencies    users    vehicles    inspection_checklist_items    inspection_items
```

**Bottom row** (the records that point at them):
```
notifications   inspections   damage_reports   repair_logs   pm_schedules   dispatches
```

Drag each box into place. Leave generous space between the two rows — that gap
is where all the lines will run.

---

### Step 5.2 — Tidy the lines

- **Drag a line's middle** to bend it away from a box it is crossing
- **Right-click a line → Properties** to change how it is drawn
- Aim for lines that run **horizontally or vertically**, never diagonally

---

### Step 5.3 — About the lines that cross

Some lines will cross no matter what you do. **This is not your fault and it
cannot be fixed.**

Three tables — `agencies`, `users` and `vehicles` — each connect to the same four
tables: `inspections`, `damage_reports`, `repair_logs` and `dispatches`. There is
a proven result in mathematics that a shape like that cannot be drawn flat
without at least one crossing. No amount of dragging escapes it.

**What you can control is how the crossings look.** Aim for lines that meet at
right angles, so a crossing can never be mistaken for a join. A diagram with
zero crossings would actually mean you had left relationships out.

> 💡 **If a panelist asks why lines cross, that paragraph is your answer.** It is
> a much better answer than apologising for it.

---

### Step 5.4 — Turn off the grid

**View → Grid** (untick it), so the printed diagram has a clean white background.

---

<a name="part6"></a>
## PART 6 — Export the image and check your work

### Step 6.1 — Export as PNG

1. **File → Export → Export as PNG…**
2. Save it as `rvms-erd-workbench.png`

> Use **PNG**, not JPG — JPG blurs thin lines and small text.

---

### Step 6.2 — Verify against the real database

This is the step that catches typos, and it takes 30 seconds.

1. **File → Export → Forward Engineer SQL CREATE Script…**
2. Save it anywhere
3. Open it in Notepad and check three numbers:

| Check | Should be |
|---|---|
| Count of `CREATE TABLE` | **11** |
| Count of `FOREIGN KEY` | **23** |
| Count of column lines | **131** |

*(In Notepad: Ctrl + H, search for `CREATE TABLE`, and it reports the count.)*

If any number is off, something was missed. Compare your script against
`rvms-erd.sql` — that file is the correct answer.

---

### Step 6.3 — Final checklist

- [ ] 11 tables, all lowercase names
- [ ] 131 columns total
- [ ] 23 relationship lines
- [ ] Every table has `id` as PK, with 🔑 and AI ticked
- [ ] Crow's foot symbols visible on the "many" end of every line
- [ ] Two lines from `inspections` to `users`, two from `damage_reports` to `users`
- [ ] Grid off, exported as PNG
- [ ] The `.mwb` model file saved somewhere safe

---

<a name="route-a"></a>
## PART 7 — ROUTE A: the 10-minute import

Use this if you want the diagram quickly, or as an answer key for Route B.

**This creates a database called `rvms_erd`. It does NOT touch your real `rvms`
database** — nothing you already have is at risk.

### Step A1 — Start MySQL

Make sure your MySQL server is running.

```powershell
sc query MySQL80
```

**You should see:** `STATE : 4 RUNNING`. If it says STOPPED, run
`net start MySQL80` as Administrator.

---

### Step A2 — Run the SQL file

1. Open MySQL Workbench and **click your connection** to open it
2. **File → Open SQL Script…** and choose `rvms-erd.sql`
3. Click the **⚡ lightning bolt** to run it

**You should see:** green ticks down the left of the output panel, and no red.

---

### Step A3 — Reverse engineer

1. **Database → Reverse Engineer…** (**Ctrl + R**)
2. Pick your connection → **Next**
3. **Next** again (it fetches the schema list)
4. **Tick `rvms_erd`** → **Next**
5. Keep clicking **Next** → **Execute** → **Next** → **Finish**

**You should see:** the finished EER diagram with all 11 tables and all 23
relationships already drawn.

---

### Step A4 — Set the notation and tidy up

Now do these from Route B — they apply exactly the same:

- **Step 1.5** — set Crow's Foot notation
- **Part 5** — arrange the boxes, turn off the grid
- **Part 6** — export as PNG

---

<a name="troubleshooting"></a>
## TROUBLESHOOTING

---

### ❌ The Referenced Table dropdown is empty

The table you want to point at **has not been created yet.**

Foreign keys can only point at tables that already exist. Go back and create the
missing table first. This is why Part 3's order matters.

---

<a name="solid-line"></a>
### ❌ My relationship line is SOLID, not dashed

You created an **identifying** relationship. Every relationship here should be
**non-identifying**.

**Fix:** right-click the line → **Delete**, then add the foreign key again using
the **Foreign Keys tab** method in Step 4.1. That method always produces a
non-identifying relationship.

---

### ❌ Workbench added a column I did not type

When you create a foreign key with the **relationship tools on the left
toolbar**, Workbench invents a column for it — usually named something like
`agencies_id`.

**Fix:** delete that invented column and delete the relationship, then add the
foreign key through the **Foreign Keys tab** instead, ticking the column you
already made. That way it uses *your* column and invents nothing.

> This is the single most common mess in Workbench, and it is why this manual
> tells you to build all the columns first and add foreign keys afterwards.

---

### ❌ My ENUM shows an error

Almost always one of three things:

1. A **missing quote** — every value needs single quotes: `ENUM('OK','Has Issue')`
2. A **space after a comma** — write `'a','b'`, not `'a', 'b'`
3. **Curly quotes** — if you pasted from Word, it may have turned `'` into `'`.
   Retype the quotes by hand in Workbench.

---

### ❌ I keep creating tables by accident

You are still in table mode. **Press Esc** after every table you place.

---

### ❌ The lines are a tangled mess

That is normal until you arrange the boxes. Do **Part 5** — put the tables in
two rows first, and most of the tangle sorts itself out.

---

### ❌ I closed the editor panel and cannot get it back

**Double-click any table** on the canvas and it reopens at the bottom.

---

### ❌ Workbench crashed and I lost my work

Workbench does not auto-save. **Ctrl + S every 10 minutes** is the only cure.

If you lost a lot, do **Route A** instead — 10 minutes and you have the whole
diagram back.

---

## ONE-PAGE SUMMARY

Print this part.

```
SETUP     File > New Model (Ctrl+N)
          rename schema to "rvms"
          Model > Relationship Notation > Crow's Foot (IE)
          Model > Object Notation   > Workbench (Simplified)
          double-click "Add Diagram"

ONE TABLE press T > click canvas > press Esc
          double-click it > Table tab   > type the name
                          > Columns tab > type the columns
          tick only: PK NN UN AI

ORDER     agencies · users · vehicles · inspection_checklist_items ·
          inspections · inspection_items · damage_reports · repair_logs ·
          pm_schedules · dispatches · notifications
          (a table can only point at one that already exists)

ONE FK    double-click table > Foreign Keys tab
          name it > pick Referenced Table > tick the column >
          set Referenced Column = id > set On Delete
          the line draws itself

TOTALS    11 tables · 131 columns · 23 foreign keys

CHECK     File > Export > Forward Engineer SQL CREATE Script
          count CREATE TABLE = 11, FOREIGN KEY = 23

EXPORT    View > Grid (off)
          File > Export > Export as PNG

SAVE      Ctrl + S every 10 minutes. Workbench does not auto-save.

STUCK?    Run rvms-erd.sql, then Database > Reverse Engineer (Ctrl+R).
          Ten minutes, same diagram.
```

---

## Notes

- The authority for every name, type and relationship in this manual is
  `rvms-erd.sql`, which is generated from `erd_model.py` and checked against the
  real migrations by `verify.py`. If this manual and the database ever disagree,
  the database is right.
- Three columns on `vehicles` that exist in the running system —
  `remarks`, `status_source` and `status_changed_at` — are **deliberately left
  out**, here and in the data dictionary, because no functional requirement backs
  them. That is a decision, not an omission.
- Framework tables (sessions, jobs, cache, tokens) are also left out. They are
  created by Laravel, hold no fleet information, and are not part of the study's
  data model.
