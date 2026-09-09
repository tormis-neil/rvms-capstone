const NAVY="#1b2a5b", MAJ="#dfe6f5", SUB="#ffffff";
const cols=[
 {t:"USER MANAGEMENT", s:["User Login and Authentication","Driver Registration","Access Request Approval","Create Administrator Account","Profile Management","Role and Agency Access Control","Password Recovery"]},
 {t:"VEHICLE AND DRIVER RECORD MANAGEMENT", s:["Manage Vehicle Records","Manage Driver Records","View Assigned Vehicle","Monitor License Expiry","Update Vehicle Status"]},
 {t:"INSPECTION MANAGEMENT", s:["Submit BLOWBAGETS Inspection","Review Inspection","View Inspection History"]},
 {t:"DAMAGE AND REPAIR MANAGEMENT", s:["Submit Damage Report","Review Damage Report","Log Repair Activity"]},
 {t:"PREVENTIVE MAINTENANCE MANAGEMENT", s:["Create PM Schedule","Recompute PM Status","Record PM Completion"]},
 {t:"DISPATCH MANAGEMENT", s:["Open Dispatch","Close Dispatch and Return Status","Monitor Vehicle Availability"]},
 {t:"DASHBOARD AND REPORT GENERATION", s:["View Dashboard Summary","Monitor Frequent Issues","Generate Reports"]},
 {t:"NOTIFICATION MANAGEMENT", s:["Send In-App Notification","Send Notification via FCM","Manage Notification List"]},
];
function wrap(t,n){const w=t.split(" ");const L=[];let c="";for(const x of w){if((c+" "+x).trim().length>n){L.push(c.trim());c=x;}else c=(c+" "+x).trim();}if(c)L.push(c);return L;}
const pitch=262, x0=150, majW=232, majH=64, subW=196, subH=50, subGap=16;
const subTop=290, rootY=24, rootH=56, majY=150;
const width = x0 + 7*pitch + 150;
const maxRows=Math.max(...cols.map(c=>c.s.length));
const height = subTop + maxRows*(subH+subGap) + 120;
const cx=i=>x0+i*pitch;
const rootCx=(cx(0)+cx(7))/2;
let e=[];
// root
e.push(`<rect x="${rootCx-200}" y="${rootY}" width="400" height="${rootH}" rx="4" fill="${NAVY}"/>`);
e.push(`<text x="${rootCx}" y="${rootY+rootH/2+5}" text-anchor="middle" font-family="Arial" font-size="17" font-weight="bold" fill="#fff">RESCUE VEHICLE MANAGEMENT SYSTEM</text>`);
// bus
const busY=majY-26;
e.push(`<line x1="${rootCx}" y1="${rootY+rootH}" x2="${rootCx}" y2="${busY}" stroke="${NAVY}" stroke-width="1.5"/>`);
e.push(`<line x1="${cx(0)}" y1="${busY}" x2="${cx(7)}" y2="${busY}" stroke="${NAVY}" stroke-width="1.5"/>`);
cols.forEach((c,i)=>{
  const X=cx(i);
  e.push(`<line x1="${X}" y1="${busY}" x2="${X}" y2="${majY}" stroke="${NAVY}" stroke-width="1.5"/>`);
  // major box
  const ml=wrap(c.t,26);
  e.push(`<rect x="${X-majW/2}" y="${majY}" width="${majW}" height="${majH}" rx="4" fill="${MAJ}" stroke="${NAVY}" stroke-width="1.5"/>`);
  const mty=majY+majH/2-(ml.length-1)*8+4;
  ml.forEach((ln,k)=>e.push(`<text x="${X}" y="${mty+k*15}" text-anchor="middle" font-family="Arial" font-size="12.5" font-weight="bold" fill="${NAVY}">${ln}</text>`));
  // spine
  const spineX=X-subW/2-14;
  const lastMid=subTop+(c.s.length-1)*(subH+subGap)+subH/2;
  e.push(`<line x1="${X}" y1="${majY+majH}" x2="${X}" y2="${subTop-14}" stroke="${NAVY}" stroke-width="1.3"/>`);
  e.push(`<line x1="${spineX}" y1="${subTop-14}" x2="${X}" y2="${subTop-14}" stroke="${NAVY}" stroke-width="1.3"/>`);
  e.push(`<line x1="${spineX}" y1="${subTop-14}" x2="${spineX}" y2="${lastMid}" stroke="${NAVY}" stroke-width="1.3"/>`);
  c.s.forEach((s,j)=>{
    const y=subTop+j*(subH+subGap), mid=y+subH/2;
    e.push(`<line x1="${spineX}" y1="${mid}" x2="${X-subW/2}" y2="${mid}" stroke="${NAVY}" stroke-width="1.3"/>`);
    e.push(`<rect x="${X-subW/2}" y="${y}" width="${subW}" height="${subH}" rx="4" fill="${SUB}" stroke="${NAVY}" stroke-width="1.3"/>`);
    const sl=wrap(s,24);
    const sty=mid-(sl.length-1)*7+4;
    sl.forEach((ln,k)=>e.push(`<text x="${X}" y="${sty+k*14}" text-anchor="middle" font-family="Arial" font-size="11.5" fill="#111">${ln}</text>`));
  });
});
console.log(`<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><rect width="${width}" height="${height}" fill="#fff"/>${e.join("")}</svg>`);
