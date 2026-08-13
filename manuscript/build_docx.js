const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType,
  AlignmentType, HeadingLevel, BorderStyle, ShadingType, ImageRun, PageOrientation,
} = require('docx');

const M = JSON.parse(fs.readFileSync('model.json','utf8'));
const N = JSON.parse(fs.readFileSync('narratives.json','utf8'));

const SERIF = 'Times New Roman';
const SANS  = 'Calibri';
const NAVY  = '1F3864';

// ---------- small builders ----------
const P = (text, o={}) => new Paragraph({
  alignment: o.align || AlignmentType.JUSTIFIED,
  spacing: { after: o.after ?? 160, line: o.line ?? 276 },
  indent: o.indent,
  children: [ new TextRun({ text, font: o.font || SERIF, size: o.size || 24,
    bold: o.bold, italics: o.italics, color: o.color }) ],
});
const Rich = (runs, o={}) => new Paragraph({
  alignment: o.align || AlignmentType.JUSTIFIED,
  spacing: { after: o.after ?? 160, line: 276 },
  children: runs.map(r => new TextRun({ font: o.font || SERIF, size: o.size || 24, ...r })),
});
const H1 = t => new Paragraph({ spacing:{before:360, after:200},
  children:[new TextRun({text:t, font:SANS, size:32, bold:true, color:NAVY})] });
const H2 = t => new Paragraph({ spacing:{before:280, after:140},
  children:[new TextRun({text:t, font:SANS, size:26, bold:true, color:NAVY})] });
const Bullet = (t, lvl=0) => new Paragraph({ bullet:{level:lvl}, spacing:{after:90},
  children:[new TextRun({text:t, font:SERIF, size:24})] });
const Spacer = (h=120) => new Paragraph({ spacing:{after:h}, children:[new TextRun('')] });

const NOTE = (title, lines) => new Table({
  width:{size:100,type:WidthType.PERCENTAGE},
  borders:{ top:{style:BorderStyle.SINGLE,size:6,color:NAVY}, bottom:{style:BorderStyle.SINGLE,size:6,color:NAVY},
            left:{style:BorderStyle.SINGLE,size:18,color:NAVY}, right:{style:BorderStyle.SINGLE,size:6,color:NAVY},
            insideHorizontal:{style:BorderStyle.NONE}, insideVertical:{style:BorderStyle.NONE} },
  rows:[ new TableRow({ children:[ new TableCell({
    shading:{type:ShadingType.CLEAR, fill:'F2F5FB'},
    margins:{top:160,bottom:160,left:200,right:200},
    children:[
      new Paragraph({spacing:{after:80}, children:[new TextRun({text:title,font:SANS,size:22,bold:true,color:NAVY})]}),
      ...lines.map(l => new Paragraph({spacing:{after:70},
        children:[new TextRun({text:l, font:SANS, size:20})]})),
    ]})]})],
});

// ---------- the data dictionary table ----------
const COLS = [2000, 1180, 620, 3520, 620, 1420];   // = 9360 dxa (6.5in)
const HEADERS = ['Field Name','Data Type','Null','Description','Key','Reference Table'];

function ddTable(name){
  const cell = (txt, i, hdr) => new TableCell({
    width:{size:COLS[i], type:WidthType.DXA},
    shading: hdr ? {type:ShadingType.CLEAR, fill:'DDE3F0'} : undefined,
    margins:{top:60,bottom:60,left:90,right:90},
    children:[ new Paragraph({ alignment: (i===2||i===4)?AlignmentType.CENTER:AlignmentType.LEFT,
      spacing:{after:0, line:230},
      children:[ new TextRun({ text: txt, font: SERIF, size: 19, bold: !!hdr }) ] }) ],
  });
  return new Table({
    width:{size:100,type:WidthType.PERCENTAGE},
    columnWidths: COLS,
    borders:{ top:{style:BorderStyle.SINGLE,size:8,color:'000000'},
              bottom:{style:BorderStyle.SINGLE,size:8,color:'000000'},
              left:{style:BorderStyle.NONE}, right:{style:BorderStyle.NONE},
              insideHorizontal:{style:BorderStyle.SINGLE,size:2,color:'BBBBBB'},
              insideVertical:{style:BorderStyle.NONE} },
    rows:[
      new TableRow({ tableHeader:true, children: HEADERS.map((h,i)=>cell(h,i,true)) }),
      ...M.tables[name].map(r => new TableRow({ children:[
        cell(r[0],0), cell(r[1],1), cell(r[2],2), cell(r[5],3), cell(r[3],4), cell(r[4],5),
      ]})),
    ],
  });
}

const children = [];
const push = (...x) => children.push(...x);

