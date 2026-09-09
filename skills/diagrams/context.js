const NAVY="#1b2a5b", LT="#eef2fb";
const W=2040,H=940;
const CX=1015, CY=430, R=210;
function arcX(y,side){const dy=Math.max(-R+1,Math.min(R-1,y-CY));const dx=Math.sqrt(R*R-dy*dy);return side<0?CX-dx:CX+dx;}
let e=[];
e.push(`<defs>
<marker id="ah" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6 Z" fill="${NAVY}"/></marker>
</defs>`);
// central process
e.push(`<circle cx="${CX}" cy="${CY}" r="${R}" fill="${LT}" stroke="${NAVY}" stroke-width="2"/>`);
e.push(`<text x="${CX}" y="${CY-40}" text-anchor="middle" font-family="Arial" font-size="30" font-weight="bold" fill="${NAVY}">0</text>`);
e.push(`<text x="${CX}" y="${CY+15}" text-anchor="middle" font-family="Arial" font-size="17" font-weight="bold" fill="${NAVY}">RESCUE VEHICLE MANAGEMENT</text>`);
e.push(`<text x="${CX}" y="${CY+42}" text-anchor="middle" font-family="Arial" font-size="17" font-weight="bold" fill="${NAVY}">SYSTEM</text>`);
function box(x,y,w,h,label){e.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${LT}" stroke="${NAVY}" stroke-width="2"/>`);e.push(`<text x="${x+w/2}" y="${y+h/2+6}" text-anchor="middle" font-family="Arial" font-size="18" font-weight="bold" fill="${NAVY}">${label}</text>`);}
// entities
box(90,300,270,260,"AUTHORIZED DRIVER");
box(1680,250,280,360,"AGENCY ADMINISTRATOR");
e.push(`<rect x="${CX-150}" y="770" width="300" height="115" fill="${LT}" stroke="${NAVY}" stroke-width="2"/>`);
e.push(`<text x="${CX}" y="820" text-anchor="middle" font-family="Arial" font-size="18" font-weight="bold" fill="${NAVY}">FIREBASE CLOUD</text>`);
e.push(`<text x="${CX}" y="847" text-anchor="middle" font-family="Arial" font-size="18" font-weight="bold" fill="${NAVY}">MESSAGING</text>`);
// helper flow: dir 'in' arrow points to circle, 'out' points to entity
function flow(side,y,label,dir){
  const boxEdge = side<0?360:1680;
  const cxEdge = arcX(y,side);
  const x1=Math.min(boxEdge,cxEdge), x2=Math.max(boxEdge,cxEdge);
  // determine arrow end
  let ax1,ax2;
  if(side<0){ // left entity
    if(dir=='in'){ax1=boxEdge;ax2=cxEdge;} else {ax1=cxEdge;ax2=boxEdge;}
  } else { // right entity
    if(dir=='in'){ax1=boxEdge;ax2=cxEdge;} else {ax1=cxEdge;ax2=boxEdge;}
  }
  e.push(`<line x1="${ax1}" y1="${y}" x2="${ax2}" y2="${y}" stroke="${NAVY}" stroke-width="1.5" marker-end="url(#ah)"/>`);
  const mx=(boxEdge+cxEdge)/2;
  e.push(`<text x="${mx}" y="${y-9}" text-anchor="middle" font-family="Arial" font-size="13" fill="#233">${label}</text>`);
}
// driver flows (5)
const dl=[["Login and Registration Credentials","in"],["BLOWBAGETS Inspection","in"],["Damage Report","in"],["Assigned Vehicle Information","out"],["Driver Notifications","out"]];
dl.forEach((f,i)=>flow(-1,320+i*52,f[0],f[1]));
// admin flows (7)
const al=[["Login Credentials","in"],["Vehicle and Driver Records","in"],["Dispatch, Repair and Maintenance Data","in"],["Report Request","in"],["Dashboard and Summary Records","out"],["Generated Reports","out"],["Admin Alerts","out"]];
al.forEach((f,i)=>flow(1,300+i*46,f[0],f[1]));
// system -> FCM (down)
e.push(`<line x1="${CX}" y1="${CY+R}" x2="${CX}" y2="770" stroke="${NAVY}" stroke-width="1.5" marker-end="url(#ah)"/>`);
e.push(`<text x="${CX+12}" y="${(CY+R+780)/2}" text-anchor="start" font-family="Arial" font-size="13" fill="#233">Push Notification Request</text>`);
console.log(`<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}"><rect width="${W}" height="${H}" fill="#fff"/>${e.join("")}</svg>`);
