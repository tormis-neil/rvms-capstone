/**
 * "How to Build the RVMS Entity Relationship Diagram in MySQL Workbench"
 *
 * A hands-on manual: create the model, build all eleven tables, add the
 * twenty-three foreign keys, arrange it, export it. The import-and-reverse-
 * engineer shortcut is deliberately not included — this document is only about
 * designing the diagram yourself.
 *
 * The column specs and the foreign-key list come from workbench_specs.json,
 * which dump_workbench_specs.py generates out of rvms-erd.sql. Nothing about
 * the schema is typed here, so the manual cannot drift from the database.
 */
const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType,
  AlignmentType, BorderStyle, ShadingType, LineRuleType, HeadingLevel,
} = require('docx');

const S = JSON.parse(fs.readFileSync('workbench_specs.json', 'utf8'));

const FONT = 'Arial';
const MONO = 'Consolas';
const NAVY = '1F3864';
const BODY = 22;      // 11 pt
const CELL = 18;      // 9 pt
const W = 9360;       // 6.5 in

// ---------------- building blocks ----------------
const P = (text, o = {}) => new Paragraph({
  alignment: o.align || AlignmentType.LEFT,
  spacing: { after: o.after ?? 140, line: 260, lineRule: LineRuleType.AUTO },
  indent: o.indent,
  children: [new TextRun({ text, font: FONT, size: o.size || BODY, bold: o.bold, italics: o.italics, color: o.color })],
});

/** A paragraph mixing bold/plain/monospace runs. */
const Rich = (runs, o = {}) => new Paragraph({
  spacing: { after: o.after ?? 140, line: 260, lineRule: LineRuleType.AUTO },
  indent: o.indent,
  children: runs.map(r => new TextRun({ font: r.mono ? MONO : FONT, size: r.mono ? BODY - 2 : BODY, ...r })),
});

const H1 = t => new Paragraph({
  heading: HeadingLevel.HEADING_1, spacing: { before: 400, after: 180 },
  children: [new TextRun({ text: t, font: FONT, size: 30, bold: true, color: NAVY })],
});
const H2 = t => new Paragraph({
  heading: HeadingLevel.HEADING_2, spacing: { before: 280, after: 120 },
  children: [new TextRun({ text: t, font: FONT, size: 24, bold: true, color: NAVY })],
});
const H3 = t => new Paragraph({
  heading: HeadingLevel.HEADING_3, spacing: { before: 220, after: 100 },
  children: [new TextRun({ text: t, font: FONT, size: BODY, bold: true })],
});

const Bullet = (t, lvl = 0) => new Paragraph({
  bullet: { level: lvl }, spacing: { after: 80, line: 260 },
  children: [new TextRun({ text: t, font: FONT, size: BODY })],
});
const Check = t => new Paragraph({
  spacing: { after: 80, line: 260 }, indent: { left: 360 },
  children: [new TextRun({ text: '☐  ' + t, font: FONT, size: BODY })],
});

/** Monospace block on a light background. */
const Code = lines => new Table({
  width: { size: 100, type: WidthType.PERCENTAGE },
  borders: {
    top: { style: BorderStyle.SINGLE, size: 2, color: 'CCCCCC' },
    bottom: { style: BorderStyle.SINGLE, size: 2, color: 'CCCCCC' },
    left: { style: BorderStyle.SINGLE, size: 2, color: 'CCCCCC' },
    right: { style: BorderStyle.SINGLE, size: 2, color: 'CCCCCC' },
    insideHorizontal: { style: BorderStyle.NONE }, insideVertical: { style: BorderStyle.NONE },
  },
  rows: [new TableRow({
    children: [new TableCell({
      shading: { type: ShadingType.CLEAR, fill: 'F4F5F7' },
      margins: { top: 100, bottom: 100, left: 160, right: 160 },
      children: (Array.isArray(lines) ? lines : [lines]).map(l => new Paragraph({
        spacing: { after: 0, line: 240 },
        children: [new TextRun({ text: l, font: MONO, size: 19 })],
      })),
    })],
  })],
});

