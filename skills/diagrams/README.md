# Chapter 4 diagrams — draft references

Draft diagrams for the RVMS manuscript (Chapter 4), generated 2026-09 from the
finished system. **These are references to rebuild in draw.io**, not final art —
they match the format of the team's previous versions with content updated to
what the system actually does.

| Diagram | Files | Notes |
|---|---|---|
| System Architecture | `arch.png` / `arch.svg` | Client–server view: driver app + admin dashboard ↔ database ↔ FCM. |
| Context Diagram | `context.png` / `context.svg` | Whole system as process 0 + the three external entities. Content already current. |
| Data Flow Diagram (Diagram 0) | `dfd.png` / `dfd.svg` | 8 processes, D1–D8 stores, duplication corner-marks, feedback flows into Send Notification. |
| Functional Decomposition (FDD) | `fdd.png` / `fdd.svg` | 8 major functions. Updated: **Create Administrator Account** under User Management; **Monitor Frequent Issues** moved to Dashboard & Report Generation. |

## Editing
- `*.svg` — open directly in draw.io (File → Import) or any vector editor; fully editable.
- `*.png` — flat images for quick reference / pasting.
- `*.js` — the Node generator that produced each SVG (`node arch.js > arch.svg`), if you'd
  rather change data and regenerate than hand-edit.

## To re-render a PNG from an edited SVG
Any SVG→PNG tool works; these were rendered with headless Chromium at 2× scale.

## Cleanup to do in draw.io (known rough spots in the drafts)
- **System Architecture:** the admin/laptop labels sit close to the laptop icon — nudge apart.
- **DFD:** the feedback flows (New Access Request, License Alert, New Damage Report, PM Due
  Alert, Vehicle Status Update) route down the right margin into "Send Notification" and crowd
  the D8 store — draw.io's connector routing will space these cleanly.
