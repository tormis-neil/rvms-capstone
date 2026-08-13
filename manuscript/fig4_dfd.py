"""Figure 4 — Data Flow Diagram (Diagram 0).

Laid out one process per row: the external entities it serves on the left, the
process in the middle, the data stores it reads and writes on the right. Every
flow is then a short horizontal line inside its own row, which is the only way
eight processes, eight stores and thirty-odd labelled flows stay readable.

Entities and stores are drawn more than once where they are used by several
processes — the standard duplication convention, marked with a corner tick —
rather than dragged across the page.
"""
from dlib import *

ROW_H, TOP = 148, 96
EXT_X, EXT_W = 44, 178
PROC_CX, PROC_R = 470, 62
ST_X,  ST_W = 700, 230
LANE = 1076                      # outermost notification-trigger lane

ROWS = [
 ("1", "Authenticate and\nManage Account",
     [("AUTHORIZED DRIVER","Login and Registration Credential",">"),
      ("AGENCY ADMINISTRATOR","Login Credential",">")],
     [("D1","USERS","User Account","User Credential")], "New Access Request"),
 ("2", "Manage Vehicle and\nDriver Record",
     [("AGENCY ADMINISTRATOR","Vehicle and Driver Records",">"),
      ("AUTHORIZED DRIVER","Assigned Vehicle Information","<")],
     [("D1","USERS","Driver Record","Driver Detail"),
      ("D2","VEHICLES","Vehicle Detail","Vehicle Record")], "License Alert"),
 ("3", "Process Inspection",
     [("AUTHORIZED DRIVER","BLOWBAGETS Inspection",">"),
      ("AGENCY ADMINISTRATOR","Inspection Review","<>")],
     [("D3","INSPECTIONS","Inspection Record","Inspection History"),
      ("D2","VEHICLES","Vehicle Status", None)], None),
 ("4", "Manage Damage\nand Repair",
     [("AUTHORIZED DRIVER","Damage Report",">"),
      ("AGENCY ADMINISTRATOR","Damage Review and Repair Log","<>")],
     [("D4","DAMAGE REPORTS","Damage Record","Damage Detail"),
      ("D5","REPAIR LOGS","Repair Detail","Repair Record")], "New Damage Report"),
 ("5", "Manage Preventive\nMaintenance",
     [("AGENCY ADMINISTRATOR","Preventive Maintenance Schedule",">")],
     [("D6","PM SCHEDULES","PM Record","PM Schedule"),
      ("D2","VEHICLES", None, "Current Mileage")], "Preventive Maintenance Due Alert"),
 ("6", "Manage Dispatch",
     [("AGENCY ADMINISTRATOR","Dispatch Data","<>")],
     [("D7","DISPATCHES","Dispatch Record","Dispatch Details"),
      ("D2","VEHICLES","Vehicle Status","Vehicle Details")], "Vehicle Status Update"),
 ("7", "Generate Dashboard\nand Report",
     [("AGENCY ADMINISTRATOR","Report Request","<>")],
     [("D1-D7","ALL RECORD STORES", None, "Record Data")], None),
 ("8", "Send Notification",
     [("FIREBASE CLOUD MESSAGING","Push Notification Request",">"),
      ("AGENCY ADMINISTRATOR","Admin Alerts","<"),
      ("AUTHORIZED DRIVER","Driver Notifications","<")],
     [("D8","NOTIFICATIONS","Notification Record", None)], None),
]

def row_y(i): return TOP + i*ROW_H
P8Y = row_y(len(ROWS)-1) + 60     # centre of the Send Notification process
H = P8Y + 168                     # room for the trigger lines entering it from below
C = Canvas(1130, H); c = C

def store(x, y, code, name, dup=False):
    """Open-ended store: bar, code, name."""
    h = 40
    c.rect(x, y, ST_W, h, fill=(240, 244, 251), outline=NAVY, width=1.5)
    c.line([(x+46, y), (x+46, y+h)], NAVY, 1.5)
    c.text(x+23, y+h/2, code, 9.5, True, NAVY, anchor="cm")
    c.wrapped(x+46+(ST_W-46)/2, y+h/2, name, 9, True, NAVY, width=ST_W-60)
    if dup: c.line([(x+ST_W-13, y), (x+ST_W, y+13)], NAVY, 1.2)
    return dict(x=x, y=y, w=ST_W, h=h, cy=y+h/2, r=x+ST_W)