/** Callout box — a note, a warning, or a tip. */
const Note = (title, lines, colour = NAVY, fill = 'F2F5FB') => new Table({
  width: { size: 100, type: WidthType.PERCENTAGE },
  borders: {
    top: { style: BorderStyle.SINGLE, size: 4, color: colour },
    bottom: { style: BorderStyle.SINGLE, size: 4, color: colour },
    left: { style: BorderStyle.SINGLE, size: 18, color: colour },
    right: { style: BorderStyle.SINGLE, size: 4, color: colour },
    insideHorizontal: { style: BorderStyle.NONE }, insideVertical: { style: BorderStyle.NONE },
  },
  rows: [new TableRow({
    children: [new TableCell({
      shading: { type: ShadingType.CLEAR, fill },
      margins: { top: 120, bottom: 120, left: 180, right: 180 },
      children: [
        ...(title ? [new Paragraph({
          spacing: { after: 70 },
          children: [new TextRun({ text: title, font: FONT, size: 20, bold: true, color: colour })],
        })] : []),
        ...lines.map(l => new Paragraph({
          spacing: { after: 60, line: 250 },
          children: [new TextRun({ text: l, font: FONT, size: 20 })],
        })),
      ],
    })],
  })],
});

const Gap = (h = 140) => new Paragraph({ spacing: { after: h }, children: [new TextRun('')] });

