#!/usr/bin/env python3
# sanitize_make_blueprints.py
#
# Usage:
#   python sanitize_make_blueprints.py <input_path> <output_path>
#
# Examples:
#   python sanitize_make_blueprints.py ./blueprints_raw ./blueprints_sanitized
#   python sanitize_make_blueprints.py ./LTL\ Blog-Bot\ v2.1\ [DE].blueprint.json ./out

import argparse
import json
import re
from pathlib import Path
from typing import Any, Dict, List, Tuple, Union

JsonType = Union[Dict[str, Any], List[Any], str, int, float, bool, None]

SENSITIVE_EXACT_KEYS = {
    "__IMTCONN__",   # Make connection id
    "__IMTHOOK__",   # Make webhook/hook id
    "__IMTKEY__",    # sometimes appears
    "__IMTSECRET__", # sometimes appears
}

# Keys that are likely to contain secrets/tokens/passwords/etc.
SENSITIVE_KEY_SUBSTRINGS = (
    "token",
    "secret",
    "password",
    "passwd",
    "apikey",
    "api_key",
    "authorization",
    "auth",
    "bearer",
    "cookie",
    "session",
    "client_secret",
    "private_key",
    "refresh_token",
    "access_token",
    "webhook",
    "hook_url",
)

EMAIL_RE = re.compile(r"([a-zA-Z0-9._%+\-]+)@([a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})")
URL_RE = re.compile(r"https?://[^\s\"'<>]+", re.IGNORECASE)

# Typical Make/Integromat webhook domains/patterns (very common)
MAKE_WEBHOOK_HINTS = (
    "hook.",
    "hooks.",
    "webhook",
    "integromat",
    "make.com",
)

# Keep your public brand links intact
ALLOWED_URL_DOMAINS = (
    "lazytechlab.de",
    "store.lazytechlab.de",
)

def is_allowed_url(url: str) -> bool:
    try:
        # crude domain check, good enough for sanitizing
        return any(d in url for d in ALLOWED_URL_DOMAINS)
    except Exception:
        return False

def redact_string_by_context(key: str, s: str) -> str:
    k = (key or "").lower()

    # If the key clearly suggests secrets -> nuke string
    if any(sub in k for sub in SENSITIVE_KEY_SUBSTRINGS):
        # keep type string, but redact
        return "REDACTED"

    # Redact Make webhook-like URLs unless whitelisted
    def replace_url(m: re.Match) -> str:
        url = m.group(0)
        if is_allowed_url(url):
            return url
        low = url.lower()
        if any(h in low for h in MAKE_WEBHOOK_HINTS):
            return "REDACTED_URL"
        return url

    s2 = URL_RE.sub(replace_url, s)

    # Redact emails (keep format)
    s3 = EMAIL_RE.sub("REDACTED_EMAIL", s2)

    return s3

def redact_value(key: str, value: Any) -> Any:
    # Exact Make keys (connection/hook ids)
    if key in SENSITIVE_EXACT_KEYS:
        # Preserve type: Make usually uses ints here
        if isinstance(value, (int, float)):
            return 0
        return "REDACTED"

    # If key suggests secrets, preserve type but redact
    k = (key or "").lower()
    if any(sub in k for sub in SENSITIVE_KEY_SUBSTRINGS):
        if isinstance(value, (int, float)):
            return 0
        if isinstance(value, bool):
            return value
        if value is None:
            return None
        return "REDACTED"

    # Strings: selectively redact emails + Make webhook URLs
    if isinstance(value, str):
        return redact_string_by_context(key, value)

    return value

def sanitize(obj: JsonType, parent_key: str = "") -> JsonType:
    if isinstance(obj, dict):
        out: Dict[str, Any] = {}
        for k, v in obj.items():
            # First sanitize nested structures
            if isinstance(v, (dict, list)):
                out[k] = sanitize(v, k)
            else:
                out[k] = redact_value(k, v)
        return out

    if isinstance(obj, list):
        return [sanitize(item, parent_key) for item in obj]

    # Primitive at root
    return redact_value(parent_key, obj)

def iter_blueprint_files(input_path: Path) -> List[Path]:
    if input_path.is_file():
        return [input_path]
    return sorted(list(input_path.rglob("*.blueprint.json")) + list(input_path.rglob("*.json")))

def main():
    ap = argparse.ArgumentParser(description="Sanitize Make.com blueprint JSON files for safe GitHub storage.")
    ap.add_argument("input", type=str, help="Input file or directory containing blueprint JSON")
    ap.add_argument("output", type=str, help="Output directory (or output file if input is a file)")
    args = ap.parse_args()

    in_path = Path(args.input).expanduser().resolve()
    out_path = Path(args.output).expanduser().resolve()

    files = iter_blueprint_files(in_path)
    if not files:
        raise SystemExit(f"No JSON files found in: {in_path}")

    # If single file and output is a file path
    output_is_file = in_path.is_file() and out_path.suffix.lower() == ".json"
    if not output_is_file:
        out_path.mkdir(parents=True, exist_ok=True)

    for f in files:
        try:
            data = json.loads(f.read_text(encoding="utf-8"))
        except Exception as e:
            print(f"[SKIP] {f} (invalid json): {e}")
            continue

        sanitized = sanitize(data)

        if output_is_file:
            target = out_path
        else:
            # mirror folder structure if input was a dir
            if in_path.is_dir():
                rel = f.relative_to(in_path)
                target = out_path / rel
            else:
                target = out_path / f.name

            target.parent.mkdir(parents=True, exist_ok=True)

        target.write_text(json.dumps(sanitized, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"[OK] {f.name} -> {target}")

if __name__ == "__main__":
    main()
