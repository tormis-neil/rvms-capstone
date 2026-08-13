# Chapter 4 — Data Model deliverables (2026-08)

Generated for the Chapter 4 revision. Everything here was verified against the
system database, not typed from memory: `verify.py` compares the documented
fields against `backend/database/migrations/` and must report ALL MATCH before
the document is regenerated.

| File | What it is |
|---|---|
| `RVMS-Chapter4-ERD-and-Data-Dictionary.docx` | **The deliverable.** Part A explains the ERD; Part B is manuscript text to paste, including Tables 5–15. |
| `rvms-erd.png` | The ERD, 6780 × 3930 px. Insert as Figure 6. |
| `rvms-erd.drawio` | Editable source for diagrams.net, same layout. |
| `rvms-erd.sql` | Schema for MySQL Workbench. Run it, then Database > Reverse Engineer to have Workbench draw the EER diagram from the foreign keys. |
| `fig2-system-architecture.png` | Figure 2 — System Architecture, three tiers. |
| `fig3-context-diagram.png` | Figure 3 — Context Diagram (Diagram 0 level 0). |
| `fig4-data-flow-diagram.png` | Figure 4 — Data Flow Diagram, eight processes. |
| `fig5-functional-decomposition.png` | Figure 5 — Functional Decomposition Diagram. |

## Regenerating

```bash
python3 verify.py        # must print ALL MATCH
python3 draw_erd.py      # -> rvms-erd.png
python3 make_drawio.py   # -> rvms-erd.drawio
node build_docx.js       # -> the .docx

python3 fig2_architecture.py     # -> fig2-system-architecture.png
python3 fig3_context.py          # -> fig3-context-diagram.png
python3 fig4_dfd.py              # -> fig4-data-flow-diagram.png
python3 fig5_fdd.py              # -> fig5-functional-decomposition.png
```

`dlib.py` is the shared drawing library for figures 2–5 (the same palette and
line weights as the ERD, so the five figures look like one set).

`erd_model.py` is the single source of truth for the diagram, the dictionary
and the SQL, so the three can never disagree — `verify.py` checks all of them. Edit it, re-run `verify.py`, then
regenerate.

## Decisions baked in

- **Crow's foot notation**, chosen over Chen: the database is designed down to
  its columns, and Chen would need roughly 130 attribute ovals.
- **`deleted_at` is documented** (FR-05, FR-06 require delete and restore);
  `vehicles.remarks`, `status_source` and `status_changed_at` are **not**, as no
  functional requirement backs them.
- **Framework tables are excluded** — sessions, jobs, cache, tokens.
- **Primary keys are `id`**, which is what the database actually uses.
- **Figures 2–5 correct four errors** carried by the drawio originals, rather
  than reproducing them: "TRIGGGER" → "TRIGGER" (Fig 2), "Recods" → "Records"
  and "Repair Request" → "Report Request" (Fig 3). Figure 5 gains **Password
  Recovery** and **Manage Notification List**, both of which the system has
  (FR-22, and the notifications page's mark-read / clear-read controls) and the
  original decomposition omitted.
- **The DFD nests its notification-trigger lanes.** The five events that raise an
  alert (new access request, licence alert, new damage report, PM due, vehicle
  status update) all flow into process 8 from across the page. The earliest
  trigger takes the outermost lane and the deepest return line, which is what
  keeps the five from crossing one another or any store.
- Some relationship lines cross. {agencies, users, vehicles} each relate to
  {inspections, damage_reports, repair_logs, dispatches} — a complete K(3,4),
  which contains K(3,3) and is non-planar, so a crossing-free drawing does not
  exist. Routing is orthogonal, lines are grouped into lanes, and every crossing
  is a right angle.
