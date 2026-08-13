"""Shared drawing helpers for the RVMS Chapter 4 figures.

Everything renders at SC× and is saved at full resolution, so the text stays
crisp when Word scales the image down to fit a page.
"""
from PIL import Image, ImageDraw, ImageFont

SC = 3
F  = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
BF = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"

NAVY  = (31, 56, 100)
INK   = (34, 34, 34)
GREY  = (120, 130, 148)
LGREY = (208, 214, 226)
FILL  = (243, 246, 251)
WHITE = (255, 255, 255)

_fonts = {}
def font(size, bold=False):
    k = (size, bold)
    if k not in _fonts: _fonts[k] = ImageFont.truetype(BF if bold else F, int(size*SC))
    return _fonts[k]

class Canvas:
    def __init__(self, w, h, bg=WHITE):
        self.w, self.h = w, h
        self.img = Image.new("RGB", (int(w*SC), int(h*SC)), bg)
        self.d = ImageDraw.Draw(self.img)

    # ---- primitives -------------------------------------------------------
    def line(self, pts, colour=NAVY, width=1.4):
        self.d.line([(round(x*SC), round(y*SC)) for x, y in pts],
                    fill=colour, width=max(1, round(width*SC)), joint="curve")

    def rect(self, x, y, w, h, fill=WHITE, outline=NAVY, width=1.6, radius=0):
        box = [x*SC, y*SC, (x+w)*SC, (y+h)*SC]
        if radius:
            self.d.rounded_rectangle(box, radius=radius*SC, fill=fill,
                                     outline=outline, width=max(1, round(width*SC)))
        else:
            self.d.rectangle(box, fill=fill, outline=outline, width=max(1, round(width*SC)))

    def ellipse(self, x, y, w, h, fill=WHITE, outline=NAVY, width=1.6):
        self.d.ellipse([x*SC, y*SC, (x+w)*SC, (y+h)*SC], fill=fill,
                       outline=outline, width=max(1, round(width*SC)))

    def tw(self, text, size, bold=False):
        return self.d.textlength(text, font=font(size, bold)) / SC

    def text(self, x, y, s, size=10, bold=False, colour=INK, anchor="lt"):
        f = font(size, bold)
        w = self.d.textlength(s, font=f) / SC
        if   anchor[0] == "c": x -= w/2
        elif anchor[0] == "r": x -= w
        if len(anchor) > 1 and anchor[1] == "m": y -= size*0.62
        self.d.text((x*SC, y*SC), s, font=f, fill=colour)

    def wrapped(self, cx, cy, s, size=10, bold=False, colour=INK, width=None, lead=1.32):
        """Centre a string, breaking it onto several lines if width is given."""
        words, lines, cur = s.split(), [], ""
        for wd in words:
            t = (cur + " " + wd).strip()
            if width and self.tw(t, size, bold) > width and cur:
                lines.append(cur); cur = wd
            else:
                cur = t
        if cur: lines.append(cur)
        lh = size*lead
        y0 = cy - lh*len(lines)/2
        for i, ln in enumerate(lines):
            self.text(cx, y0 + i*lh, ln, size, bold, colour, anchor="ct")
        return len(lines)

    # ---- arrows -----------------------------------------------------------
    def arrowhead(self, x, y, dx, dy, size=7, colour=NAVY):
        import math
        a = math.atan2(dy, dx)
        p = [(x, y),
             (x - size*math.cos(a - 0.42), y - size*math.sin(a - 0.42)),
             (x - size*math.cos(a + 0.42), y - size*math.sin(a + 0.42))]
        self.d.polygon([(px*SC, py*SC) for px, py in p], fill=colour)

    def arrow(self, pts, colour=NAVY, width=1.4, head=True, back=False):
        self.line(pts, colour, width)
        if head:
            (x1, y1), (x2, y2) = pts[-2], pts[-1]
            self.arrowhead(x2, y2, x2-x1, y2-y1, colour=colour)
        if back:
            (x1, y1), (x2, y2) = pts[1], pts[0]
            self.arrowhead(x2, y2, x2-x1, y2-y1, colour=colour)

    def label(self, x, y, s, size=8.5, colour=(70, 80, 100), plate=True, anchor="cm"):
        w = self.tw(s, size); h = size*1.15
        if plate:
            self.d.rectangle([(x-w/2-3)*SC, (y-h/2-1.5)*SC, (x+w/2+3)*SC, (y+h/2+1.5)*SC], fill=WHITE)
        self.text(x, y, s, size, False, colour, anchor=anchor)

    def save(self, path):
        self.img.save(path)
        return self.img.size
