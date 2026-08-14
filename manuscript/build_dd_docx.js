/**
 * Data Dictionary — manuscript-ready.
 *
 * Everything that was guidance in RVMS-Chapter4-ERD-and-Data-Dictionary.docx is
 * gone: no cover, no Part A explainer, no "how to use this" boxes. What is left
 * is only what gets pasted into Chapter 4 — the section title, the introductory
 * paragraph, and for each of the eleven tables the lead-in sentence, the table
 * number and title, the table, and its description.
 *
 * Format follows the PathFinder sample: Arial throughout, 12 pt double-spaced
 * body text and table titles, 11 pt single-spaced table contents, each table
 * starting on its own page with its lead-in sentence on the page before it.
 */
const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType,
  AlignmentType, BorderStyle, ShadingType, LineRuleType, TableLayoutType, HeightRule,
} = require('docx');

const M = JSON.parse(fs.readFileSync('model.json', 'utf8'));
const N = JSON.parse(fs.readFileSync('narratives.json', 'utf8'));

const FONT = 'Arial';
const BODY = 24;                 // 12 pt, in half-points
const CELL = 22;                 // 11 pt
const DOUBLE = { line: 480, lineRule: LineRuleType.AUTO, after: 0 };
const INDENT = { firstLine: 720 };   // 0.5 in

const SYSTEM = 'Rescue Vehicle Management System';

/** A double-spaced body paragraph, justified, first line indented. */
const P = (text, o = {}) => new Paragraph({
  alignment: o.align || AlignmentType.JUSTIFIED,
  spacing: DOUBLE,
  indent: o.indent === null ? undefined : (o.indent || INDENT),
  children: [new TextRun({ text, font: FONT, size: BODY, bold: o.bold, italics: o.italics })],
});

/** Same, built from styled runs. */
const Rich = (runs, o = {}) => new Paragraph({
  alignment: o.align || AlignmentType.JUSTIFIED,
  spacing: DOUBLE,
  indent: o.indent === null ? undefined : (o.indent || INDENT),
  children: runs.map(r => new TextRun({ font: FONT, size: BODY, ...r })),
});

// ---------- the data dictionary table ----------
//
// Copied cell-for-cell from Table 5 of the Chapter 4 draft, read out of that
// file's XML rather than by eye: 8640 dxa fixed layout on the column widths
// below; only three horizontal rules (3 pt above the header, 1 pt below it,
// 3 pt under the last row) and no line at all between data rows; no shading;
// header centred, Arial 12 pt bold, row at least 660 twips tall; data rows
// Arial 11 pt double-spaced, with Null and Key centred and Description
// justified.
const COLS = [1575, 1605, 1170, 2010, 825, 1455];   // = 8640 dxa (6.0 in)
const HEADERS = ['Field Name', 'Data Type', 'Null', 'Description', 'Key', 'Reference Table'];

const NO_EDGE = { style: BorderStyle.NONE, size: 0, color: '000000' };
const RULE = size => ({ style: BorderStyle.SINGLE, size, color: '000000' });
// The draft rules off every data row in white — invisible, but it is what the
// file does, so it is what is reproduced here.
const HIDDEN = { style: BorderStyle.SINGLE, size: 8, color: 'FFFFFF' };

/** Per-column alignment, matching the draft. */
const ALIGN = [
  AlignmentType.LEFT,       // Field Name
  AlignmentType.LEFT,       // Data Type
  AlignmentType.CENTER,     // Null
  AlignmentType.JUSTIFIED,  // Description
  AlignmentType.CENTER,     // Key
  AlignmentType.LEFT,       // Reference Table
];

// The draft sets the Reference Table column a point larger than the other five,
// which carry no explicit size and so fall back to the document default. Copied
// as found rather than normalised.
const COL_SIZE = [CELL, CELL, CELL, CELL, CELL, BODY];

