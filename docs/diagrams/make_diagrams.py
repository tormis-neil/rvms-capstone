#!/usr/bin/env python3
"""Render the RVMS Context Diagram (Fig. 3) and Data Flow Diagram / Diagram 0
(Fig. 4) using Gane & Sarson conventions, matplotlib only."""
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, Rectangle, FancyArrowPatch
import textwrap

EDGE = "#1B2F72"      # navy outline
ENT_FILL = "#E8ECF6"
PROC_FILL = "#FFFFFF"
STORE_FILL = "#F4F6FB"
ARROW = "#222222"


def process(ax, x, y, w, h, num, text, fs=10):
    p = FancyBboxPatch((x, y), w, h,
                       boxstyle="round,pad=0.02,rounding_size=0.18",
                       linewidth=1.6, edgecolor=EDGE, facecolor=PROC_FILL, zorder=3)
    ax.add_patch(p)
    ax.text(x + 0.18, y + h - 0.20, str(num), fontsize=fs + 1, fontweight="bold",
            color=EDGE, zorder=4, ha="left", va="top")
    ax.text(x + w / 2, y + h / 2 - 0.05, text, fontsize=fs, ha="center", va="center",
            zorder=4, fontweight="bold", color="#0F172A")


def entity(ax, x, y, w, h, text, fs=10):
    ax.add_patch(Rectangle((x + 0.07, y - 0.07), w, h, linewidth=1.2,
                 edgecolor="#9AA6C2", facecolor="#C9D2E6", zorder=2))  # 3D shadow
    ax.add_patch(Rectangle((x, y), w, h, linewidth=1.6, edgecolor=EDGE,
                 facecolor=ENT_FILL, zorder=3))
    ax.text(x + w / 2, y + h / 2, text, fontsize=fs, ha="center", va="center",
            fontweight="bold", zorder=4, color="#0F172A")


def store(ax, x, y, w, h, dnum, label, fs=9):
    # Gane & Sarson: closed on the left, open on the right.
    ax.add_patch(Rectangle((x, y), w, h, linewidth=0, facecolor=STORE_FILL, zorder=2))
    ax.plot([x, x + w], [y + h, y + h], color=EDGE, lw=1.5, zorder=3)   # top
    ax.plot([x, x + w], [y, y], color=EDGE, lw=1.5, zorder=3)           # bottom
    ax.plot([x, x], [y, y + h], color=EDGE, lw=1.5, zorder=3)           # left (closed)
    ax.plot([x + 0.5, x + 0.5], [y, y + h], color=EDGE, lw=1.2, zorder=3)  # D# divider
    ax.text(x + 0.25, y + h / 2, dnum, fontsize=fs, ha="center", va="center",
            fontweight="bold", color=EDGE, zorder=4)
    ax.text(x + 0.5 + (w - 0.5) / 2, y + h / 2, label, fontsize=fs, ha="center",
            va="center", zorder=4, color="#0F172A")


def arrow(ax, p1, p2, label=None, fs=8, lpos=0.5, dx=0.0, dy=0.0,
          conn="arc3,rad=0", color=ARROW):
    a = FancyArrowPatch(p1, p2, arrowstyle="-|>", mutation_scale=13,
                        lw=1.2, color=color, connectionstyle=conn, zorder=2,
                        shrinkA=2, shrinkB=2)
    ax.add_patch(a)
    if label:
        mx = p1[0] + (p2[0] - p1[0]) * lpos + dx
        my = p1[1] + (p2[1] - p1[1]) * lpos + dy
        ax.text(mx, my, label, fontsize=fs, ha="center", va="center", zorder=5,
                bbox=dict(boxstyle="round,pad=0.18", fc="white", ec="none", alpha=0.95))


