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

## Regenerating

```bash
python3 verify.py        # must print ALL MATCH
python3 draw_erd.py      # -> rvms-erd.png
python3 make_drawio.py   # -> rvms-erd.drawio
node build_docx.js       # -> the .docx
```

`erd_model.py` is the single source of truth for both the diagram and the
dictionary, so the two can never disagree. Edit it, re-run `verify.py`, then
regenerate.

## Decisions baked in

- **Crow's foot notation**, chosen over Chen: the database is designed down to
  its columns, and Chen would need roughly 130 attribute ovals.
- **`deleted_at` is documented** (FR-05, FR-06 require delete and restore);
  `vehicles.remarks`, `status_source` and `status_changed_at` are **not**, as no
  functional requirement backs them.
- **Framework tables are excluded** — sessions, jobs, cache, tokens.
- **Primary keys are `id`**, which is what the database actually uses.
- Some relationship lines cross. {agencies, users, vehicles} each relate to
  {inspections, damage_reports, repair_logs, dispatches} — a complete K(3,4),
  which contains K(3,3) and is non-planar, so a crossing-free drawing does not
  exist. Routing is orthogonal, lines are grouped into lanes, and every crossing
  is a right angle.
