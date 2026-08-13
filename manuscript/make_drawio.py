"""Emit the ERD as a draw.io file with the same layout as the rendered PNG."""
import html
import routes as R
from erd_model import TABLES, RELATIONSHIPS

RH, HDR, W = R.RH, R.HDR, R.W
cells, ids = [], {}

def esc(s): return html.escape(str(s), quote=True)

TBL = ("shape=table;startSize={hdr};container=1;collapsible=0;childLayout=tableLayout;"
       "fixedRows=1;rowLines=1;fontStyle=1;align=center;resizeLast=1;html=1;"
       "fillColor=#FFFFFF;strokeColor=#1F3864;strokeWidth=1.5;fontSize=13;")
ROW = ("shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;"
       "collapsible=0;dropTarget=0;points=[[0,0.5,0,0,0],[1,0.5,0,0,0]];portConstraint=eastwest;"
       "top=0;left=0;right=0;bottom=0;")
CELL = ("shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;"
        "align={align};spacingLeft={sl};spacingRight={sr};overflow=hidden;html=1;fontSize={fs};{extra}")

def cell(cid, parent, value, style, x=None, y=None, w=None, h=None, alt=None):
    attrs = ""
    if x is not None: attrs += 'x="%s" ' % x
    if y is not None: attrs += 'y="%s" ' % y
    geo = '<mxGeometry %swidth="%s" height="%s" as="geometry">' % (attrs, w, h)
    if alt: geo += '<mxRectangle width="%s" height="%s" as="alternateBounds"/>' % (w, h)
    geo += '</mxGeometry>'
    cells.append('<mxCell id="%s" value="%s" style="%s" vertex="1" parent="%s">%s</mxCell>'
                 % (cid, esc(value), style, parent, geo))

for t in TABLES:
    b = R.B(t); tid = f"tbl_{t}"; ids[t] = tid
    cell(tid, "1", t, TBL.format(hdr=HDR), b['x'], b['y'], W, b['h'])
    for i,(fn,ty,nu,key,ref,desc) in enumerate(TABLES[t]):
        rid = f"{tid}_r{i}"; ids[(t,fn)] = rid
        cell(rid, tid, "", ROW, None, HDR+i*RH, W, RH)
        badge = key if key in ("PK","FK") else ""
        bold = "fontStyle=1;fontColor=#1F3864;" if badge else ""
        cell(f"{rid}_a", rid, badge, CELL.format(align="left", sl=6, sr=0, fs=9, extra="fontColor=#8A94A6;fontStyle=1;"),
             None, None, 34, RH, alt=True)
        cell(f"{rid}_b", rid, fn, CELL.format(align="left", sl=2, sr=0, fs=10, extra=bold),
             None, None, 160, RH, alt=True)
        cell(f"{rid}_c", rid, ty, CELL.format(align="right", sl=0, sr=6, fs=9, extra="fontColor=#8A94A6;"),
             None, None, W-194, RH, alt=True)

EDGE = ("edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;exitX={ex};exitY={ey};exitDx=0;exitDy=0;"
        "entryX={nx};entryY={ny};entryDx=0;entryDy=0;startArrow=ERone;startFill=0;endArrow=ERmany;"
        "endFill=0;strokeColor=#1F3864;strokeWidth=1.3;jumpStyle=arc;jumpSize=8;")

for i,(parent, child, label, fk) in enumerate(RELATIONSHIPS):
    pid, cid = ids[parent], ids.get((child, fk), ids[child])
    pb, cb = R.B(parent), R.B(child)
    if pb['y'] == cb['y']:                       # same row -> side to side
        ex,ey,nx,ny = (1,0.5,0,0.5) if pb['x'] < cb['x'] else (0,0.5,1,0.5)
    elif parent == "agencies":                   # agency lane runs underneath
        ex,ey,nx,ny = 0.15,1,0.15,1
    else:                                        # driver / vehicle lanes run above
        ex,ey,nx,ny = 0.5,1,0.5,0
    cells.append(
        f'<mxCell id="edge{i}" value="{esc(label)}" '
        f'style="{EDGE.format(ex=ex,ey=ey,nx=nx,ny=ny)}fontSize=9;fontColor=#5A6478;" '
        f'edge="1" parent="1" source="{pid}" target="{cid}"><mxGeometry relative="1" as="geometry"/></mxCell>')

xml = ('<mxfile host="app.diagrams.net" pages="1">\n'
       '  <diagram name="entity relationship diagram" id="rvms-erd">\n'
       f'    <mxGraphModel dx="1400" dy="900" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" '
       f'arrows="1" fold="1" page="1" pageScale="1" pageWidth="1169" pageHeight="827" math="0" shadow="0">\n'
       '      <root>\n        <mxCell id="0" />\n        <mxCell id="1" parent="0" />\n        '
       + "\n        ".join(cells) +
       '\n      </root>\n    </mxGraphModel>\n  </diagram>\n</mxfile>\n')
open("rvms-erd.drawio","w").write(xml)
print("rvms-erd.drawio", len(cells), "cells,", len(RELATIONSHIPS), "edges")