/* ============================ COVER ============================ */
push(
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{before:600,after:60},
    children:[new TextRun({text:'RESCUE VEHICLE MANAGEMENT SYSTEM',font:SANS,size:28,bold:true,color:NAVY})]}),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{after:400},
    children:[new TextRun({text:'Chapter 4 — Data Model',font:SANS,size:44,bold:true,color:NAVY})]}),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{after:500},
    children:[new TextRun({text:'Entity Relationship Diagram and Data Dictionary',font:SANS,size:26,color:'444444'})]}),
  NOTE('How to use this document', [
    'PART A is a GUIDE. It explains what an ERD is, what the eleven tables hold, and how the relationships work. Read it to understand the diagram. Do NOT paste it into the manuscript.',
    'PART B is MANUSCRIPT TEXT. Everything in Part B is written to be pasted into Chapter 4 under the Data Model section, in the order given.',
    'Two image files accompany this document: rvms-erd.png (insert this as Figure 6) and rvms-erd.drawio (the editable source, for diagrams.net).',
    'Every field, data type and relationship here was checked against the system database on 12 August 2026. Do not retype them from memory.',
  ]),
  Spacer(300),
  P('Prepared for the Chapter 4 revision · August 2026', {align:AlignmentType.CENTER, italics:true, size:20, color:'666666'}),
  new Paragraph({pageBreakBefore:true, children:[]}),
);

/* ============================ PART A ============================ */
push(H1('PART A — Understanding the Entity Relationship Diagram'),
  P('This part is for your own understanding. It is not part of the manuscript.', {italics:true}),

  H2('A1. What an Entity Relationship Diagram is'),
  P('An Entity Relationship Diagram, usually shortened to ERD, is a picture of how a system stores its information. It answers three questions at once: what kinds of information the system keeps, what details it keeps about each kind, and how those kinds are connected to one another.'),
  P('It is drawn before the database is built and kept up to date afterwards, because it is the clearest single description of the data a system holds. In the manuscript it belongs to the Data Model section of Chapter 4, alongside the Data Dictionary.'),

  H2('A2. The three things an ERD shows'),
  Rich([{text:'Entities ',bold:true},{text:'are the kinds of information the system keeps. In the diagram each entity is one box, and each box becomes one table in the database. The Rescue Vehicle Management System has eleven. A vehicle is an entity; so is a driver, an inspection, and a dispatch.'}]),
  Rich([{text:'Attributes ',bold:true},{text:'are the details kept about an entity. They are the rows listed inside each box. For a vehicle, the attributes include the plate number, the make, the model, and the current odometer reading. Every attribute has a data type, which says what kind of value it can hold — a number, a date, a piece of text, or one of a fixed list of choices.'}]),
  Rich([{text:'Relationships ',bold:true},{text:'are the lines between the boxes. They say how records in one entity are connected to records in another. A relationship is created by a foreign key: an attribute in one table that points at the primary key of another.'}]),
  Rich([{text:'Two attributes carry a marker. ',bold:true},{text:'PK marks the primary key — the attribute that gives each record its own unique identity, so that no two records can ever be confused. FK marks a foreign key — an attribute that points at the primary key of another table, and so creates a relationship. In this system every table uses a field named id as its primary key.'}]),

  H2('A3. How to read the diagram'),
  P('The diagram uses crow’s foot notation, which is the most common style for a database that has already been designed down to its columns. The symbol at each end of a line tells you how many records may take part on that side.'),
  Bullet('A single bar across the line means exactly one. Reading toward that end, one record.'),
  Bullet('A three-pronged fork, which is what gives the notation its name, means many. Reading toward that end, any number of records.'),
  P('So a line with a bar at the agencies end and a fork at the vehicles end reads: one agency owns many vehicles, and each vehicle belongs to exactly one agency. Every relationship in this system is of that kind — one record on one side, many on the other.'),
  P('The legend printed at the top right of the diagram repeats these two symbols so that a reader does not have to remember them.'),

  H2('A4. The eleven entities'),
  P('The table below lists every entity, what it holds, and how many attributes it has. The detail of each attribute is in Part B.'),
);