seen_ext, seen_store = set(), set()
trigger_pts = []

for i, (num, pname, exts, stores, trig) in enumerate(ROWS):
    y = row_y(i); cy = y + 60

    # ---- process -----------------------------------------------------------
    c.ellipse(PROC_CX-PROC_R, cy-PROC_R, PROC_R*2, PROC_R*2, fill=(233,239,249))
    c.text(PROC_CX, cy-30, num, 15, True, NAVY, anchor="ct")
    c.wrapped(PROC_CX, cy+12, pname.replace("\n", " "), 9, True, NAVY, width=PROC_R*1.85)

    # ---- external entities on the left -------------------------------------
    eh = 44; gap = 8
    total = len(exts)*eh + (len(exts)-1)*gap
    ey0 = cy - total/2
    for k, (ename, flow, direction) in enumerate(exts):
        ey = ey0 + k*(eh+gap)
        c.rect(EXT_X, ey, EXT_W, eh, fill=FILL, outline=NAVY, width=1.5)
        c.wrapped(EXT_X+EXT_W/2, ey+eh/2, ename, 8, True, NAVY, width=EXT_W-14)
        if ename in seen_ext: c.line([(EXT_X+EXT_W-13, ey), (EXT_X+EXT_W, ey+13)], NAVY, 1.2)
        seen_ext.add(ename)
        my = ey + eh/2
        x1, x2 = EXT_X+EXT_W, PROC_CX-PROC_R-6
        if direction == ">":   c.arrow([(x1, my), (x2, my)])
        elif direction == "<": c.arrow([(x2, my), (x1, my)])
        else:                  c.arrow([(x1, my-7), (x2, my-7)]); c.arrow([(x2, my+7), (x1, my+7)])
        c.wrapped((x1+x2)/2, my-16 if direction != "<>" else my-22, flow, 8, False, (66,76,96), width=x2-x1-8)

    # ---- data stores on the right ------------------------------------------
    sh = 40; sgap = 14
    stot = len(stores)*sh + (len(stores)-1)*sgap
    sy0 = cy - stot/2
    for k, (code, sname, into, outof) in enumerate(stores):
        sy = sy0 + k*(sh+sgap)
        st = store(ST_X, sy, code, sname, dup=(code in seen_store))
        seen_store.add(code)
        x1, x2 = PROC_CX+PROC_R+6, ST_X
        if into and outof:
            c.arrow([(x1, st['cy']-7), (x2, st['cy']-7)]); c.wrapped((x1+x2)/2, st['cy']-19, into, 8, False, (66,76,96), width=x2-x1-8)
            c.arrow([(x2, st['cy']+7), (x1, st['cy']+7)]); c.wrapped((x1+x2)/2, st['cy']+19, outof, 8, False, (66,76,96), width=x2-x1-8)
        elif into:
            c.arrow([(x1, st['cy']), (x2, st['cy'])]); c.wrapped((x1+x2)/2, st['cy']-11, into, 8, False, (66,76,96), width=x2-x1-8)
        else:
            c.arrow([(x2, st['cy']), (x1, st['cy'])]); c.wrapped((x1+x2)/2, st['cy']-11, outof, 8, False, (66,76,96), width=x2-x1-8)

    if trig: trigger_pts.append((cy, trig))

# ---- the five notification triggers, down lanes on the right ----------------
# Each leaves its process below the row's data stores, runs out to its own lane,
# down the page, and back in underneath process 8. The lanes are nested — the
# earliest trigger takes the OUTERMOST lane and the DEEPEST return line — which
# is what keeps the five from crossing one another.
import math
for k, (cy, lab) in enumerate(trigger_pts):
    lx = LANE - k*24                     # outermost lane first
    gy = cy + 80                         # clear of this row's stores and the next row's
    ey = P8Y + 132 - k*14                # deepest return line first
    dx = -48 + k*24                      # where it re-enters process 8 from below
    iy = P8Y + math.sqrt(PROC_R**2 - dx**2)
    c.arrow([(PROC_CX, cy+PROC_R), (PROC_CX, gy), (lx, gy), (lx, ey),
             (PROC_CX+dx, ey), (PROC_CX+dx, iy+4)])
    c.wrapped((PROC_CX+lx)/2, gy-11, lab, 8, False, (66, 76, 96), width=lx-PROC_CX-40)

C.save("fig4-data-flow-diagram.png"); print("fig4", C.img.size)
