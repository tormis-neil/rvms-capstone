"""Figure 2 — System Architecture of the Rescue Vehicle Management System."""
from dlib import *

C = Canvas(1200, 660); c = C

def node(x, y, w, h, title, sub=None, fill=FILL, tsize=12.5, dashed=False):
    c.rect(x, y, w, h, fill=fill, radius=8)
    if sub:
        n = c.wrapped(x+w/2, y+h/2-10, title, tsize, True, NAVY, width=w-26)
        c.wrapped(x+w/2, y+h/2+13+(n-1)*6, sub, 9, False, GREY, width=w-22)
    else:
        c.wrapped(x+w/2, y+h/2, title, tsize, True, NAVY, width=w-26)
    return dict(x=x, y=y, w=w, h=h, cx=x+w/2, cy=y+h/2, r=x+w, b=y+h)

BANDS = [(40, 320, "PRESENTATION TIER"), (470, 300, "APPLICATION TIER"), (880, 280, "DATA AND EXTERNAL SERVICES")]
for bx, bw, lab in BANDS:
    c.d.rectangle([bx*SC, 46*SC, (bx+bw)*SC, 500*SC], outline=LGREY, width=max(1, round(1.2*SC)))
    c.text(bx+bw/2, 57, lab, 8.5, True, GREY, anchor="ct")

drv = node(70,  120, 250, 92,  "Authorized Driver", "Android mobile application")
adm = node(70,  350, 250, 92,  "Agency Administrator", "Web dashboard in a browser")
api = node(500, 120, 240, 322, "RVMS Server", "Laravel 11 REST API and web dashboard")
db  = node(910, 120, 220, 92,  "MySQL Database", "Centralized records")
fcm = node(910, 350, 220, 92,  "Firebase Cloud Messaging", "Google push service")

def flow(x1, x2, y, label, second=None):
    """A straight horizontal arrow with its label sitting above the run."""
    c.arrow([(x1, y), (x2, y)])
    mid = (x1+x2)/2
    if second:
        c.text(mid, y-24, label,  8.5, False, (70,80,100), anchor="ct")
        c.text(mid, y-13, second, 8.5, False, (70,80,100), anchor="ct")
    else:
        c.text(mid, y-13, label, 8.5, False, (70,80,100), anchor="ct")

flow(drv['r'], api['x'], 152, "Submits inspection and", "damage report")
flow(api['x'], drv['r'], 196, "Returns assigned vehicle", "information")

flow(adm['r'], api['x'], 378, "Manages vehicles, drivers,", "dispatch and maintenance")
flow(api['x'], adm['r'], 424, "Returns fleet status and", "generated reports")

flow(api['r'], db['x'],  152, "Stores and updates records")
flow(db['x'],  api['r'], 196, "Retrieves records")
flow(api['r'], fcm['x'], 378, "Triggers notifications")

# FCM delivers the push straight to the handset, not back through the server.
c.arrow([(fcm['cx'], fcm['b']), (fcm['cx'], 540), (drv['cx'], 540), (drv['cx'], drv['b'])])
c.text((fcm['cx']+drv['cx'])/2, 527, "Delivers push notifications to the driver's device",
       8.5, False, (70,80,100), anchor="ct")

c.text(600, 590, "Every request from either platform passes through the RVMS Server; only the server reads or writes the database.",
       9, False, GREY, anchor="ct")

C.save("fig2-system-architecture.png"); print("fig2", C.img.size)
