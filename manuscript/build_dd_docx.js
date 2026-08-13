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
  AlignmentType, BorderStyle, ShadingType, LineRuleType,
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
const COLS = [2000, 1180, 620, 3520, 620, 1420];   // = 9360 dxa (6.5 in)
const HEADERS = ['Field Name', 'Data Type', 'Null', 'Description', 'Key', 'Reference Table'];

function ddTable(name) {
  const cell = (txt, i, hdr) => new TableCell({
    width: { size: COLS[i], type: WidthType.DXA },
    shading: hdr ? { type: ShadingType.CLEAR, fill: 'DDE3F0' } : undefined,
    margins: { top: 60, bottom: 60, left: 90, right: 90 },
    children: [new Paragraph({
      alignment: (i === 2 || i === 4) ? AlignmentType.CENTER : AlignmentType.LEFT,
      spacing: { after: 0, line: 240, lineRule: LineRuleType.AUTO },
      children: [new TextRun({ text: txt, font: FONT, size: CELL, bold: !!hdr })],
    })],
  });
  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    columnWidths: COLS,
    borders: {
      top: { style: BorderStyle.SINGLE, size: 8, color: '000000' },
      bottom: { style: BorderStyle.SINGLE, size: 8, color: '000000' },
      left: { style: BorderStyle.NONE }, right: { style: BorderStyle.NONE },
      insideHorizontal: { style: BorderStyle.SINGLE, size: 2, color: 'BBBBBB' },
      insideVertical: { style: BorderStyle.NONE },
    },
    rows: [
      new TableRow({ tableHeader: true, children: HEADERS.map((h, i) => cell(h, i, true)) }),
      ...M.tables[name].map(r => new TableRow({
        children: [cell(r[0], 0), cell(r[1], 1), cell(r[2], 2), cell(r[5], 3), cell(r[3], 4), cell(r[4], 5)],
      })),
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
