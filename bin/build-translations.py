#!/usr/bin/env python3
"""Build .po/.mo from POT + languages/translations/<locale>.json maps."""
from __future__ import annotations
import json
import sys
from pathlib import Path
import polib

ROOT = Path(__file__).resolve().parents[1]
LANG = ROOT / "languages"
POT = LANG / "seofyme-seo.pot"
TRANS = LANG / "translations"

# WordPress text domain file naming: {domain}-{locale}.po
DOMAIN = "seofyme-seo"

LOCALES = {
    "de_DE": "German",
    "fr_FR": "French",
    "es_ES": "Spanish",
    "it_IT": "Italian",
    "nl_NL": "Dutch",
    "pt_PT": "Portuguese",
    "pl_PL": "Polish",
    "sv_SE": "Swedish",
    "da_DK": "Danish",
    "fi": "Finnish",
    "el": "Greek",
    "cs_CZ": "Czech",
    "ro_RO": "Romanian",
    "hu_HU": "Hungarian",
    "bg_BG": "Bulgarian",
    "hr": "Croatian",
    "sk_SK": "Slovak",
    "sl_SI": "Slovenian",
    "lt_LT": "Lithuanian",
    "lv": "Latvian",
    "et": "Estonian",
}

def build_locale(locale: str, name: str) -> None:
    map_path = TRANS / f"{locale}.json"
    if not map_path.exists():
        print(f"SKIP {locale}: missing {map_path.name}")
        return
    translations = json.loads(map_path.read_text(encoding="utf-8"))
    pot = polib.pofile(str(POT))
    po = polib.POFile()
    po.metadata = {
        "Project-Id-Version": "Seofyme SEO 0.1.0",
        "Report-Msgid-Bugs-To": "https://github.com/seofyme/wordpress-plugin",
        "Language": locale,
        "Language-Team": f"{name}",
        "MIME-Version": "1.0",
        "Content-Type": "text/plain; charset=UTF-8",
        "Content-Transfer-Encoding": "8bit",
        "X-Domain": DOMAIN,
        "Plural-Forms": plural_forms(locale),
    }
    missing = 0
    for entry in pot:
        if not entry.msgid:
            continue
        ne = polib.POEntry(
            msgid=entry.msgid,
            msgstr=translations.get(entry.msgid, ""),
            msgctxt=entry.msgctxt,
            comment=entry.comment,
            tcomment=entry.tcomment,
            occurrences=entry.occurrences,
        )
        if not ne.msgstr:
            missing += 1
        po.append(ne)
    po_path = LANG / f"{DOMAIN}-{locale}.po"
    mo_path = LANG / f"{DOMAIN}-{locale}.mo"
    po.save(str(po_path))
    po.save_as_mofile(str(mo_path))
    print(f"OK {locale}: {len(po)} entries, {missing} missing -> {po_path.name}")

def plural_forms(locale: str) -> str:
    # Common gettext plural forms for EU locales
    mapping = {
        "de_DE": "nplurals=2; plural=(n != 1);",
        "fr_FR": "nplurals=2; plural=(n > 1);",
        "es_ES": "nplurals=2; plural=(n != 1);",
        "it_IT": "nplurals=2; plural=(n != 1);",
        "nl_NL": "nplurals=2; plural=(n != 1);",
        "pt_PT": "nplurals=2; plural=(n != 1);",
        "pl_PL": "nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);",
        "sv_SE": "nplurals=2; plural=(n != 1);",
        "da_DK": "nplurals=2; plural=(n != 1);",
        "fi": "nplurals=2; plural=(n != 1);",
        "el": "nplurals=2; plural=(n != 1);",
        "cs_CZ": "nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;",
        "ro_RO": "nplurals=3; plural=(n==1 ? 0 : (n==0 || (n%100>0 && n%100<20)) ? 1 : 2);",
        "hu_HU": "nplurals=2; plural=(n != 1);",
        "bg_BG": "nplurals=2; plural=(n != 1);",
        "hr": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);",
        "sk_SK": "nplurals=3; plural=(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2;",
        "sl_SI": "nplurals=4; plural=(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3);",
        "lt_LT": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2);",
        "lv": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2);",
        "et": "nplurals=2; plural=(n != 1);",
    }
    return mapping.get(locale, "nplurals=2; plural=(n != 1);")

def main() -> int:
    locales = sys.argv[1:] or list(LOCALES)
    for loc in locales:
        build_locale(loc, LOCALES.get(loc, loc))
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
