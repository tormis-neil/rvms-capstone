"""Figure 5 — Functional Decomposition Diagram.

Adds the two functions the 2026-08 audit found missing from the draw.io
source: Password Recovery (FR-22 had no leaf at all) and Manage Notification
List (FR-21 also grants mark-as-read and clear, which were unrepresented).
"""
from dlib import *

BRANCHES = [
 ("USER MANAGEMENT", ["User Login and Authentication","Driver Registration",
    "Access Request Approval","Profile Management","Role and Agency Access Control",
    "Password Recovery"]),
 ("VEHICLE AND DRIVER\nRECORD MANAGEMENT", ["Manage Vehicle Records","Manage Driver Records",
    "View Assigned Vehicle","Monitor License Expiry","Update Vehicle Status"]),
 ("INSPECTION MANAGEMENT", ["Submit BLOWBAGETS Inspection","Review Inspection",
    "View Inspection History","Monitor Frequent Issues"]),
 ("DAMAGE AND REPAIR\nMANAGEMENT", ["Submit Damage Report","Review Damage Report",
    "Log Repair Activity"]),
 ("PREVENTIVE MAINTENANCE\nMANAGEMENT", ["Create PM Schedule","Recompute PM Status",
    "Record PM Completion"]),
 ("DISPATCH MANAGEMENT", ["Open Dispatch","Close Dispatch and Return Status",
    "Monitor Vehicle Availability"]),
 ("DASHBOARD AND REPORT\nGENERATION", ["View Dashboard Summary","Generate Reports"]),
 ("NOTIFICATION MANAGEMENT", ["Send In-App Notification","Send Notification via FCM",
    "Manage Notification List"]),
]

CW, GAP, M = 186, 16, 40
BRANCH_H, LEAF_H, LEAF_GAP = 62, 46, 12
ROOT_Y, BUS_Y, BRANCH_Y = 40, 132, 162
LEAF_TOP = BRANCH_Y + BRANCH_H + 34

n = len(BRANCHES)
W = M*2 + n*CW + (n-1)*GAP
maxleaf = max(len(l) for _, l in BRANCHES)
H = LEAF_TOP + maxleaf*(LEAF_H+LEAF_GAP) + 30

C = Canvas(W, H); c = C
colx = [M + i*(CW+GAP) for i in range(n)]

# ---- root ------------------------------------------------------------------
rw, rh = 330, 58
rx = (W-rw)/2
c.rect(rx, ROOT_Y, rw, rh, fill=(31,56,100), outline=NAVY, radius=6)
c.wrapped(rx+rw/2, ROOT_Y+rh/2, "RESCUE VEHICLE MANAGEMENT SYSTEM", 12, True, WHITE, width=rw-24)

# ---- bus from the root out to each branch ----------------------------------
c.line([(W/2, ROOT_Y+rh), (W/2, BUS_Y)])
c.line([(colx[0]+CW/2, BUS_Y), (colx[-1]+CW/2, BUS_Y)])

for i, (name, leaves) in enumerate(BRANCHES):
    x = colx[i]; cx = x + CW/2
    c.line([(cx, BUS_Y), (cx, BRANCH_Y)])
    c.rect(x, BRANCH_Y, CW, BRANCH_H, fill=(223, 231, 245), radius=5)
    c.wrapped(cx, BRANCH_Y+BRANCH_H/2, name.replace("\n", " "), 9.5, True, NAVY, width=CW-16)

    # a spine down the left, with a stub into each leaf
    spine = x + 16
    last = LEAF_TOP + (len(leaves)-1)*(LEAF_H+LEAF_GAP) + LEAF_H/2
    c.line([(cx, BRANCH_Y+BRANCH_H), (cx, LEAF_TOP-18), (spine, LEAF_TOP-18), (spine, last)])
    for j, leaf in enumerate(leaves):
        ly = LEAF_TOP + j*(LEAF_H+LEAF_GAP)
        c.line([(spine, ly+LEAF_H/2), (x+30, ly+LEAF_H/2)])
        c.rect(x+30, ly, CW-30, LEAF_H, fill=WHITE, outline=(120,140,178), width=1.2, radius=4)
        c.wrapped(x+30+(CW-30)/2, ly+LEAF_H/2, leaf, 8.5, False, INK, width=CW-46)

C.save("fig5-functional-decomposition.png"); print("fig5", C.img.size)
