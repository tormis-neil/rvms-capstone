"""Turn rvms-erd.sql into the column and foreign-key specs the Workbench manual
prints, so the manual is generated from the schema rather than typed against it.

Writes workbench_specs.json:  {order: [...], tables: {name: {columns, fks}}}
"""
import json
import re

sql = open('rvms-erd.sql').read()
blocks = re.findall(r'CREATE TABLE `(\w+)` \((.*?)\n\) ENGINE', sql, re.S)

out = {'order': [b[0] for b in blocks], 'tables': {}}

for name, body in blocks:
    body = re.sub(r'\n\s{20,}', ' ', body)          # rejoin wrapped definitions
    columns = []
    for line in (l.strip().rstrip(',') for l in body.split('\n')):
        if line.startswith(('PRIMARY KEY', 'UNIQUE KEY', 'KEY', 'CONSTRAINT')):
            continue
        m = re.match(r'`(\w+)`\s+(.+)', line)
        if not m:
            continue
        col, rest = m.group(1), m.group(2)
        rest = re.sub(r"\s*COMMENT\s+'.*'", '', rest).strip()
        not_null = 'NOT NULL' in rest
        typ = rest.replace('NOT NULL', '').replace('NULL', '')
        default = ''
        d = re.search(r"DEFAULT\s+('[^']*'|\S+)", typ)
        if d:
            default, typ = d.group(1), typ[:d.start()]
        typ = typ.replace('AUTO_INCREMENT', '').strip()
        columns.append({
            'name': col,
            'type': typ.replace(' UNSIGNED', ''),
            'pk': col == 'id',
            'nn': not_null,
            'un': 'UNSIGNED' in typ,
            'ai': col == 'id',
            'default': default,
        })

    fks = [
        {'name': f'fk_{name}_{col}', 'column': col, 'ref': ref, 'on_delete': od or 'NO ACTION'}
        for col, ref, od in re.findall(
            r'FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`id`\)(?:\s+ON DELETE (\w+(?: \w+)?))?',
            body)
    ]
    out['tables'][name] = {'columns': columns, 'fks': fks}

json.dump(out, open('workbench_specs.json', 'w'), indent=1)

n_cols = sum(len(t['columns']) for t in out['tables'].values())
n_fks = sum(len(t['fks']) for t in out['tables'].values())
print(f"{len(out['order'])} tables, {n_cols} columns, {n_fks} foreign keys")