function ddTable(name) {
  const cell = (txt, i, { header = false, last = false } = {}) => new TableCell({
    width: { size: COLS[i], type: WidthType.DXA },
    shading: header ? { type: ShadingType.CLEAR, fill: 'FFFFFF' } : undefined,
    margins: { top: 60, bottom: 60, left: 100, right: 100 },
    verticalAlign: 'top',
    borders: {
      top: header ? RULE(18) : HIDDEN,
      bottom: header ? RULE(8) : (last ? RULE(18) : HIDDEN),
      left: NO_EDGE, right: NO_EDGE,
    },
    children: [new Paragraph({
      alignment: header ? AlignmentType.CENTER : ALIGN[i],
      spacing: { after: 0, line: header ? 240 : 480, lineRule: LineRuleType.AUTO },
      children: [new TextRun({ text: txt, font: FONT, size: header ? BODY : COL_SIZE[i], bold: header })],
    })],
  });

  const rows = M.tables[name];
  return new Table({
    width: { size: 8640, type: WidthType.DXA },
    columnWidths: COLS,
    layout: TableLayoutType.FIXED,
    alignment: AlignmentType.LEFT,
    borders: {
      top: NO_EDGE, bottom: NO_EDGE, left: NO_EDGE, right: NO_EDGE,
      insideHorizontal: NO_EDGE, insideVertical: NO_EDGE,
    },
    rows: [
      new TableRow({
        height: { value: 660, rule: HeightRule.ATLEAST },
        children: HEADERS.map((h, i) => cell(h, i, { header: true })),
      }),
      ...rows.map((r, k) => {
        const o = { last: k === rows.length - 1 };
        return new TableRow({
          children: [cell(r[0], 0, o), cell(r[1], 1, o), cell(r[2], 2, o),
                     cell(r[5], 3, o), cell(r[3], 4, o), cell(r[4], 5, o)],
        });
      }),
    ],
  });
}

const children = [];
const push = (...x) => children.push(...x);

/* ---------------- title + introduction ---------------- */
push(
  new Paragraph({
    spacing: DOUBLE,
    children: [new TextRun({ text: 'Data Dictionary', font: FONT, size: BODY, bold: true })],
  }),
  P('The Data Dictionary is a centralized document that gives detailed information about the structure, organization, and attributes of the data used in the Rescue Vehicle Management System (RVMS). It includes clear definitions and descriptions of all the data inside the system, such as tables, fields, and data types. This dictionary is very important for data management because it helps everyone understand the data, maintain its quality, and communicate effectively among developers, agency administrators, and other stakeholders.'),
);

/* ---------------- Tables 5 to 15 ---------------- */
M.order.forEach((name, i) => {
  const n = i + 5;
  push(
    // Lead-in sentence, on the page before the table — as in the sample.
    P(`Table ${n} shows the data dictionary for the ${name} table of the ${SYSTEM}.`),

    new Paragraph({
      pageBreakBefore: true, spacing: DOUBLE,
      children: [new TextRun({ text: `Table ${n}`, font: FONT, size: BODY, bold: true })],
    }),
    new Paragraph({
      spacing: DOUBLE,
      children: [new TextRun({
        text: `Data Dictionary of ${name} Table of the ${SYSTEM}`,
        font: FONT, size: BODY, italics: true,
      })],
    }),
    ddTable(name),
    new Paragraph({ spacing: DOUBLE, children: [new TextRun({ text: '', font: FONT, size: BODY })] }),
    Rich([{ text: `Table ${n} – `, bold: true }, { text: N[name] }]),
  );
});

const doc = new Document({
  creator: 'RVMS Capstone',
  title: 'Chapter 4 — Data Dictionary',
  styles: { default: { document: { run: { font: FONT, size: BODY } } } },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
      },
    },
    children,
  }],
});

Packer.toBuffer(doc).then(b => {
  fs.writeFileSync('RVMS-Chapter4-Data-Dictionary.docx', b);
  console.log('wrote docx —', (b.length / 1024).toFixed(0), 'KB,', children.length, 'blocks');
});
