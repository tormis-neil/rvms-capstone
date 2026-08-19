# =====================================================================
#  RVMS ERD — automatic layout for MySQL Workbench
#
#  Run this INSIDE MySQL Workbench (Scripting > Scripting Shell), after the
#  eleven tables are already on an EER diagram. It moves every table to the
#  position it occupies in Figure 6, so the diagram comes out evenly spaced
#  instead of in Workbench's cramped automatic arrangement.
#
#  HOW TO RUN IT
#    1. Open your model and its EER diagram.
#    2. FIRST make the canvas big enough:
#         Model > Diagram Properties and Size...  ->  Width 3, Height 2
#       Without this the canvas is one page and the tables physically
#       cannot be spread out. This is the usual reason spacing "won't work".
#    3. Scripting > Scripting Shell   (Ctrl + F3)
#    4. Click the SHELL tab (the black one). Type this at the prompt, Enter:
#
#         exec(open(r"C:\\path\\to\\rvms-erd-layout.py").read())
#
#    5. Read the report it prints. Then click the diagram tab to see the result.
#
#  DO NOT USE THE RUN BUTTON, and do not open this file in Workbench's editor
#  to run it. On Python 3 builds of Workbench the Run button fails with
#  "NameError: name 'execfile' is not defined" — that is a bug in Workbench's
#  own script runner, not in this file. The shell command above avoids it.
#
#  If you cannot get the file to load at all, paste the single line in
#  ONE-LINE FALLBACK at the bottom of this file into the shell instead. It
#  does the same job with no file to find.
#
#  Nothing here touches your data. It only moves boxes on the drawing.
# =====================================================================
from __future__ import print_function

import grt

# ---------------------------------------------------------------------
#  Where each table goes, in diagram pixels: (left, top)
#
#  Two rows, mirroring Figure 6. The top row holds the tables that other
#  records point AT; the bottom row holds the records that point at them.
#  The 400-pixel gap between the rows is the lane the relationship lines
#  run through — keep it, it is what stops the diagram looking tangled.
# ---------------------------------------------------------------------
ROW_1_Y = 40
ROW_2_Y = 780
PITCH = 380                      # horizontal distance between table boxes

POSITIONS = {
    # top row
    'agencies':                   (40,                  ROW_1_Y),
    'users':                      (40 + PITCH * 1,      ROW_1_Y),
    'vehicles':                   (40 + PITCH * 2,      ROW_1_Y),
    'inspection_checklist_items': (40 + PITCH * 3,      ROW_1_Y),
    'inspection_items':           (40 + PITCH * 4,      ROW_1_Y),
    # bottom row
    'notifications':              (40,                  ROW_2_Y),
    'inspections':                (40 + PITCH * 1,      ROW_2_Y),
    'damage_reports':             (40 + PITCH * 2,      ROW_2_Y),
    'repair_logs':                (40 + PITCH * 3,      ROW_2_Y),
    'pm_schedules':               (40 + PITCH * 4,      ROW_2_Y),
    'dispatches':                 (40 + PITCH * 5,      ROW_2_Y),
}

CANVAS_W = 2400
CANVAS_H = 1300


def main():
    print('')
    print('RVMS ERD layout')
    print('---------------')

    models = grt.root.wb.doc.physicalModels
    if not len(models):
        print('ERROR: no model is open. Open your .mwb file first.')
        return
    model = models[0]

    if not len(model.diagrams):
        print('ERROR: this model has no EER diagram.')
        print('       In the model tab, double-click "Add Diagram", then drag the')
        print('       eleven tables onto it from the Catalog panel on the right.')
        return
    diagram = model.diagrams[0]

    # Grow the canvas first, or the moves below get clamped to one page.
    try:
        if diagram.width < CANVAS_W:
            diagram.width = CANVAS_W
        if diagram.height < CANVAS_H:
            diagram.height = CANVAS_H
        print('canvas: %d x %d' % (diagram.width, diagram.height))
    except Exception as e:
        print('NOTE: could not resize the canvas from the script (%s).' % e)
        print('      Set it by hand: Model > Diagram Properties and Size,')
        print('      Width 3 and Height 2, then run this script again.')

    # Index the table figures actually present on the diagram.
    figures = {}
    for fig in diagram.figures:
        table = getattr(fig, 'table', None)
        if table is not None:
            figures[table.name] = fig

    print('found %d table figures on the diagram' % len(figures))
    print('')

    moved = 0
    for name in sorted(POSITIONS, key=lambda n: (POSITIONS[n][1], POSITIONS[n][0])):
        left, top = POSITIONS[name]
        fig = figures.get(name)
        if fig is None:
            print('  MISSING   %-28s not on the diagram' % name)
            continue
        fig.left = left
        fig.top = top
        moved += 1
        print('  placed    %-28s at (%4d, %4d)' % (name, left, top))

    extra = [n for n in figures if n not in POSITIONS]
    for name in extra:
        print('  IGNORED   %-28s not part of the RVMS data model' % name)

    print('')
    print('%d of %d tables placed.' % (moved, len(POSITIONS)))

    if moved < len(POSITIONS):
        print('')
        print('Tables reported MISSING are not on the diagram yet. Drag them onto')
        print('the canvas from the Catalog panel on the right, then run this again.')

    print('')
    print('Next:')
    print("  Model > Relationship Notation > Crow's Foot (IE)")
    print('  Model > Object Notation       > Workbench (Simplified)')
    print('  View  > Grid                  (untick, for a clean export)')
    print('  File  > Export > Export as PNG')
    print('')


main()


# =====================================================================
#  ONE-LINE FALLBACK
#
#  If Workbench will not load this file at all, copy the single long line
#  below (everything after the "#" on the line starting with "import grt")
#  and paste it straight into the Scripting Shell prompt, then press Enter.
#  It needs no file path and does the same job.
#
# import grt; d=grt.root.wb.doc.physicalModels[0].diagrams[0]; P={'agencies':(40,40),'users':(420,40),'vehicles':(800,40),'inspection_checklist_items':(1180,40),'inspection_items':(1560,40),'notifications':(40,780),'inspections':(420,780),'damage_reports':(800,780),'repair_logs':(1180,780),'pm_schedules':(1560,780),'dispatches':(1940,780)}; d.width=max(d.width,2400); d.height=max(d.height,1300); n=len([1 for f in d.figures if getattr(f,'table',None) and f.table.name in P and (setattr(f,'left',P[f.table.name][0]) or setattr(f,'top',P[f.table.name][1]) or True)]); print('placed %d of 11' % n)
# =====================================================================
