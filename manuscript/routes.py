"""Edge routing for the RVMS ERD.

Crossings cannot be eliminated: {agencies, users, vehicles} each relate to
{inspections, damage_reports, repair_logs, dispatches} — a complete K(3,4),
which contains K(3,3) and is therefore non-planar by Kuratowski's theorem. The
goal is legibility, not zero crossings.

Two devices do most of the work:

  * the agency relationships run in a lane BELOW the operational tables and
    approach them from underneath, while the driver and vehicle relationships
    run ABOVE and approach from the top. Separating the two families into
    different halves of the drawing removes the crossings between them
    entirely — which was most of them.
  * within each half, every edge gets its own lane, so lines never overlap and
    the only crossings left are right angles between a drop and a lane.
"""
from erd_model import TABLES

RH, HDR, W, GAP = 17, 26, 300, 60
ROW1_Y = 170
row1 = ["agencies","users","vehicles","inspection_checklist_items","inspection_items"]
row2 = ["notifications","inspections","damage_reports","repair_logs","pm_schedules","dispatches"]

def h(t): return HDR + len(TABLES[t])*RH
POS = {}
for i,t in enumerate(row1): POS[t] = (70 + i*(W+GAP), ROW1_Y)
CHANNEL = 330
ROW2_Y = ROW1_Y + max(h(t) for t in row1) + CHANNEL
for i,t in enumerate(row2): POS[t] = (70 + i*(W+GAP), ROW2_Y)

def B(t):
    x,y = POS[t]; return dict(x=x, y=y, w=W, h=h(t), cx=x+W/2, r=x+W, b=y+h(t))

CHAN_TOP = ROW1_Y + max(h(t) for t in row1) + 16
ROW2_BOT = ROW2_Y + max(h(t) for t in row2)
BOTTOM   = ROW2_BOT + 40          # lane band under everything, for agencies
LEFTMARG = 34                     # vertical run down the left margin

EDGES = []
def add(pts, one_end, many_end, label=""): EDGES.append((pts, one_end, many_end, label))

def slot(t, i, n):
    b = B(t); return b['x'] + b['w']*(i+1)/(n+1)

a, u, v = B("agencies"), B("users"), B("vehicles")

# ---------- agencies: three short edges, then a lane underneath -------------
add([(a['r'], a['y']+40), (u['x'], a['y']+40)],
    ('h', a['r'], a['y']+40, 1), ('h', u['x'], a['y']+40, -1), 'employs')

TOPLANE = 96
add([(a['cx']+70, a['y']), (a['cx']+70, TOPLANE), (v['cx'], TOPLANE), (v['cx'], v['y'])],
    ('v', a['cx']+70, a['y'], -1), ('v', v['cx'], v['y'], 1), 'owns')

n = B("notifications")
add([(a['cx']-95, a['b']), (a['cx']-95, n['y'])],
    ('v', a['cx']-95, a['b'], 1), ('v', a['cx']-95, n['y'], -1), 'has')

# The five remaining scoped tables, approached from below.
for i, t in enumerate(["inspections","damage_reports","repair_logs","pm_schedules","dispatches"]):
    tb = B(t); lane = BOTTOM + i*20; tx = slot(t, 0, 4)
    add([(a['x']+35, a['b']), (a['x']+35, lane), (tx, lane), (tx, tb['b'])],
        ('v', a['x']+35, a['b'], 1), ('v', tx, tb['b'], 1), 'has')

# ---------- users: the records a person is named on -------------------------
add([(u['r'], u['y']+40), (v['x'], u['y']+40)],
    ('h', u['r'], u['y']+40, 1), ('h', v['x'], u['y']+40, -1), 'drives')

USERS_LANE = CHAN_TOP + 10
USER_LABELS = ['receives','submits','reviews','submits','reviews','assigned to','dispatched on']
user_targets = [("notifications",1),("inspections",1),("inspections",2),
                ("damage_reports",1),("damage_reports",2),("repair_logs",1),("dispatches",1)]
for i,(t,which) in enumerate(user_targets):
    tb = B(t); lane = USERS_LANE + i*20; ex = u['x'] + 26 + i*38; tx = slot(t, which, 4)
    add([(ex, u['b']), (ex, lane), (tx, lane), (tx, tb['y'])],
        ('v', ex, u['b'], 1), ('v', tx, tb['y'], -1), USER_LABELS[i])

# ---------- vehicles: its own records ---------------------------------------
VEH_LABELS = ['undergoes','has','has','has','used in']
VEH_LANE = USERS_LANE + 7*20 + 24
for i,t in enumerate(["inspections","damage_reports","repair_logs","pm_schedules","dispatches"]):
    tb = B(t); lane = VEH_LANE + i*20; ex = v['x'] + 34 + i*46; tx = slot(t, 3, 4)
    add([(ex, v['b']), (ex, lane), (tx, lane), (tx, tb['y'])],
        ('v', ex, v['b'], 1), ('v', tx, tb['y'], -1), VEH_LABELS[i])

# ---------- inspection_items and its two parents ----------------------------
ii, ck, insp = B("inspection_items"), B("inspection_checklist_items"), B("inspections")
add([(ck['r'], ck['y']+58), (ii['x'], ck['y']+58)],
    ('h', ck['r'], ck['y']+58, 1), ('h', ii['x'], ck['y']+58, -1), 'assessed in')

ITEMS_LANE = VEH_LANE + 5*20 + 20
add([(insp['cx']+90, insp['y']), (insp['cx']+90, ITEMS_LANE), (ii['cx'], ITEMS_LANE), (ii['cx'], ii['b'])],
    ('v', insp['cx']+90, insp['y'], -1), ('v', ii['cx'], ii['b'], 1), 'contains')

CANVAS_W = 70 + len(row2)*(W+GAP) + 30
CANVAS_H = BOTTOM + 5*20 + 40