# ============================ FIGURE 3 — CONTEXT ============================
def context_diagram():
    fig, ax = plt.subplots(figsize=(13.5, 8.8))
    ax.set_xlim(0, 14); ax.set_ylim(0, 9); ax.axis("off")

    process(ax, 5.0, 3.7, 4.0, 1.7, "0", "RESCUE VEHICLE\nMANAGEMENT SYSTEM", fs=12)
    entity(ax, 0.6, 6.5, 3.0, 1.25, "AUTHORIZED\nDRIVER", fs=11)
    entity(ax, 10.4, 6.5, 3.0, 1.25, "AGENCY\nADMINISTRATOR", fs=11)
    entity(ax, 5.6, 0.6, 2.8, 1.2, "FIREBASE CLOUD\nMESSAGING (FCM)", fs=10)

    # Driver <-> System
    arrow(ax, (2.5, 6.5), (5.15, 5.05),
          "Login Credentials, Registration\nRequest, Inspection Submission,\nDamage Report, Profile Update",
          lpos=0.5, dx=-0.15, dy=0.55, fs=8)
    arrow(ax, (5.0, 4.35), (2.3, 6.5),
          "Assigned Vehicle\nInformation,\nDriver Notification",
          lpos=0.55, dx=-0.35, dy=-0.35, fs=8)
    # Admin <-> System
    arrow(ax, (11.5, 6.5), (8.85, 5.05),
          "Login Credentials,\nVehicle & Driver Record,\nMaintenance & Dispatch Record,\nDriver Access Approval,\nReport Request",
          lpos=0.5, dx=0.2, dy=0.6, fs=8)
    arrow(ax, (9.0, 4.35), (11.7, 6.5),
          "Monitoring Information,\nGenerated Report,\nAdministrator Notification",
          lpos=0.55, dx=0.35, dy=-0.3, fs=8)
    # System -> FCM
    arrow(ax, (7.0, 3.7), (7.0, 1.8), "Notification Request", lpos=0.5, dx=1.15, fs=8)
    ax.text(7.0, 0.25, "(FCM pushes the notification to the driver's mobile device)",
            fontsize=7.5, ha="center", va="center", style="italic", color="#64748B")

    ax.text(7.0, 8.55, "Figure 3. Context Diagram of the Rescue Vehicle Management System",
            fontsize=11.5, ha="center", va="center", fontweight="bold", color=EDGE)
    fig.savefig("/home/user/rvms-capstone/docs/diagrams/figure3_context_diagram.png",
                dpi=160, bbox_inches="tight", facecolor="white")
    plt.close(fig)


