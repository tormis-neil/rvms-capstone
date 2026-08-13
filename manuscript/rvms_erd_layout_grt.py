# -*- coding: utf-8 -*-
# =====================================================================
#  RVMS ERD layout — as a MySQL Workbench PLUGIN
#
#  Same job as rvms-erd-layout.py, but installed as a menu item instead of
#  typed into the Scripting Shell. Use this if the Shell tab will not accept
#  keyboard input, which is a known Workbench bug on some Windows setups.
#
#  INSTALL (once)
#    1. Close MySQL Workbench completely.
#    2. Copy THIS FILE into:
#         C:\Users\<you>\AppData\Roaming\MySQL\Workbench\modules
#       Paste that path into the File Explorer address bar to get there.
#       (Your own Workbench printed this exact folder when it started up.)
#    3. Keep the file name EXACTLY as it is — rvms_erd_layout_grt.py.
#       Workbench only loads modules whose names end in _grt.py.
#    4. Start Workbench again and open your model and its EER diagram.
#
#  RUN
#    Open the EER diagram, then:  Tools > Utilities > Arrange RVMS ERD
#    (on some builds it appears under the Scripting menu instead)
#
#  A message box reports what it placed. Nothing here touches your data —
#  it only moves boxes on the drawing.
# =====================================================================
from wb import DefineModule, wbinputs
import grt
import mforms

ModuleInfo = DefineModule(name='RvmsErdLayout',
                          author='RVMS Capstone',
                          version='1.0')

ROW_1_Y = 40
ROW_2_Y = 780
PITCH = 380

POSITIONS = {
    'agencies':                   (40 + PITCH * 0, ROW_1_Y),
    'users':                      (40 + PITCH * 1, ROW_1_Y),
    'vehicles':                   (40 + PITCH * 2, ROW_1_Y),
    'inspection_checklist_items': (40 + PITCH * 3, ROW_1_Y),
    'inspection_items':           (40 + PITCH * 4, ROW_1_Y),
    'notifications':              (40 + PITCH * 0, ROW_2_Y),
    'inspections':                (40 + PITCH * 1, ROW_2_Y),
    'damage_reports':             (40 + PITCH * 2, ROW_2_Y),
    'repair_logs':                (40 + PITCH * 3, ROW_2_Y),
    'pm_schedules':               (40 + PITCH * 4, ROW_2_Y),
    'dispatches':                 (40 + PITCH * 5, ROW_2_Y),
}

CANVAS_W = 2400
CANVAS_H = 1300


def _resolve_diagram(diagram):
    """Use the diagram Workbench handed us; fall back to the model's first."""
    if diagram is not None:
        return diagram
    models = grt.root.wb.doc.physicalModels
    if len(models) and len(models[0].diagrams):
        return models[0].diagrams[0]
    return None


def _arrange(diagram):
    diagram = _resolve_diagram(diagram)
    if diagram is None:
        return 'No EER diagram is open.\n\nOpen your model, open its diagram, then run this again.'

    try:
        if diagram.width < CANVAS_W:
            diagram.width = CANVAS_W
        if diagram.height < CANVAS_H:
            diagram.height = CANVAS_H
    except Exception:
        pass    # the menu route below still works; canvas can be set by hand

    figures = {}
    for fig in diagram.figures:
        table = getattr(fig, 'table', None)
        if table is not None:
            figures[table.name] = fig

    placed, missing = [], []
    for name, (left, top) in POSITIONS.items():
        fig = figures.get(name)
        if fig is None:
            missing.append(name)
            continue
        fig.left = left
        fig.top = top
        placed.append(name)

    lines = ['Placed %d of %d tables.' % (len(placed), len(POSITIONS))]
    if missing:
        lines.append('')
        lines.append('Not on the diagram yet:')
        for name in sorted(missing):
            lines.append('   ' + name)
        lines.append('')
        lines.append('Drag them onto the canvas from the Catalog panel, then run again.')
    else:
        lines.append('')
        lines.append('Next:')
        lines.append("   Model > Relationship Notation > Crow's Foot (IE)")
        lines.append('   Model > Object Notation       > Workbench (Simplified)')
        lines.append('   View  > Grid                  (untick)')
        lines.append('   File  > Export > Export as PNG')
    return '\n'.join(lines)


@ModuleInfo.plugin('rvms.erd.arrange',
                   caption='Arrange RVMS ERD',
                   description='Places the eleven RVMS tables in the Figure 6 layout',
                   input=[wbinputs.currentDiagram()],
                   groups=['Overview/Utility', 'Menu/Utilities'])
@ModuleInfo.export(grt.INT, grt.classes.model_Diagram)
def arrangeRvmsErd(diagram):
    mforms.Utilities.show_message('RVMS ERD layout', _arrange(diagram), 'OK', '', '')
    return 0
