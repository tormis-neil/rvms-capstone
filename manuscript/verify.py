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

print("\nDIFFERENCES FOUND — fix erd_model.py before regenerating" if bad else "\nALL MATCH")
sys.exit(1 if bad else 0)