# ============================ FIGURE 4 — DFD (DIAGRAM 0) ====================
def dfd_diagram():
    fig, ax = plt.subplots(figsize=(17.5, 20))
    ax.set_xlim(0, 18); ax.set_ylim(0, 21); ax.axis("off")

    PX, PW, PH = 6.6, 4.2, 1.15
    py = {1: 18.4, 2: 16.2, 3: 14.0, 4: 11.8, 5: 9.6, 6: 7.4, 7: 5.2, 8: 2.6}
    pnames = {
        1: "Manage User Account\nand Access",
        2: "Manage Vehicle and\nDriver Record",
        3: "Process Vehicle\nInspection",
        4: "Manage Damage Report\nand Repair",
        5: "Manage Preventive\nMaintenance",
        6: "Manage Vehicle\nDispatch",
        7: "Generate Dashboard\nand Report",
        8: "Send Notification",
    }
    for n, yy in py.items():
        process(ax, PX, yy, PW, PH, n, pnames[n], fs=10)

    def pL(n):  # left-center anchor of a process
        return (PX, py[n] + PH / 2)
    def pR(n):
        return (PX + PW, py[n] + PH / 2)
    def pT(n):
        return (PX + PW / 2, py[n] + PH)
    def pB(n):
        return (PX + PW / 2, py[n])

    # Data stores (right column)
    SX, SW, SH = 13.2, 3.6, 0.85
    sy = {"D1": 18.6, "D2": 16.4, "D3": 13.7, "D4": 11.9, "D5": 10.2,
          "D6": 8.9, "D7": 7.5, "D8": 6.1, "D9": 2.8}
    snames = {"D1": "Agencies", "D2": "Users", "D3": "Vehicles", "D4": "Inspections",
              "D5": "Damage Reports", "D6": "Repair Logs", "D7": "PM Schedules",
              "D8": "Dispatch Logs", "D9": "Notifications"}
    for d, yy in sy.items():
        store(ax, SX, yy, SW, SH, d, snames[d])

    def sL(d):
        return (SX, sy[d] + SH / 2)

    # Entities (left column)
    entity(ax, 0.5, 14.9, 2.7, 1.2, "AUTHORIZED\nDRIVER", fs=10)
    entity(ax, 0.5, 8.6, 2.7, 1.2, "AGENCY\nADMINISTRATOR", fs=10)
    entity(ax, 0.5, 1.9, 2.7, 1.2, "FIREBASE CLOUD\nMESSAGING (FCM)", fs=9)

    def eDrv():
        return (3.2, 15.5)
    def eAdm():
        return (3.2, 9.2)
    def eFcm():
        return (3.2, 2.5)

    # ---- Driver flows ----
    arrow(ax, eDrv(), pL(1), "Login Credentials /\nRegistration Request /\nProfile Update", lpos=0.46, dy=0.35, fs=7)
    arrow(ax, eDrv(), pL(3), "Inspection\nSubmission", lpos=0.5, dy=0.25, fs=7)
    arrow(ax, eDrv(), pL(4), "Damage Report", lpos=0.55, dy=0.2, fs=7)
    arrow(ax, pL(2), (3.2, 15.1), "Assigned Vehicle\nInformation", lpos=0.5, dy=-0.25, fs=7)
    arrow(ax, pB(8), (3.2, 2.9), "Driver Notification", lpos=0.6, dy=-0.25, fs=7, conn="arc3,rad=0.1")

    # ---- Admin flows ----
    arrow(ax, eAdm(), pL(1), "Login Credentials /\nAccess Approval", lpos=0.5, dy=0.3, fs=7)
    arrow(ax, eAdm(), pL(2), "Vehicle and\nDriver Record", lpos=0.5, dy=0.25, fs=7)
    arrow(ax, eAdm(), pL(3), "Inspection Review", lpos=0.5, dy=-0.18, fs=7)
    arrow(ax, eAdm(), pL(4), "Damage Review /\nRepair Detail", lpos=0.5, dy=-0.2, fs=7)
    arrow(ax, eAdm(), pL(5), "PM Schedule", lpos=0.5, dy=-0.15, fs=7)
    arrow(ax, eAdm(), pL(6), "Dispatch Record", lpos=0.5, dy=-0.15, fs=7)
    arrow(ax, eAdm(), pL(7), "Report Request", lpos=0.55, dy=-0.15, fs=7)
    arrow(ax, pB(7), (3.2, 8.9), "Monitoring Information /\nGenerated Report", lpos=0.62, dy=-0.25, fs=7, conn="arc3,rad=-0.1")
    arrow(ax, pL(8), (3.2, 8.7), "Administrator\nNotification", lpos=0.5, dy=0.25, fs=7, conn="arc3,rad=-0.12")

    # ---- Process <-> Store flows ----
    arrow(ax, pR(1), sL("D2"), "Account Record", lpos=0.55, dy=0.18, fs=7, conn="arc3,rad=-0.08")
    arrow(ax, pR(1), sL("D1"), "Agency Profile", lpos=0.5, dy=0.18, fs=7, conn="arc3,rad=-0.12")
    arrow(ax, pR(2), sL("D3"), "Vehicle Record", lpos=0.5, dy=0.16, fs=7)
    arrow(ax, pR(2), sL("D2"), "Driver Record", lpos=0.5, dy=-0.16, fs=7, conn="arc3,rad=0.08")
    arrow(ax, pR(3), sL("D4"), "Inspection Record", lpos=0.5, dy=0.16, fs=7)
    arrow(ax, pR(3), sL("D3"), "Status Update", lpos=0.32, dy=-0.16, fs=7, conn="arc3,rad=-0.08")
    arrow(ax, pR(4), sL("D5"), "Damage Record", lpos=0.5, dy=0.16, fs=7)
    arrow(ax, pR(4), sL("D6"), "Repair Record", lpos=0.5, dy=-0.05, fs=7, conn="arc3,rad=0.06")
    arrow(ax, pR(4), sL("D3"), None, conn="arc3,rad=-0.12")
    arrow(ax, pR(5), sL("D7"), "PM Record", lpos=0.5, dy=0.16, fs=7)
    arrow(ax, pR(5), sL("D3"), None, conn="arc3,rad=-0.18")
    arrow(ax, pR(6), sL("D8"), "Dispatch Record", lpos=0.5, dy=0.16, fs=7)
    arrow(ax, pR(6), sL("D3"), None, conn="arc3,rad=-0.22")
    # P7 reads operational stores (representative bus)
    arrow(ax, sL("D8"), pR(7), "Record Query\n(D2–D8)", lpos=0.5, dy=-0.2, fs=7, conn="arc3,rad=0.18")
    # P8 notifications
    arrow(ax, sL("D5"), pR(8), "Alert Trigger\n(D2, D5, D7)", lpos=0.5, dy=-0.2, fs=7, conn="arc3,rad=0.2")
    arrow(ax, pR(8), sL("D9"), "Notification Record", lpos=0.5, dy=0.16, fs=7)
    arrow(ax, pB(8), eFcm(), "Notification Request", lpos=0.55, dy=-0.2, fs=7, conn="arc3,rad=-0.15")

    ax.text(8, 20.4, "Figure 4. Data Flow Diagram (Diagram 0) of the Rescue Vehicle Management System",
            fontsize=12, ha="center", va="center", fontweight="bold", color=EDGE)
    fig.savefig("/home/user/rvms-capstone/docs/diagrams/figure4_data_flow_diagram.png",
                dpi=150, bbox_inches="tight", facecolor="white")
    plt.close(fig)


context_diagram()
dfd_diagram()
print("done")