/** A generic bordered table: headers + rows of plain strings. */
function grid(headers, rows, widths, centre = []) {
  const cell = (txt, i, hdr) => new TableCell({
    width: { size: widths[i], type: WidthType.DXA },
    shading: hdr ? { type: ShadingType.CLEAR, fill: 'DDE3F0' } : undefined,
    margins: { top: 50, bottom: 50, left: 80, right: 80 },
    children: [new Paragraph({
      alignment: centre.includes(i) ? AlignmentType.CENTER : AlignmentType.LEFT,
      spacing: { after: 0, line: 230 },
      children: [new TextRun({
        text: txt, size: CELL, bold: !!hdr,
        font: (!hdr && /^[a-z_]+$|^[A-Z]+\(|^`/.test(txt)) ? MONO : FONT,
      })],
    })],
  });
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    columnWidths: widths,
    borders: {
      top: { style: BorderStyle.SINGLE, size: 6, color: '000000' },
      bottom: { style: BorderStyle.SINGLE, size: 6, color: '000000' },
      left: { style: BorderStyle.NONE }, right: { style: BorderStyle.NONE },
      insideHorizontal: { style: BorderStyle.SINGLE, size: 2, color: 'BBBBBB' },
      insideVertical: { style: BorderStyle.NONE },
    },
    rows: [
      new TableRow({ tableHeader: true, children: headers.map((h, i) => cell(h, i, true)) }),
      ...rows.map(r => new TableRow({ children: r.map((t, i) => cell(t, i, false)) })),
    ],
  });
}

const d = [];
const push = (...x) => d.push(...x);
const TICK = '✔';

/* ======================= TITLE ======================= */
push(
  new Paragraph({
    alignment: AlignmentType.CENTER, spacing: { before: 300, after: 80 },
    children: [new TextRun({ text: 'RESCUE VEHICLE MANAGEMENT SYSTEM', font: FONT, size: 24, bold: true, color: NAVY })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER, spacing: { after: 120 },
    children: [new TextRun({ text: 'How to Design the Entity Relationship Diagram in MySQL Workbench', font: FONT, size: 36, bold: true, color: NAVY })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER, spacing: { after: 300 },
    children: [new TextRun({ text: 'A step-by-step manual  ·  11 tables  ·  131 columns  ·  23 relationships', font: FONT, size: 22, color: '555555' })],
  }),
  Note('Who this is for', [
    'This manual assumes you have never opened MySQL Workbench before. Every step says what to click and what you should see afterwards.',
    'If what you see does not match, stop and check the Troubleshooting section at the end rather than carrying on.',
    'Work through it in order. Set aside two to three hours.',
  ]),
  Gap(200),
  H2('What you need'),
  Check('MySQL Workbench installed (free, from dev.mysql.com/downloads/workbench)'),
  Check('Figure 6 — the finished ERD — open on screen or printed, to check your work against'),
  Check('Two to three hours'),
  Gap(80),
  Note('You do not need MySQL running', [
    'Drawing a model in Workbench is just drawing. It never touches a real database until you explicitly tell it to, so nothing you already have is at risk.',
  ]),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= PART 1 ======================= */
push(
  H1('PART 1 — Create the model file'),

  H3('Step 1.1 — Open MySQL Workbench'),
  P('Launch it. You will see the home screen with a list of connections.'),
  Rich([{ text: 'Ignore the connections.', bold: true }, { text: ' You are not connecting to anything.' }]),

  H3('Step 1.2 — Create a new model'),
  Rich([{ text: 'Click ' }, { text: 'File → New Model', bold: true }, { text: ' (or press ' }, { text: 'Ctrl + N', bold: true }, { text: ').' }]),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'a new tab named MySQL Model, with a section called Physical Schemas and a default schema named mydb.' }]),

  H3('Step 1.3 — Rename the schema'),
  Rich([{ text: '1.  Double-click', bold: true }, { text: ' the mydb box under Physical Schemas.' }]),
  Rich([{ text: '2.  In the panel that opens at the bottom, change ' }, { text: 'Name', bold: true }, { text: ' to:' }]),
  Code('rvms'),
  Gap(80),
  P('3.  Press Enter.'),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'the box now reads rvms.' }]),

  H3('Step 1.4 — Save immediately'),
  Rich([{ text: 'File → Save Model As…', bold: true }, { text: '  and name it rvms-erd.mwb' }]),
  Note('Press Ctrl + S every ten minutes from now on', [
    'Workbench does not auto-save. Losing an hour of typing is a miserable way to spend an evening.',
  ], 'B54708', 'FEF6EE'),

  H3("Step 1.5 — Set the notation to Crow's Foot"),
  P('This is the notation used in Figure 6, so set it now, before you draw anything.'),
  Rich([{ text: '1.  ' }, { text: "Model → Relationship Notation → Crow's Foot (IE)", bold: true }]),
  Rich([{ text: '2.  ' }, { text: 'Model → Object Notation → Workbench (Simplified)', bold: true }]),
  Rich([{ text: 'Why: ', bold: true }, { text: "Crow's Foot gives you the three-pronged “many” symbol. Workbench (Simplified) makes each table box list its columns with their data types — which is what Figure 6 shows." }]),

  H3('Step 1.6 — Open the diagram canvas'),
  Rich([{ text: 'Scroll down in the MySQL Model tab to the ' }, { text: 'EER Diagrams', bold: true }, { text: ' section and double-click the ' }, { text: 'Add Diagram', bold: true }, { text: ' icon.' }]),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'a large blank white canvas with a vertical toolbar on the left. This is where the diagram gets drawn.' }]),
  Gap(60),
  P('Find these three tools on the left toolbar — you will use them constantly:', { bold: true }),
  grid(['Tool', 'Shortcut', 'What it does'],
    [['Pointer (arrow)', 'Esc', 'Select and move things'],
     ['Place a New Table', 'T', 'Creates a table'],
     ['Hand tool', 'H', 'Drag the canvas around']],
    [3000, 1600, 4760], [1]),
  Gap(120),
  Note('The single most useful key: Esc', [
    'After placing a table, Workbench stays in table mode and will keep dropping new tables every time you click. Press Esc to go back to the pointer.',
  ]),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= PART 2 ======================= */
push(
  H1('PART 2 — How to create one table'),
  P('Every one of the eleven tables is made the same way. Read this part carefully once, and Part 3 becomes repetition.'),
  Rich([{ text: 'We will build ' }, { text: 'agencies', mono: true }, { text: ' as the worked example.' }]),

  H3('Step 2.1 — Place the table'),
  Rich([{ text: '1.  Press ' }, { text: 'T', bold: true }, { text: ' (or click the table icon).' }]),
  Rich([{ text: '2.  ' }, { text: 'Click once', bold: true }, { text: ' on the canvas, near the top-left.' }]),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'a small box named table1.' }]),
  Rich([{ text: '3.  Press ' }, { text: 'Esc', bold: true }, { text: " so you don't accidentally create more." }]),

  H3('Step 2.2 — Open the table editor'),
  Rich([{ text: 'Double-click', bold: true }, { text: ' the new box.' }]),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'an editor panel open at the bottom of the window, with tabs along its bottom edge — Table, Columns, Indexes, Foreign Keys, Triggers, Partitioning, Options.' }]),
  Note('Make the editor panel taller', [
    'It is small by default. Drag its top edge upward — you will be living in it for the next two hours.',
  ]),

  H3('Step 2.3 — Name the table'),
  Rich([{ text: 'In the ' }, { text: 'Table', bold: true }, { text: ' tab, set ' }, { text: 'Name', bold: true }, { text: ' to:' }]),
  Code('agencies'),
  Gap(100),
  Note('Type every name exactly as written in this manual', [
    'All lowercase, with underscores, no spaces. agencies, not Agencies. inspection_checklist_items, not Inspection Checklist Items.',
    'The names must match the real database, because that is what the data dictionary documents and what a panelist would find if they opened MySQL.',
  ], 'B54708', 'FEF6EE'),

  H3('Step 2.4 — Add the columns'),
  Rich([{ text: 'Click the ' }, { text: 'Columns', bold: true }, { text: ' tab. You should see a grid with these headings:' }]),
  Code('Column Name | Datatype | PK | NN | UQ | B | UN | ZF | AI | G | Default/Expression'),
  Gap(100),
  Rich([{ text: 'You only ever need four of the tick-boxes:', bold: true }]),
  grid(['Box', 'Full name', 'Tick it when…'],
    [['PK', 'Primary Key', "it's the id column"],
     ['NN', 'Not Null', 'the field is required'],
     ['UN', 'Unsigned', 'the number can never be negative'],
     ['AI', 'Auto Increment', "it's the id column"]],
    [900, 2200, 6260], [0]),
  Gap(120),
  Rich([{ text: 'Ignore UQ, B, ZF and G entirely ', bold: true }, { text: '— this design never uses them.' }]),
  Gap(60),
  P('To add a column:', { bold: true }),
  Bullet('Double-click the empty row under Column Name'),
  Bullet('Type the name, press Tab'),
  Bullet('Type the datatype, press Enter'),
  Bullet('Tick the boxes the spec table tells you to'),
  Bullet('A fresh empty row appears — repeat'),
  Gap(60),
  Rich([{ text: 'For agencies, enter the ten columns listed in Part 3. When you are done, you should see the box on the canvas grow, with a key icon beside ' }, { text: 'id', mono: true }, { text: '.' }]),

  H3('Step 2.5 — Three typing rules that will save you pain'),
  Rich([{ text: 'ENUM columns — type the whole thing, exactly. ', bold: true }, { text: 'Including the brackets and the single quotes, with no spaces after the commas. The four longest ones in this design are:' }]),
  Code([
    "ENUM('Operational','Dispatched','Not Operational','Under Preventive Maintenance')",
    '',
    "ENUM('Internal Office','GSO Motorpool','External Repair Shop')",
    '',
    "ENUM('Fire Response','Medical Response','Rescue Operation',",
    "     'Patrol','Administrative Travel','Others')",
    '',
    "ENUM('PM_Reminder','Vehicle_Status_Update','New_Damage_Report',",
    "     'Inspection_Flagged','License_Expiring','License_Expired',",
    "     'PM_Due_Soon','PM_Due','New_Access_Request','Password_Reset')",
  ]),
  Gap(140),
  Rich([{ text: 'Default values ', bold: true }, { text: 'go in the Default/Expression box. A number is typed plainly (30, 0); text is typed with its quotes (‘active’, ‘Pending’, ‘Operational’); a dash in the spec means leave the box empty.' }]),
  Rich([{ text: 'created_at and updated_at ', bold: true }, { text: 'end almost every table. They are always TIMESTAMP, and nothing is ticked — no PK, no NN, no UN, no AI.' }]),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= PART 3 ======================= */
push(
  H1('PART 3 — Create all eleven tables'),
  Note('THE ORDER MATTERS — do not skip ahead', [
    'A table can only point at a table that already exists. If you build inspections before vehicles exists, the Referenced Table dropdown will be empty and you will think Workbench is broken.',
    'The order below is already correct. Just work down the list.',
  ], 'B42318', 'FEF3F2'),
  Gap(140),
  P('Place the tables roughly where Figure 6 has them — the top row first, the bottom row after. Do not fuss over positions yet; Part 5 tidies everything up.'),
  Rich([{ text: 'For each table: place it (' }, { text: 'T', bold: true }, { text: ', click, ' }, { text: 'Esc', bold: true }, { text: '), double-click it, name it, then type its columns. ' }, { text: 'Foreign keys come later, in Part 4', bold: true }, { text: ' — for now just create those columns as ordinary BIGINT fields.' }]),
);

const COLW = [420, 2180, 3400, 420, 420, 420, 420, 1680];
S.order.forEach((name, i) => {
  const t = S.tables[name];
  push(
    H2(`Table ${i + 1} of 11 — ${name}`),
    grid(['#', 'Column Name', 'Datatype', 'PK', 'NN', 'UN', 'AI', 'Default'],
      t.columns.map((c, k) => [
        String(k + 1), c.name, c.type,
        c.pk ? TICK : '', c.nn ? TICK : '', c.un ? TICK : '', c.ai ? TICK : '',
        c.default || '—',
      ]),
      COLW, [0, 3, 4, 5, 6]),
  );
  if (t.fks.length) {
    push(Gap(120),
      P('Foreign keys for this table (added in Part 4):', { bold: true, size: 20 }),
      grid(['Foreign Key Name', 'Column', 'Referenced Table', 'Ref. Column', 'On Delete'],
        t.fks.map(f => [f.name, f.column, f.ref, 'id', f.on_delete]),
        [2900, 1700, 2300, 1100, 1360]));
  }
  push(Gap(160));
});

push(
  Note('Checkpoint — end of Part 3', [
    'Eleven table boxes on the canvas, all named in lowercase.',
    'Every box has a key icon next to id.',
    'No relationship lines yet — that is correct, they come next.',
    'Press Ctrl + S.',
  ]),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= PART 4 ======================= */
push(
  H1('PART 4 — Draw the twenty-three relationships'),
  Rich([{ text: 'Here is the good news: ' }, { text: 'you do not draw the lines.', bold: true }, { text: ' You tell Workbench which column points at which table, and it draws the line for you, with the correct crow’s-foot symbols on both ends.' }]),

  H3('Step 4.1 — How to add one foreign key'),
  Rich([{ text: 'We will do ' }, { text: 'users.agency_id → agencies.id', mono: true }, { text: ' as the worked example.' }]),
  Bullet('Double-click the users table'),
  Bullet('Click the Foreign Keys tab'),
  Bullet('Double-click the empty row under Foreign Key Name and type: fk_users_agency_id'),
  Bullet('In the same row, click the Referenced Table cell and choose agencies from the dropdown'),
  Bullet('The middle panel now lists every column in users. Tick agency_id'),
  Bullet('In the right-hand panel, set Referenced Column to id'),
  Bullet('At the bottom right, set On Delete to what the spec says. Leave On Update as NO ACTION everywhere'),
  Gap(60),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'a line appear on the canvas joining users to agencies, with a single bar at the agencies end and a crow’s foot at the users end.' }]),
  Rich([{ text: 'That is one relationship. There are twenty-three, and they all work exactly like this.', bold: true }]),

  H3('Step 4.2 — Identifying or non-identifying?'),
  Rich([{ text: 'Every relationship in this design is ' }, { text: 'NON-IDENTIFYING', bold: true }, { text: ', which Workbench draws with a dashed line.' }]),
  Rich([{ text: 'Why: ', bold: true }, { text: 'an “identifying” relationship means the child’s primary key includes the parent’s key. Here every table’s primary key is just its own id, so no relationship is identifying. If a line comes out solid, you picked the wrong one — see Troubleshooting.' }]),

  H3('Step 4.3 — Add all twenty-three foreign keys'),
  P('Work down this list, ticking them off. Every one also appears in its table’s spec in Part 3, so you can do them table by table instead if you prefer.'),
  Gap(60),
);

const fkRows = [];
S.order.forEach(name => S.tables[name].fks.forEach(f =>
  fkRows.push([String(fkRows.length + 1), name, f.name, f.column, f.ref, f.on_delete])));
push(grid(['#', 'In this table', 'Foreign Key Name', 'Column', 'Points to', 'On Delete'],
  fkRows, [460, 1700, 2760, 1700, 1500, 1240], [0]));

push(
  Gap(200),
  H3('Step 4.4 — The two that will confuse you'),
  Rich([{ text: 'Look at numbers 6 and 7 in that list. ' }, { text: 'inspections points at users twice.', bold: true }, { text: ' The same happens at numbers 12 and 13 on damage_reports.' }]),
  Rich([{ text: 'This is correct and deliberate — do not “fix” it.', bold: true }]),
  Bullet('driver_id → the driver who SUBMITTED the inspection'),
  Bullet('reviewed_by → the administrator who REVIEWED it'),
  P('Both are people, and both come from the users table, but they are two different roles, so they need two separate columns and two separate lines. It is the same reason a school permission slip has a line for the student’s name and a separate line for the parent’s signature — you need both at once, so one field cannot serve.'),
  Rich([{ text: 'You should see: ', bold: true }, { text: 'two lines running between inspections and users. Figure 6 shows the same thing — count them.' }]),
  Gap(140),
  Note('Checkpoint — end of Part 4', [
    'Twenty-three relationship lines on the canvas.',
    'Every line is dashed, not solid.',
    'Two lines between inspections and users; two between damage_reports and users.',
    'Every foreign key column now shows a red diamond in its table box.',
    'Press Ctrl + S.',
  ]),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= PART 5 ======================= */
push(
  H1('PART 5 — Make it presentable'),
  P('Right now it is correct, but it probably looks like a plate of noodles. This part is what turns it into something you can put in a manuscript.'),

  H3('Step 5.1 — Arrange the boxes like Figure 6'),
  P('Figure 6 uses two rows, and it is worth copying because it keeps the lines short.'),
  Rich([{ text: 'Top row ', bold: true }, { text: '(the things other records point at):' }]),
  Code('agencies    users    vehicles    inspection_checklist_items    inspection_items'),
  Gap(100),
  Rich([{ text: 'Bottom row ', bold: true }, { text: '(the records that point at them):' }]),
  Code('notifications   inspections   damage_reports   repair_logs   pm_schedules   dispatches'),
  Gap(140),
  P('Drag each box into place. Leave generous space between the two rows — that gap is where all the lines will run.'),

  H3('Step 5.2 — Tidy the lines'),
  Bullet('Drag a line’s middle to bend it away from a box it is crossing'),
  Bullet('Right-click a line → Properties to change how it is drawn'),
  Bullet('Aim for lines that run horizontally or vertically, never diagonally'),

  H3('Step 5.3 — About the lines that cross'),
  Rich([{ text: 'Some lines will cross no matter what you do. ' }, { text: 'This is not your fault and it cannot be fixed.', bold: true }]),
  P('Three tables — agencies, users and vehicles — each connect to the same four tables: inspections, damage_reports, repair_logs and dispatches. There is a proven result in mathematics that a shape like that cannot be drawn flat without at least one crossing. No amount of dragging escapes it.'),
  P('What you can control is how the crossings look. Aim for lines that meet at right angles, so a crossing can never be mistaken for a join. A diagram with zero crossings would actually mean you had left relationships out.'),
  Note('If a panelist asks why lines cross', [
    'The paragraph above is your answer, and it is a much better one than apologising for it.',
  ]),

  H3('Step 5.4 — Turn off the grid'),
  Rich([{ text: 'View → Grid', bold: true }, { text: ' (untick it), so the exported diagram has a clean white background.' }]),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= PART 6 ======================= */
push(
  H1('PART 6 — Export the image and check your work'),

  H3('Step 6.1 — Export as PNG'),
  Rich([{ text: 'File → Export → Export as PNG…', bold: true }, { text: '  and save it as rvms-erd-workbench.png' }]),
  Rich([{ text: 'Use PNG, not JPG ', bold: true }, { text: '— JPG blurs thin lines and small text.' }]),

  H3('Step 6.2 — Verify against the real database'),
  P('This is the step that catches typos, and it takes about thirty seconds.'),
  Rich([{ text: '1.  ' }, { text: 'File → Export → Forward Engineer SQL CREATE Script…', bold: true }]),
  P('2.  Save it anywhere, then open it in Notepad and check three numbers:'),
  Gap(60),
  grid(['Check', 'Should be'],
    [['Count of CREATE TABLE', '11'], ['Count of FOREIGN KEY', '23'], ['Count of column lines', '131']],
    [6360, 3000], [1]),
  Gap(140),
  P('In Notepad, press Ctrl + H, search for the phrase, and it reports how many it found. If any number is off, something was missed — go back and compare that table against its spec in Part 3.'),

  H3('Step 6.3 — Final checklist'),
  Check('Eleven tables, all lowercase names'),
  Check('131 columns in total'),
  Check('Twenty-three relationship lines'),
  Check('Every table has id as its primary key, with the key icon and AI ticked'),
  Check("Crow's foot symbols visible on the “many” end of every line"),
  Check('Two lines from inspections to users, and two from damage_reports to users'),
  Check('Grid turned off, and the diagram exported as a PNG'),
  Check('The .mwb model file saved somewhere safe'),
  new Paragraph({ pageBreakBefore: true, children: [] }),
);

/* ======================= TROUBLESHOOTING ======================= */
const trouble = [
  ['The Referenced Table dropdown is empty',
    ['The table you want to point at has not been created yet. Foreign keys can only point at tables that already exist.',
     'Go back and create the missing table first. This is exactly why the order in Part 3 matters.']],
  ['My relationship line is solid, not dashed',
    ['You created an identifying relationship. Every relationship here should be non-identifying.',
     'Fix: right-click the line and delete it, then add the foreign key again using the Foreign Keys tab method in Step 4.1. That method always produces a non-identifying relationship.']],
  ['Workbench added a column I did not type',
    ['When you create a foreign key with the relationship tools on the left toolbar, Workbench invents a column for it — usually named something like agencies_id — alongside the agency_id you already made.',
     'Fix: delete the invented column and the relationship, then add the foreign key through the Foreign Keys tab instead, ticking the column you already made. That way it uses your column and invents nothing.',
     'This is the single most common mess in Workbench, and it is why this manual has you build all the columns first and add foreign keys afterwards.']],
  ['My ENUM shows an error',
    ['Almost always one of three things. A missing quote — every value needs single quotes. A space after a comma — write ‘a’,’b’ and not ‘a’, ‘b’. Or curly quotes, if you pasted from Word: retype the quotes by hand in Workbench.']],
  ['I keep creating tables by accident',
    ['You are still in table mode. Press Esc after every table you place.']],
  ['The lines are a tangled mess',
    ['That is normal until you arrange the boxes. Do Part 5 — put the tables into two rows first, and most of the tangle sorts itself out.']],
  ['I closed the editor panel and cannot get it back',
    ['Double-click any table on the canvas and it reopens at the bottom.']],
  ['Workbench crashed and I lost my work',
    ['Workbench does not auto-save. Ctrl + S every ten minutes is the only cure.']],
];
push(H1('Troubleshooting'));
trouble.forEach(([q, lines]) => push(H3(q), ...lines.map(l => P(l))));

/* ======================= SUMMARY ======================= */
push(
  new Paragraph({ pageBreakBefore: true, children: [] }),
  H1('One-page summary'),
  P('Print this page and keep it beside you.'),
  Code([
    'SETUP     File > New Model (Ctrl+N)',
    '          rename the schema to "rvms"',
    "          Model > Relationship Notation > Crow's Foot (IE)",
    '          Model > Object Notation      > Workbench (Simplified)',
    '          double-click "Add Diagram"',
    '',
    'ONE TABLE press T > click the canvas > press Esc',
    '          double-click it > Table tab   > type the name',
    '                          > Columns tab > type the columns',
    '          tick only:  PK  NN  UN  AI',
    '',
    'ORDER     agencies . users . vehicles . inspection_checklist_items .',
    '          inspections . inspection_items . damage_reports .',
    '          repair_logs . pm_schedules . dispatches . notifications',
    '          (a table can only point at one that already exists)',
    '',
    'ONE FK    double-click the table > Foreign Keys tab',
    '          name it > pick the Referenced Table > tick the column >',
    '          set Referenced Column = id > set On Delete',
    '          the line draws itself',
    '',
    'TOTALS    11 tables  .  131 columns  .  23 foreign keys',
    '',
    'CHECK     File > Export > Forward Engineer SQL CREATE Script',
    '          count CREATE TABLE = 11, FOREIGN KEY = 23',
    '',
    'EXPORT    View > Grid (off)',
    '          File > Export > Export as PNG',
    '',
    'SAVE      Ctrl + S every ten minutes. Workbench does not auto-save.',
  ]),
  Gap(200),
  Note('A note on what is deliberately left out', [
    'Three columns on vehicles that exist in the running system — remarks, status_source and status_changed_at — are excluded here and in the data dictionary, because no functional requirement backs them. That is a decision, not an omission.',
    'Framework tables (sessions, jobs, cache, tokens) are also excluded. They are created by Laravel, hold no fleet information, and are not part of the study’s data model.',
  ]),
);

const doc = new Document({
  creator: 'RVMS Capstone',
  title: 'How to Design the RVMS ERD in MySQL Workbench',
  styles: { default: { document: { run: { font: FONT, size: BODY } } } },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
      },
    },
    children: d,
  }],
});

Packer.toBuffer(doc).then(b => {
  fs.writeFileSync('RVMS-ERD-MySQL-Workbench-Manual.docx', b);
  console.log('wrote docx —', (b.length / 1024).toFixed(0), 'KB,', d.length, 'blocks');
});
