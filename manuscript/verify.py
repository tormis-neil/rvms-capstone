"""Fail loudly if the documented data dictionary drifts from the migrations."""
import re, glob, sys
from erd_model import TABLES

REPO_ONLY = {"remarks", "status_source", "status_changed_at"}   # vehicles, by design
real = {}
for f in sorted(glob.glob("../backend/database/migrations/*.php")):
    up = open(f).read().split("public function down")[0]
    for m in re.finditer(r"Schema::(create|table)\('(\w+)'.*?function \(Blueprint \$table\) \{(.*?)\n        \}\);", up, re.S):
        kind, tbl, body = m.groups()
        real.setdefault(tbl, [])
        if kind == "create" and "$table->id()" in body: real[tbl].append("id")
        for c in re.findall(r"\$table->(?:\w+)\('(\w+)'", body):
            if c not in real[tbl] and not c.endswith("_unique"): real[tbl].append(c)
        if "rememberToken()" in body and "remember_token" not in real[tbl]: real[tbl].append("remember_token")
        if "softDeletes()" in body and "deleted_at" not in real[tbl]: real[tbl].append("deleted_at")
        if "timestamps()" in body:
            for c in ("created_at", "updated_at"):
                if c not in real[tbl]: real[tbl].append(c)
        # Migrations also REMOVE columns, and a checker that only ever adds them
        # reports a column the database no longer has — which is exactly the kind
        # of quiet wrongness this file exists to prevent. Files are read in name
        # order, so a later drop correctly undoes an earlier add.
        if "dropSoftDeletes()" in body and "deleted_at" in real[tbl]:
            real[tbl].remove("deleted_at")
        for dropped in re.findall(r"dropColumn\(\s*'(\w+)'", body):
            if dropped in real[tbl]: real[tbl].remove(dropped)
        for group in re.findall(r"dropColumn\(\s*\[(.*?)\]", body, re.S):
            for dropped in re.findall(r"'(\w+)'", group):
                if dropped in real[tbl]: real[tbl].remove(dropped)

bad = False
for t in TABLES:
    db = [c for c in real.get(t, []) if not (t == "vehicles" and c in REPO_ONLY)]
    doc = [f[0] for f in TABLES[t]]
    missing = [c for c in db if c not in doc]
    extra = [c for c in doc if c not in db]
    if missing or extra:
        bad = True
        print(f"[DIFF] {t}: in database but undocumented={missing} documented but absent={extra}")
    else:
        print(f"[OK  ] {t:28} {len(doc)} fields")

# --- and the hand-written SQL must agree with the model too --------------
import os
if os.path.exists("rvms-erd.sql"):
    sql = open("rvms-erd.sql").read()
    from erd_model import RELATIONSHIPS
    print()
    for t, fields in TABLES.items():
        m = re.search(r"CREATE TABLE `%s` \((.*?)\n\) ENGINE" % t, sql, re.S)
        if not m:
            bad = True; print(f"[DIFF] rvms-erd.sql is missing table {t}"); continue
        cols = re.findall(r"^\s*`(\w+)`\s+[A-Z]", m.group(1), re.M)
        doc = [f[0] for f in fields]
        miss = [c for c in doc if c not in cols]; extra = [c for c in cols if c not in doc]
        if miss or extra:
            bad = True; print(f"[DIFF] rvms-erd.sql {t}: missing={miss} extra={extra}")
    fks = len(re.findall(r"CONSTRAINT `\w+`\s+FOREIGN KEY", sql))
    if fks != len(RELATIONSHIPS):
        bad = True; print(f"[DIFF] rvms-erd.sql declares {fks} foreign keys, model has {len(RELATIONSHIPS)}")
    else:
        print(f"[OK  ] rvms-erd.sql              {fks} foreign keys, all tables match")

print("\nDIFFERENCES FOUND — fix before regenerating" if bad else "\nALL MATCH")
sys.exit(1 if bad else 0)