const rowsA4 = [
  ['agencies','The four participating agencies and the license warning period each one sets'],
  ['users','Every account that can sign in — both Agency Administrators and Authorized Drivers'],
  ['vehicles','The fleet of each agency, including the one shared operational status'],
  ['inspection_checklist_items','The standard BLOWBAGETS checklist: twelve items, plus two for BFP vehicles'],
  ['inspections','Each daily inspection submitted by a driver'],
  ['inspection_items','The result of each checklist item within an inspection'],
  ['damage_reports','Damage reported by drivers, with an optional photo'],
  ['repair_logs','Repairs carried out on a vehicle, and who performed them'],
  ['pm_schedules','Preventive maintenance plans and their completion details'],
  ['dispatches','Each deployment of a vehicle, from time out to time in'],
  ['notifications','Alerts raised for drivers and administrators'],
];
push(new Table({
  width:{size:100,type:WidthType.PERCENTAGE}, columnWidths:[2600,5700,1060],
  borders:{ top:{style:BorderStyle.SINGLE,size:8,color:'000000'}, bottom:{style:BorderStyle.SINGLE,size:8,color:'000000'},
            left:{style:BorderStyle.NONE}, right:{style:BorderStyle.NONE},
            insideHorizontal:{style:BorderStyle.SINGLE,size:2,color:'BBBBBB'}, insideVertical:{style:BorderStyle.NONE} },
  rows:[ new TableRow({tableHeader:true, children:['Entity','What it holds','Attributes'].map((h,i)=>new TableCell({
      shading:{type:ShadingType.CLEAR,fill:'DDE3F0'}, margins:{top:60,bottom:60,left:90,right:90},
      children:[new Paragraph({alignment:i===2?AlignmentType.CENTER:AlignmentType.LEFT,
        children:[new TextRun({text:h,font:SERIF,size:20,bold:true})]})]}))}),
    ...rowsA4.map(([n,w])=>new TableRow({children:[n,w,String(M.tables[n].length)].map((t,i)=>new TableCell({
      margins:{top:60,bottom:60,left:90,right:90},
      children:[new Paragraph({alignment:i===2?AlignmentType.CENTER:AlignmentType.LEFT, spacing:{line:230},
        children:[new TextRun({text:t,font:SERIF,size:20})]})]}))})),
  ],
}), Spacer(200));

push(H2('A5. The relationships, grouped'),
  P('The system has twenty-three relationships. They fall into four groups, and once you see the groups the diagram stops looking complicated.'),

  Rich([{text:'Group 1 — agency scoping (eight relationships). ',bold:true},{text:'Every operational table carries an agency_id, so every record belongs to exactly one agency. This is what makes it possible for four agencies to share one system without any of them seeing another’s records. In the diagram these eight lines run in a lane underneath the drawing and approach each table from below, so that they can be read as one family.'}]),
  Rich([{text:'Group 2 — the vehicle (five relationships). ',bold:true},{text:'A vehicle has inspections, damage reports, repair logs, preventive maintenance schedules, and dispatches. Each of those records names exactly one vehicle.'}]),
  Rich([{text:'Group 3 — the person (eight relationships). ',bold:true},{text:'A driver is the primary driver of a vehicle, submits inspections and damage reports, is assigned to repair logs, and is dispatched on missions; an administrator reviews inspections and damage reports; and any user receives notifications. Inspections and damage reports each connect to users twice, because one line records who submitted the record and the other records who reviewed it.'}]),
  Rich([{text:'Group 4 — the checklist (two relationships). ',bold:true},{text:'An inspection is made up of many inspection items, and each inspection item refers to one entry in the standard checklist. This is what allows the twelve standard items and the two BFP items to be stored once and reused by every inspection.'}]),

  H2('A6. Why some lines cross'),
  P('A few lines in the diagram cross one another. That is not untidiness — it cannot be avoided, and it is worth understanding why in case you are asked.'),
  P('Three tables — agencies, users and vehicles — each relate to the same four tables: inspections, damage reports, repair logs, and dispatches. In graph theory a structure in which three things each connect to the same three or more things cannot be drawn on a flat surface without at least one line crossing another. It is a known result, and no amount of rearranging the boxes can escape it.'),
  P('What can be controlled is how the crossings look. In this diagram every line runs strictly horizontally or vertically, related lines are grouped into shared lanes, no line passes through or behind a box, and every crossing meets at a right angle so that it can never be mistaken for a junction. That is what a well-drawn ERD looks like; a diagram with no crossings at all would mean relationships had been left out.'),

  H2('A7. What the diagram deliberately does not show'),
  P('Two sets of columns are intentionally left out, and both omissions were decided rather than overlooked.'),
  Rich([{text:'Framework tables. ',bold:true},{text:'The database also contains tables created by the web framework itself — for sign-in tokens, background jobs, cached values and sessions. They hold no fleet information, are not designed by the proponents, and are not part of the data model of the study, so they are not drawn and not documented.'}]),
  Rich([{text:'Three implementation-only columns. ',bold:true},{text:'The vehicles table in the running system carries three extra columns that support the user interface rather than any functional requirement: a note on the most recent manual status change, the name of the module that last set the status, and the time it was set. Because no functional requirement calls for them, they are deliberately excluded from both the diagram and the data dictionary. Everything else in the database is documented.'}]),

  new Paragraph({pageBreakBefore:true, children:[]}),
);

