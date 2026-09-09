const NAVY="#1b2a5b", LT="#eef2fb", ST="#f3f6fc";
const PCX=760, R=66, ROWH=214, TOP=140;
const W=1780; const H=TOP+7*ROWH+180;
let e=[];
e.push(`<defs><marker id="ah" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto"><path d="M0,0 L8,3 L0,6 Z" fill="${NAVY}"/></marker></defs>`);
const cy=i=>TOP+i*ROWH;
function wrap(t,n){const w=t.split(" ");const L=[];let c="";for(const x of w){if((c+" "+x).trim().length>n){L.push(c.trim());c=x;}else c=(c+" "+x).trim();}if(c)L.push(c);return L;}
function txt(x,y,t,{s=13,a="middle",b="normal",fill="#20304f"}={}){e.push(`<text x="${x}" y="${y}" text-anchor="${a}" font-family="Arial" font-size="${s}" font-weight="${b}" fill="${fill}">${t}</text>`);}
function dup(x,y){e.push(`<line x1="${x-16}" y1="${y}" x2="${x}" y2="${y+16}" stroke="${NAVY}" stroke-width="1.2"/>`);} // top-right corner diagonal
function entity(x,y,w,h,label,isDup){e.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${LT}" stroke="${NAVY}" stroke-width="1.4"/>`);if(isDup)dup(x+w,y);const ls=wrap(label,18);const ty=y+h/2-(ls.length-1)*8+5;ls.forEach((l,k)=>txt(x+w/2,ty+k*15,l,{s:12.5,b:"bold"}));}
function store(x,y,w,h,code,name,isDup){const c=64;e.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${ST}" stroke="${NAVY}" stroke-width="1.4"/>`);e.push(`<line x1="${x+c}" y1="${y}" x2="${x+c}" y2="${y+h}" stroke="${NAVY}" stroke-width="1.4"/>`);if(isDup)dup(x+w,y);txt(x+c/2,y+h/2+5,code,{s:13,b:"bold",fill:NAVY});txt(x+c+(w-c)/2,y+h/2+5,name,{s:12.5,b:"bold",fill:NAVY});}
function proc(x,y,num,name){e.push(`<circle cx="${x}" cy="${y}" r="${R}" fill="${LT}" stroke="${NAVY}" stroke-width="1.8"/>`);txt(x,y-14,num,{s:20,b:"bold",fill:NAVY});const ls=wrap(name,16);const ty=y+6+(0)-(ls.length-1)*0;ls.forEach((l,k)=>txt(x,y+10+k*14,l,{s:11,b:"bold",fill:NAVY}));}
function harrow(x1,y,x2,label,dir,labelY){ // dir 'r' arrow to right end, 'l' to left end
  if(dir==='r') e.push(`<line x1="${x1}" y1="${y}" x2="${x2}" y2="${y}" stroke="${NAVY}" stroke-width="1.4" marker-end="url(#ah)"/>`);
  else e.push(`<line x1="${x2}" y1="${y}" x2="${x1}" y2="${y}" stroke="${NAVY}" stroke-width="1.4" marker-end="url(#ah)"/>`);
  if(label) txt((x1+x2)/2,(labelY!=null?labelY:y-8),label,{s:11.5});
}
// --- entities left, stores right per row ---
const EX=70, EW=250, SX=1120, SW=380, BH=60;
const entL=EX+EW; const procL=PCX-R, procR=PCX+R; const storeL=SX;
function rowEntities(i,list){ // list of {label,dir('in'|'out'),dup}
  const y=cy(i); const n=list.length; const ys = n===1?[y]:[y-46,y+46];
  list.forEach((en,k)=>{const yy=ys[k]; entity(EX,yy-BH/2,EW,BH,en.label.split("||")[0],en.dup);
    const lab=en.label.includes("||")?en.label.split("||")[1]:"";
    if(en.dir==='in') harrow(entL,yy,procL,lab,'r'); else harrow(entL,yy,procL,lab,'l');});
}
function rowStores(i,list){ const y=cy(i); const n=list.length; const ys=n===1?[y]:[y-46,y+46];
  list.forEach((s,k)=>{const yy=ys[k]; store(SX,yy-BH/2,SW,BH,s.code,s.name,s.dup);
    // write (proc->store) upper, read (store->proc) lower
    if(s.write) harrow(procR,yy-13,storeL,s.write,'r');
    if(s.read)  harrow(procR,yy+13,storeL,s.read,'l');
  });
}
// entity/store labels via en.label "NAME||flow"
const rows=[
 {num:"1",name:"Authenticate and Manage Account",
  ent:[{label:"AUTHORIZED DRIVER||Login and Registration Credential",dir:"in"},{label:"AGENCY ADMINISTRATOR||Login Credential",dir:"in"}],
  st:[{code:"D1",name:"USERS",write:"User Account",read:"User Credential"}]},
 {num:"2",name:"Manage Vehicle and Driver Record",
  ent:[{label:"AGENCY ADMINISTRATOR||Vehicle and Driver Records",dir:"in",dup:true},{label:"AUTHORIZED DRIVER||Assigned Vehicle Information",dir:"out",dup:true}],
  st:[{code:"D1",name:"USERS",write:"Driver Record",read:"Driver Detail",dup:true},{code:"D2",name:"VEHICLES",write:"Vehicle Detail",read:"Vehicle Record"}]},
 {num:"3",name:"Process Inspection",
  ent:[{label:"AUTHORIZED DRIVER||BLOWBAGETS Inspection",dir:"in",dup:true},{label:"AGENCY ADMINISTRATOR||Inspection Review",dir:"in",dup:true}],
  st:[{code:"D3",name:"INSPECTIONS",write:"Inspection Record",read:"Inspection History"},{code:"D2",name:"VEHICLES",write:"Vehicle Status",dup:true}]},
 {num:"4",name:"Manage Damage and Repair",
  ent:[{label:"AUTHORIZED DRIVER||Damage Report",dir:"in",dup:true},{label:"AGENCY ADMINISTRATOR||Damage Review and Repair Log",dir:"in",dup:true}],
  st:[{code:"D4",name:"DAMAGE REPORTS",write:"Damage Record",read:"Damage Detail"},{code:"D5",name:"REPAIR LOGS",write:"Repair Detail",read:"Repair Record"}]},
 {num:"5",name:"Manage Preventive Maintenance",
  ent:[{label:"AGENCY ADMINISTRATOR||Preventive Maintenance Schedule",dir:"in",dup:true}],
  st:[{code:"D6",name:"PM SCHEDULES",write:"PM Record",read:"PM Schedule"},{code:"D2",name:"VEHICLES",read:"Current Mileage",dup:true}]},
 {num:"6",name:"Manage Dispatch",
  ent:[{label:"AGENCY ADMINISTRATOR||Dispatch Data",dir:"in",dup:true}],
  st:[{code:"D7",name:"DISPATCHES",write:"Dispatch Record",read:"Dispatch Details"},{code:"D2",name:"VEHICLES",write:"Vehicle Status",read:"Vehicle Details",dup:true}]},
 {num:"7",name:"Generate Dashboard and Report",
  ent:[{label:"AGENCY ADMINISTRATOR||Report Request",dir:"in",dup:true}],
  st:[{code:"D1-D7",name:"ALL RECORD STORES",read:"Record Data"}]},
 {num:"8",name:"Send Notification",
  ent:[{label:"FIREBASE CLOUD MESSAGING||Push Notification Request",dir:"out"},{label:"AGENCY ADMINISTRATOR||Admin Alerts",dir:"out",dup:true},{label:"AUTHORIZED DRIVER||Driver Notifications",dir:"out",dup:true}],
  st:[{code:"D8",name:"NOTIFICATIONS",write:"Notification Record"}]},
];
// main vertical chain between processes
for(let i=0;i<7;i++){e.push(`<line x1="${PCX}" y1="${cy(i)+R}" x2="${PCX}" y2="${cy(i+1)-R}" stroke="${NAVY}" stroke-width="1.4"/>`);}
// draw rows
rows.forEach((r,i)=>{
  // row8 has 3 entities: handle spacing
  if(r.ent.length===3){const y=cy(i);const ys=[y-70,y,y+70];r.ent.forEach((en,k)=>{const yy=ys[k];entity(EX,yy-27,EW,54,en.label.split("||")[0],en.dup);harrow(entL,yy,procL,en.label.split("||")[1],en.dir==='in'?'r':'l');});}
  else rowEntities(i,r.ent);
  rowStores(i,r.st);
  proc(PCX,cy(i),r.num,r.name);
});
// --- feedback flows into P8 (right margin bus) ---
const P8=cy(7);
const fb=[
 {i:0,label:"New Access Request",bx:1600},
 {i:1,label:"License Alert",bx:1625},
 {i:3,label:"New Damage Report",bx:1650},
 {i:4,label:"Preventive Maintenance Due Alert",bx:1675},
 {i:5,label:"Vehicle Status Update",bx:1700},
];
fb.forEach((f,idx)=>{
  const y=cy(f.i); const gy=y+ROWH/2; // gap below row (clear of stores)
  const entY=P8-40+idx*16; // staggered entry near P8 right
  const d=`M ${PCX+R} ${y} L ${PCX+R+8} ${y} L ${SX+SW+30} ${y} L ${f.bx} ${y} L ${f.bx} ${entY} L ${PCX+R} ${entY}`;
  e.push(`<path d="${d}" fill="none" stroke="${NAVY}" stroke-width="1.2" marker-end="url(#ah)"/>`);
  txt((SX+SW+30+f.bx)/2, y-8, f.label, {s:11});
});
console.log(`<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}"><rect width="${W}" height="${H}" fill="#fff"/>${e.join("")}</svg>`);
