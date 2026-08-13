"""Figure 3 — Context Diagram of the Rescue Vehicle Management System.

Corrects two spellings carried by the draw.io source: "Recods" -> "Records",
and "Repair Request" -> "Report Request" (the administrator requests reports;
there is no repair-request flow anywhere in the system).
"""
from dlib import *

import math
C = Canvas(1280, 700); c = C

def entity(x, y, w, h, name):
    c.rect(x, y, w, h, fill=FILL)
    c.wrapped(x+w/2, y+h/2, name, 12, True, NAVY, width=w-24)
    return dict(x=x, y=y, w=w, h=h, cx=x+w/2, cy=y+h/2, r=x+w, b=y+h)

# --- the single process ------------------------------------------------------
px, py, pw, ph = 470, 160, 340, 290
c.ellipse(px, py, pw, ph, fill=(233, 239, 249))
ECX, ECY, ERX, ERY = px+pw/2, py+ph/2, pw/2, ph/2
c.text(ECX, py+72, "0", 22, True, NAVY, anchor="ct")
c.wrapped(ECX, ECY+22, "RESCUE VEHICLE MANAGEMENT SYSTEM", 13, True, NAVY, width=pw-80)

def edge(y, right):
    """Where a horizontal line at y meets the ellipse, so arrows touch the curve."""
    t = (y - ECY) / ERY
    dx = ERX * math.sqrt(max(0.0, 1 - t*t))
    return ECX + dx if right else ECX - dx

drv = entity(55,  195, 210, 210, "AUTHORIZED DRIVER")
adm = entity(1015, 150, 210, 300, "AGENCY ADMINISTRATOR")
fcm = entity(530,  545, 210, 100, "FIREBASE CLOUD MESSAGING")

def flow(x1, x2, y, text):
    """Horizontal data flow; the label wraps above its own run."""
    c.arrow([(x1, y), (x2, y)])
    lo, hi = min(x1, x2), max(x1, x2)
    c.wrapped((lo+hi)/2, y-16, text, 8.5, False, (66, 76, 96), width=hi-lo-6)

# driver -> system
flow(drv['r'], edge(225, False), 225, "Login and Registration Credentials")
flow(drv['r'], edge(273, False), 273, "BLOWBAGETS Inspection")
flow(drv['r'], edge(313, False), 313, "Damage Report")
# system -> driver
flow(edge(357, False), drv['r'], 357, "Assigned Vehicle Information")
flow(edge(395, False), drv['r'], 395, "Driver Notifications")

# administrator -> system
flow(adm['x'], edge(187, True), 187, "Login Credentials")
flow(adm['x'], edge(233, True), 233, "Vehicle and Driver Records")
flow(adm['x'], edge(285, True), 285, "Dispatch, Repair and Maintenance Data")
flow(adm['x'], edge(325, True), 325, "Report Request")
# system -> administrator
flow(edge(369, True), adm['x'], 369, "Dashboard and Summary Records")
flow(edge(409, True), adm['x'], 409, "Generated Reports")
flow(edge(437, True), adm['x'], 437, "Admin Alerts")

# system -> FCM
c.arrow([(ECX, py+ph), (ECX, fcm['y'])])
c.wrapped(ECX-100, (py+ph+fcm['y'])/2, "Push Notification Request", 8.5, False, (66,76,96), width=150)

C.save("fig3-context-diagram.png"); print("fig3", C.img.size)
