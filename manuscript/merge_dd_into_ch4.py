"""Splice Tables 6-15 of the data dictionary into the Chapter 4 draft.

The draft already carries Table 5 and the lead-in sentence for Table 6, so this
continues from Table 6's caption and runs to the end of Table 15.

Everything is done as text surgery on word/document.xml and the remaining parts
of the docx are copied through byte-for-byte, so the draft's images, headers,
styles, page setup and earlier chapters are untouched. Paragraphs are built from
templates lifted out of the draft's own Table 5 block, so the new material is
formatted the way the draft formats that section rather than the way the data
dictionary file does.

Usage:  python3 merge_dd_into_ch4.py <draft.docx> <data-dictionary.docx> <out.docx>
"""
import json
import re
import shutil
import sys
import zipfile

DOC = 'word/document.xml'
SYSTEM = 'Rescue Vehicle Management System'

ARIAL = ('<w:rFonts w:ascii="Arial" w:cs="Arial" w:eastAsia="Arial" '
         'w:hAnsi="Arial" /><w:sz w:val="24" /><w:szCs w:val="24" />')
ARIAL_B = ('<w:rFonts w:ascii="Arial" w:cs="Arial" w:eastAsia="Arial" '
           'w:hAnsi="Arial" /><w:b w:val="1" /><w:bCs w:val="1" />'
           '<w:sz w:val="24" /><w:szCs w:val="24" />')


def esc(t):
    return t.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')


def lead_in(text):
    """Justified, first line indented — the draft's Table 5 lead-in."""
    return ('<w:p><w:pPr><w:spacing w:line="480" w:lineRule="auto" />'
            '<w:ind w:left="0" w:firstLine="720" /><w:jc w:val="both" />'
            '<w:rPr>%s</w:rPr></w:pPr>'
            '<w:r><w:rPr>%s<w:rtl w:val="0" /></w:rPr>'
            '<w:t xml:space="preserve">%s</w:t></w:r></w:p>'
            % (ARIAL, ARIAL, esc(text)))


def caption(text):
    """Centred and bold — "Table N"."""
    return ('<w:p><w:pPr><w:spacing w:after="0" w:line="480" w:lineRule="auto" />'
            '<w:jc w:val="center" /><w:rPr>%s</w:rPr></w:pPr>'
            '<w:r><w:rPr>%s<w:rtl w:val="0" /></w:rPr>'
            '<w:t xml:space="preserve">%s</w:t></w:r></w:p>'
            % (ARIAL_B, ARIAL_B, esc(text)))


def title(text):
    """Centred and bold — the table's name line."""
    return ('<w:p><w:pPr><w:spacing w:line="480" w:lineRule="auto" />'
            '<w:ind w:left="0" w:firstLine="720" /><w:jc w:val="center" />'
            '<w:rPr>%s</w:rPr></w:pPr>'
            '<w:r><w:rPr>%s<w:rtl w:val="0" /></w:rPr>'
            '<w:t xml:space="preserve">%s</w:t></w:r>'
            '<w:r><w:rPr><w:rtl w:val="0" /></w:rPr></w:r></w:p>'
            % (ARIAL, ARIAL_B, esc(text)))


def blank():
    return ('<w:p><w:pPr><w:spacing w:line="480" w:lineRule="auto" />'
            '<w:ind w:left="0" w:firstLine="0" /><w:jc w:val="left" />'
            '<w:rPr>%s</w:rPr></w:pPr>'
            '<w:r><w:rPr><w:rtl w:val="0" /></w:rPr></w:r></w:p>' % ARIAL)


def description(label, narrative):
    """"Table N –" plain, a bold space, then the narrative — as the draft has it."""
    return ('<w:p><w:pPr><w:spacing w:after="0" w:line="480" w:lineRule="auto" />'
            '<w:ind w:firstLine="720" /><w:jc w:val="both" />'
            '<w:rPr>%s</w:rPr></w:pPr>'
            '<w:r><w:rPr>%s<w:rtl w:val="0" /></w:rPr>'
            '<w:t xml:space="preserve">%s</w:t></w:r>'
            '<w:r><w:rPr>%s<w:rtl w:val="0" /></w:rPr>'
            '<w:t xml:space="preserve"> </w:t></w:r>'
            '<w:r><w:rPr>%s<w:rtl w:val="0" /></w:rPr>'
            '<w:t xml:space="preserve">%s</w:t></w:r></w:p>'
            % (ARIAL, ARIAL, esc(label), ARIAL_B, ARIAL, esc(narrative)))


def tables_in(xml):
    return re.findall(r'<w:tbl>.*?</w:tbl>', xml, re.S)


def normalise_table5(xml):
    """Bring Table 5's Reference Table column down from 12 pt to 11 pt.

    Only the sixth cell of each data row is touched; the header row keeps its
    12 pt bold, and the other five columns are already at the document default.
    """
    blocks = tables_in(xml)
    target = next(b for b in blocks if 'Field Name' in b)
    rows = re.findall(r'<w:tr\b.*?</w:tr>', target, re.S)
    fixed, changed = target, 0
    for row in rows[1:]:                       # skip the header row
        cells = re.findall(r'<w:tc>.*?</w:tc>', row, re.S)
        if len(cells) != 6:
            continue
        sixth = cells[5]
        new = sixth.replace('w:val="24"', 'w:val="22"')
        if new != sixth:
            fixed = fixed.replace(row, row.replace(sixth, new), 1)
            changed += 1
    return xml.replace(target, fixed, 1), changed


def main(draft_path, dd_path, out_path):
    with zipfile.ZipFile(draft_path) as z:
        draft = z.read(DOC).decode('utf8')
    with zipfile.ZipFile(dd_path) as z:
        dd = z.read(DOC).decode('utf8')

    dd_tables = tables_in(dd)
    assert len(dd_tables) == 11, 'expected 11 tables in the data dictionary, got %d' % len(dd_tables)

    model = json.load(open('model.json'))
    narratives = json.load(open('narratives.json'))
    names = model['order']

    # Table 5 is already in the draft; so is Table 6's lead-in sentence.
    parts = []
    for i, name in enumerate(names):
        n = i + 5
        if n < 6:
            continue
        if n > 6:
            parts.append(lead_in('Table %d shows the data dictionary for the %s table of the %s.'
                                 % (n, name, SYSTEM)))
        parts.append(caption('Table %d' % n))
        parts.append(title('Data Dictionary of %s Table of the %s' % (name, SYSTEM)))
        parts.append(dd_tables[i])
        parts.append(blank())
        parts.append(description('Table %d –' % n, narratives[name]))

    added = ''.join(parts)

    draft, fixed_rows = normalise_table5(draft)

    cut = draft.rindex('<w:sectPr')
    merged = draft[:cut] + added + draft[cut:]

    shutil.copyfile(draft_path, out_path)
    with zipfile.ZipFile(draft_path) as src:
        items = [i for i in src.infolist()]
        with zipfile.ZipFile(out_path, 'w', zipfile.ZIP_DEFLATED) as dst:
            for item in items:
                data = src.read(item.filename)
                if item.filename == DOC:
                    data = merged.encode('utf8')
                dst.writestr(item, data)

    print('tables appended : %d (Tables 6-15)' % (len(names) - 1))
    print('Table 5 rows normalised to 11 pt: %d' % fixed_rows)
    print('other package parts copied      : %d' % (len(items) - 1))
    print('wrote', out_path)


if __name__ == '__main__':
    main(*sys.argv[1:4])