/* ============================ PART B ============================ */
push(H1('PART B — Manuscript text'),
  NOTE('Where this goes', [
    'Chapter 4 → Design of Software, Systems, Product, and/or Processes → Data Model.',
    'The Data Model section already opens with a paragraph describing the data model. Everything below follows it, in this order.',
    '"Entity Relationship Diagram" and "Data Dictionary" are NOT sub-headings. Per the approved format they are part of the running text of the Data Model section, so do not align or number them as sub-sections.',
    'Paste the text exactly as written. It has been checked against the system database.',
  ]),
  Spacer(200),

  H2('B1. Entity Relationship Diagram  (paste this text)'),
  P('An Entity Relationship Diagram (ERD) is a graphical representation of the entities in a database and the relationships among them. It is a tool used in database design to model real-world objects or concepts as entities and to show how those entities are connected through their attributes and keys. The entity relationship diagram of the Rescue Vehicle Management System is shown in Figure 6.'),
  Spacer(80),
  NOTE('Insert the figure here', [
    'Insert rvms-erd.png at this point, then the caption below it.',
    'Recommended: place the figure on its own landscape page so the attribute names stay readable. In Word: Layout → Breaks → Next Page, then Layout → Orientation → Landscape for that section only.',
    'Caption to use:  Figure 6. Entity Relationship Diagram of the Rescue Vehicle Management System',
    'If the diagram must be edited, open rvms-erd.drawio at diagrams.net, change it there, then export a new PNG at 300 dpi and replace the image. Do not redraw it from scratch.',
  ]),
  Spacer(160),
  P('Figure 6 presents the entity relationship diagram of the Rescue Vehicle Management System. An agency can have many users, vehicles, inspections, damage reports, repair logs, preventive maintenance schedules, dispatches, and notifications, while each of these records belongs to exactly one agency; this enforces the agency-scoped access of the system. A user is either an Agency Administrator or an Authorized Driver. Each vehicle is assigned to one primary driver, and a driver may be the assigned driver of one or more vehicles. Each vehicle can have many inspections, damage reports, repair logs, preventive maintenance schedules, and dispatches, and each of these records refers to exactly one vehicle. Every inspection, damage report, repair log, and dispatch also records the driver who carried it out, while inspections and damage reports additionally record the administrator who reviewed them. An inspection is composed of several inspection items, and each inspection item corresponds to one item from the inspection checklist catalog. Finally, each notification is addressed to a single user. Together, these relationships allow the system to keep agency records separated, maintain one consistent vehicle status, and support inspection, maintenance, dispatch, and reporting across the four participating agencies.'),

  H2('B2. Data Dictionary  (paste this text)'),
  P('The Data Dictionary is a centralized document that gives detailed information about the structure, organization, and attributes of the data used in the Rescue Vehicle Management System (RVMS). It includes clear definitions and descriptions of all the data inside the system, such as tables, fields, and data types. This dictionary is very important for data management because it helps everyone understand the data, maintain its quality, and communicate effectively among developers, agency administrators, and other stakeholders.'),
  Spacer(120),
);

/* ---- Tables 5..15 ---- */
const SYSTEM = 'Rescue Vehicle Management System';
M.order.forEach((name, i) => {
  const n = i + 5;
  push(
    P(`Table ${n} shows the data dictionary for the ${name} table of the ${SYSTEM}.`),
    new Paragraph({spacing:{before:120,after:0},
      children:[new TextRun({text:`Table ${n}`, font:SERIF, size:24, bold:true})]}),
    new Paragraph({spacing:{after:120},
      children:[new TextRun({text:`Data Dictionary of ${name} Table of ${SYSTEM}`, font:SERIF, size:24, italics:true})]}),
    ddTable(name),
    Spacer(140),
    Rich([{text:`Table ${n} – `,bold:true},{text:N[name]}]),
    Spacer(200),
  );
});

push(Spacer(200), NOTE('After pasting', [
  'Check that the table numbers still run 5 to 15. If Chapter 4 gains another table before the Data Model section, renumber these and the sentences that introduce them.',
  'Check that Figure 6 is still Figure 6 — the ERD follows the Functional Decomposition Diagram (Figure 5).',
  'The em dash in each "Table N –" line is the same character used throughout the manuscript.',
]));

const doc = new Document({
  creator:'RVMS Capstone', title:'Chapter 4 — Data Model: ERD and Data Dictionary',
  styles:{ default:{ document:{ run:{ font:SERIF, size:24 } } } },
  sections:[{
    properties:{ page:{ size:{ width:12240, height:15840 },
      margin:{ top:1440, right:1440, bottom:1440, left:1440 } } },
    children,
  }],
});
Packer.toBuffer(doc).then(b => { fs.writeFileSync('RVMS-Chapter4-ERD-and-Data-Dictionary.docx', b);
  console.log('wrote docx —', (b.length/1024).toFixed(0), 'KB,', children.length, 'blocks'); });
