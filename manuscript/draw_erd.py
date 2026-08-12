from PIL import Image, ImageDraw, ImageFont
import routes as R
from erd_model import TABLES

SC = 3   # supersample: 3x gives print-quality text
W, H = R.CANVAS_W, R.CANVAS_H
img = Image.new("RGB", (W*SC, H*SC), "white"); d = ImageDraw.Draw(img)
F  = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
BF = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
ft  = ImageFont.truetype(BF, int(12.5*SC)); fr = ImageFont.truetype(F, int(9.5*SC))
frb = ImageFont.truetype(BF, int(9.5*SC));  fs = ImageFont.truetype(BF, int(8.5*SC))
fy  = ImageFont.truetype(F,  int(8.5*SC));  fl = ImageFont.truetype(F, int(10*SC))
flb = ImageFont.truetype(BF, int(10.5*SC))
NAVY=(31,56,100); GREY=(138,148,166); TXT=(43,43,43); RULE=(223,228,238)

def L(pts, w=1.3):
    d.line([(round(x*SC), round(y*SC)) for x, y in pts], fill=NAVY, width=max(1, round(w*SC)))

def one_end(ax,x,y,dd):
    L([(x+11*dd,y-7),(x+11*dd,y+7)],1.8) if ax=='h' else L([(x-7,y+11*dd),(x+7,y+11*dd)],1.8)
def many_end(ax,x,y,dd):
    for o in (-7,0,7):
        L([(x+14*dd,y),(x,y+o)],1.5) if ax=='h' else L([(x,y+14*dd),(x+o,y)],1.5)

for pts, oe, me in R.EDGES:
    L(pts); one_end(*oe); many_end(*me)

for t in TABLES:
    b = R.B(t); x, y, bh = b['x'], b['y'], b['h']
    d.rectangle([x*SC,y*SC,(x+R.W)*SC,(y+bh)*SC], fill="white", outline=NAVY, width=max(1,round(1.7*SC)))
    d.rectangle([x*SC,y*SC,(x+R.W)*SC,(y+R.HDR)*SC], fill=NAVY)
    tw = d.textlength(t, font=ft); d.text(((x+R.W/2)*SC-tw/2, (y+5)*SC), t, font=ft, fill="white")
    for i,(fn,ty,nu,key,ref,desc) in enumerate(TABLES[t]):
        ry = y+R.HDR+i*R.RH
        if i: d.line([(x+1)*SC,ry*SC,(x+R.W-1)*SC,ry*SC], fill=RULE, width=max(1,round(0.7*SC)))
        bd = key if key in ("PK","FK") else ""
        if bd: d.text(((x+7)*SC,(ry+3.5)*SC), bd, font=fs, fill=GREY)
        d.text(((x+31)*SC,(ry+3)*SC), fn, font=(frb if bd else fr), fill=(NAVY if bd else TXT))
        tw = d.textlength(ty, font=fy); d.text(((x+R.W-7)*SC-tw,(ry+3.5)*SC), ty, font=fy, fill=GREY)

# ---- legend ----------------------------------------------------------------
lx, ly, lw, lh = R.B("inspection_items")['x'], 40, R.W, 96
d.rectangle([lx*SC,ly*SC,(lx+lw)*SC,(ly+lh)*SC], fill="white", outline=GREY, width=max(1,round(1.2*SC)))
d.text(((lx+10)*SC,(ly+7)*SC), "Notation", font=flb, fill=NAVY)

y0 = ly+34
L([(lx+16,y0),(lx+56,y0)]); one_end('h', lx+56, y0, -1)
d.text(((lx+76)*SC,(y0-7)*SC), "exactly one", font=fl, fill=TXT)

y1 = ly+62
L([(lx+16,y1),(lx+56,y1)]); many_end('h', lx+56, y1, -1)
d.text(((lx+76)*SC,(y1-7)*SC), "many", font=fl, fill=TXT)

d.text(((lx+180)*SC,(y0-7)*SC), "PK  primary key", font=fl, fill=TXT)
d.text(((lx+180)*SC,(y1-7)*SC), "FK  foreign key", font=fl, fill=TXT)

img.save("rvms-erd.png")
print("erd.png", img.size)
