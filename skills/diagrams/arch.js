const NAVY="#1b2a5b", CYAN="#29abe2", GRAY="#8a94a6", CLOUD="#c9d3e6";
const W=1860,H=760; let e=[];
e.push(`<defs><marker id="ah" markerWidth="11" markerHeight="11" refX="8" refY="3.2" orient="auto"><path d="M0,0 L8,3.2 L0,6.4 Z" fill="${NAVY}"/></marker></defs>`);
function person(cx,cy,c){const r=26;e.push(`<circle cx="${cx}" cy="${cy-r-6}" r="${r}" fill="${c}"/>`);e.push(`<path d="M ${cx-46} ${cy+46} a 46 46 0 0 1 92 0 Z" fill="${c}"/>`);}
function label(x,y,t,anchor="middle",size=13,w="bold"){e.push(`<text x="${x}" y="${y}" text-anchor="${anchor}" font-family="Arial" font-size="${size}" font-weight="${w}" fill="${NAVY}">${t}</text>`);}
function phone(cx,cy){const w=78,h=140;e.push(`<rect x="${cx-w/2}" y="${cy-h/2}" width="${w}" height="${h}" rx="12" fill="${CYAN}"/>`);e.push(`<rect x="${cx-w/2+7}" y="${cy-h/2+16}" width="${w-14}" height="${h-32}" rx="4" fill="#eaf6fd"/>`);e.push(`<circle cx="${cx}" cy="${cy-h/2+9}" r="3" fill="#eaf6fd"/>`);}
function cyl(cx,cy){const w=150,h=170,ry=26;const x=cx-w/2,yt=cy-h/2;
 e.push(`<path d="M ${x} ${yt+ry} a ${w/2} ${ry} 0 0 1 ${w} 0 L ${x+w} ${yt+h-ry} a ${w/2} ${ry} 0 0 1 -${w} 0 Z" fill="${GRAY}"/>`);
 e.push(`<ellipse cx="${cx}" cy="${yt+ry}" rx="${w/2}" ry="${ry}" fill="#aab3c2" stroke="#6b7688"/>`);
 e.push(`<path d="M ${x} ${yt+ry+45} a ${w/2} ${ry} 0 0 0 ${w} 0" fill="none" stroke="#e8f4fb" stroke-width="4"/>`);
 e.push(`<path d="M ${x} ${yt+ry+90} a ${w/2} ${ry} 0 0 0 ${w} 0" fill="none" stroke="#e8f4fb" stroke-width="4"/>`);}
function laptop(cx,cy){const w=170,h=100;e.push(`<rect x="${cx-w/2}" y="${cy-h/2}" width="${w}" height="${h-16}" rx="6" fill="${NAVY}"/>`);e.push(`<rect x="${cx-w/2+8}" y="${cy-h/2+8}" width="${w-16}" height="${h-32}" fill="#dfe6f5"/>`);e.push(`<path d="M ${cx-w/2-16} ${cy+h/2} L ${cx-w/2} ${cy+h/2-16} L ${cx+w/2} ${cy+h/2-16} L ${cx+w/2+16} ${cy+h/2} Z" fill="${NAVY}"/>`);}
function cloud(cx,cy){const s=1.15;const p=`M ${cx-70} ${cy+18} a 34 34 0 0 1 6 -66 a 40 40 0 0 1 74 -10 a 34 34 0 0 1 52 22 a 30 30 0 0 1 -14 64 Z`;e.push(`<path d="${p}" fill="${CLOUD}" stroke="#9fb0cf" stroke-width="2"/>`);}
function arrow(x1,y1,x2,y2){e.push(`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${NAVY}" stroke-width="1.6" marker-end="url(#ah)"/>`);}
function poly(pts){let d="M "+pts.map(p=>p.join(" ")).join(" L ");e.push(`<path d="${d}" fill="none" stroke="${NAVY}" stroke-width="1.6" marker-end="url(#ah)"/>`);}
// positions
const DR=[120,210], PH=[500,180], DB=[1010,360], CL=[1660,360], LT=[500,560], AD=[120,560];
// icons
person(DR[0],DR[1],CYAN); label(DR[0],DR[1]+80,""); 
phone(PH[0],PH[1]);
cyl(DB[0],DB[1]);
cloud(CL[0],CL[1]);
laptop(LT[0],LT[1]);
person(AD[0],AD[1],NAVY);
// driver <-> phone
arrow(DR[0]+55,PH[1]-15,PH[0]-44,PH[1]-15); label((DR[0]+55+PH[0]-44)/2,PH[1]-15-14,"SUBMIT INSPECTION &",12); label((DR[0]+55+PH[0]-44)/2,PH[1]-15-1,"DAMAGE REPORT",12);
arrow(PH[0]-44,PH[1]+30,DR[0]+55,PH[1]+30); label((DR[0]+55+PH[0]-44)/2,PH[1]+30+18,"VIEW VEHICLE INFORMATION",12); label((DR[0]+55+PH[0]-44)/2,PH[1]+30+31,"& NOTIFICATIONS",12);
// phone <-> DB
arrow(PH[0]+44,PH[1]-8,DB[0]-82,DB[1]-40); label((PH[0]+44+DB[0]-82)/2+10,PH[1]-30,"SEND INSPECTION &",12); label((PH[0]+44+DB[0]-82)/2+10,PH[1]-17,"DAMAGE DATA",12);
arrow(DB[0]-82,DB[1]-8,PH[0]+44,PH[1]+30); label((PH[0]+44+DB[0]-82)/2+18,PH[1]+55,"RETRIEVE VEHICLE",12); label((PH[0]+44+DB[0]-82)/2+18,PH[1]+68,"INFORMATION",12);
// laptop <-> DB
arrow(LT[0]+80,LT[1]-10,DB[0]-82,DB[1]+30); label((LT[0]+80+DB[0]-82)/2+10,LT[1]-28,"STORE & UPDATE",12); label((LT[0]+80+DB[0]-82)/2+10,LT[1]-15,"RECORDS",12);
arrow(DB[0]-82,DB[1]+55,LT[0]+80,LT[1]+22); label((LT[0]+80+DB[0]-82)/2+16,LT[1]+52,"RETRIEVE RECORDS",12);
// admin <-> laptop
arrow(AD[0]+55,LT[1]-14,LT[0]-90,LT[1]-14); label((AD[0]+55+LT[0]-90)/2,LT[1]-14-14,"MANAGE VEHICLES, DRIVERS,",11.5); label((AD[0]+55+LT[0]-90)/2,LT[1]-14-1,"DISPATCH & MAINTENANCE",11.5);
arrow(LT[0]-90,LT[1]+28,AD[0]+55,LT[1]+28); label((AD[0]+55+LT[0]-90)/2,LT[1]+28+18,"MONITOR VEHICLE STATUS",11.5); label((AD[0]+55+LT[0]-90)/2,LT[1]+28+31,"& GENERATE REPORTS",11.5);
// DB -> cloud
arrow(DB[0]+82,DB[1],CL[0]-95,DB[1]); label((DB[0]+82+CL[0]-95)/2,DB[1]-12,"TRIGGER NOTIFICATIONS",12);
// cloud -> phone (top bus)
poly([[CL[0],CL[1]-56],[CL[0],70],[PH[0],70],[PH[0],PH[1]-70]]);
label((PH[0]+CL[0])/2,58,"PUSH NOTIFICATIONS",12);
console.log(`<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}"><rect width="${W}" height="${H}" fill="#fff"/>${e.join("")}</svg>`);
