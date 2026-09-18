#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""PS Industrial — local PDF sanitization tool for the Group A migration decision.

THIS IS A LOCAL MIGRATION TOOL, NOT A WORDPRESS RUNTIME DEPENDENCY.
It is never loaded by the `psindustrial-core` plugin, never deployed to staging or
production, and requires nothing beyond a developer's local Python + pikepdf. See
tools/pdf-sanitizer/README.md.

What it does, in order, for every entry in group_a_sanitization of
docs/implementation/pdf-security-review/pdf-approvals.json:

 1. verifies the SHA-256 of the CURRENT file under legacy/public/<legacy_path> against
    the approved source_sha256; refuses to process on any mismatch;
 2. opens the file READ-ONLY with pikepdf (never writes back to the source path);
 3. removes the /EmbeddedFile Filespec reachable from /Names/EmbeddedFiles (the exact
    object identified and approved in 02-embedded-files-analysis.md) plus the now-empty
    /Names/EmbeddedFiles / /Names tree entries it leaves behind;
 4. saves the result to a NEW path in the private working directory (never /legacy);
 5. re-hashes the ORIGINAL source file and asserts it is byte-identical to before —
    proof the legacy file was never touched;
 6. runs the post-sanitization validation suite (pages, no /EmbeddedFile, no /Filespec
    with /EF, no dangerous actions, valid PDF per the importer's own regex, extractable
    text equivalent via pdftotext);
 7. writes docs/implementation/pdf-security-review/sanitization-audit.json — the
    auditable record the PHP side (PdfApprovals) consumes. Only entries with
    status == "SANITIZED_OK" are ever honored by the importer.

Any validation failure marks that entry KEEP_REVIEW in the audit record and the
importer will never substitute it — this script never relaxes a check to force a pass.

Usage (from the repository root, with pikepdf installed — `pip install -r
tools/pdf-sanitizer/requirements.txt`):

    python tools/pdf-sanitizer/sanitize.py [--dry-run]

--dry-run runs every check and prints what would happen without writing anything.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import re
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

import pikepdf

SANITIZER_VERSION = "1.0.0"
RULE_ID = "PDF-SANITIZE-EMBEDDEDFILE-V1"

REPO_ROOT = Path(__file__).resolve().parents[2]
LEGACY_PUBLIC = REPO_ROOT / "legacy" / "public"
APPROVALS_PATH = REPO_ROOT / "docs" / "implementation" / "pdf-security-review" / "pdf-approvals.json"
AUDIT_PATH = REPO_ROOT / "docs" / "implementation" / "pdf-security-review" / "sanitization-audit.json"

# Same private working directory the PHP importer already uses for everything else it
# must keep outside the webroot, outside /legacy and outside git (Storage::root()'s
# default: dirname(project, 2) . '/psindustrial-importer-private'). Reusing it — rather
# than inventing a second private location — is the explicit instruction for this phase.
PRIVATE_ROOT = REPO_ROOT.parent.parent / "psindustrial-importer-private"
SANITIZED_DIR = PRIVATE_ROOT / "pdf-sanitized"

# The EXACT regex the importer's Media::file_valid() runs against raw PDF bytes
# (wordpress/wp-content/plugins/psindustrial-core/includes/Media.php). Reproduced here
# ONLY to prove, objectively, that the sanitized output would pass it — never to change
# what that PHP code does.
IMPORTER_PDF_REGEX = re.compile(rb"/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b", re.IGNORECASE)


def sha256_file(path: Path) -> str:
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def extract_text(path: Path) -> bytes:
    try:
        r = subprocess.run(["pdftotext", str(path), "-"], capture_output=True, check=True)
        return r.stdout
    except FileNotFoundError:
        return b""  # pdftotext not installed locally; text-equivalence check is skipped, not faked.


def _collect_name_tree_leaves(node) -> list[str]:
    """Walks a PDF name tree that may be flat (/Names array) or nested (/Kids), purely
    to list the declared names being removed for the audit record — never evaluates
    anything. Both shapes are valid per the PDF spec; some generators (as seen here) emit
    a single-level /Kids tree even for one entry."""
    out = []
    if node is None:
        return out
    if "/Kids" in node:
        for kid in node["/Kids"]:
            out.extend(_collect_name_tree_leaves(kid))
        return out
    arr = node.get("/Names")
    if arr is not None:
        items = list(arr)
        for i in range(0, len(items) - 1, 2):
            try:
                out.append(str(items[i]))
            except Exception:
                out.append("(unreadable name)")
    return out


def remove_embedded_files(pdf: pikepdf.Pdf) -> list[str]:
    """Removes every /Filespec reachable from /Names/EmbeddedFiles (flat or nested via
    /Kids). Returns the declared name(s) removed, for the audit record. Structural edit
    only — never touches page content, fonts, or images."""
    root = pdf.Root
    names = root.get("/Names")
    if names is None or "/EmbeddedFiles" not in names:
        return []
    removed = _collect_name_tree_leaves(names["/EmbeddedFiles"])
    del names["/EmbeddedFiles"]
    if len(names.keys()) == 0:
        del root["/Names"]
    return removed


def structural_scan(pdf: pikepdf.Pdf) -> dict:
    """Re-derives the same 'dangerous action' signals used in the security audit
    (02-embedded-files-analysis.md), read-only, for the post-sanitization report."""
    root = pdf.Root
    has_open_action = "/OpenAction" in root
    has_acroform = "/AcroForm" in root
    has_names_js = bool(root.get("/Names") and "/JavaScript" in (root.get("/Names") or {}))
    has_catalog_aa = "/AA" in root
    names = root.get("/Names")
    embedded_count = 0
    if names and "/EmbeddedFiles" in names:
        embedded_count = len(_collect_name_tree_leaves(names["/EmbeddedFiles"]))
    # Belt-and-suspenders: also scan every object for an orphaned /Filespec with /EF,
    # in case one exists outside the Names tree (annotation-attached).
    for obj in pdf.objects:
        try:
            if isinstance(obj, pikepdf.Dictionary) and obj.get("/Type") == pikepdf.Name("/Filespec") and "/EF" in obj:
                embedded_count += 1
        except Exception:
            continue
    return {
        "has_OpenAction": has_open_action,
        "has_AcroForm": has_acroform,
        "has_Names_JavaScript": has_names_js,
        "has_Catalog_AA": has_catalog_aa,
        "embedded_file_count": embedded_count,
    }


def sanitize_one(entry: dict, dry_run: bool) -> dict:
    legacy_path = entry["legacy_path"]
    approved_sha256 = entry["source_sha256"]
    # legacy_path is always POSIX-style ("a/b/c"); join it segment by segment so it
    # resolves correctly regardless of host OS path separator.
    source_full = LEGACY_PUBLIC.joinpath(*legacy_path.split("/"))
    # Defense in depth, mirroring Sources::safe() on the PHP side: refuse anything that
    # would resolve outside legacy/public, even though legacy_path here always comes
    # from the curated, human-reviewed pdf-approvals.json, never from user input.
    try:
        resolved = source_full.resolve()
        resolved.relative_to(LEGACY_PUBLIC.resolve())
    except (ValueError, OSError):
        record = {
            "source_legacy_path": legacy_path,
            "source_sha256_approved": approved_sha256,
            "sanitization_rule_id": RULE_ID,
            "sanitizer_version": SANITIZER_VERSION,
            "generated_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
            "status": "KEEP_REVIEW",
            "failure_reason": "UNSAFE_PATH_OUTSIDE_LEGACY_PUBLIC",
        }
        return record
    record = {
        "source_legacy_path": legacy_path,
        "source_sha256_approved": approved_sha256,
        "sanitization_rule_id": RULE_ID,
        "sanitizer_version": SANITIZER_VERSION,
        "generated_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
    }

    # 1) Verify source exists and its hash matches the approved hash EXACTLY.
    if not source_full.is_file():
        record.update(status="KEEP_REVIEW", failure_reason="SOURCE_FILE_NOT_FOUND")
        return record
    current_sha256 = sha256_file(source_full)
    record["source_sha256_actual"] = current_sha256
    if current_sha256 != approved_sha256:
        record.update(status="KEEP_REVIEW", failure_reason="SOURCE_HASH_MISMATCH_APPROVAL_DOES_NOT_APPLY")
        return record

    # 2) Open read-only; never write back to source_full.
    try:
        pdf = pikepdf.open(source_full)
    except Exception as exc:
        record.update(status="KEEP_REVIEW", failure_reason=f"SOURCE_OPEN_FAILED:{exc}")
        return record

    pages_before = len(pdf.pages)
    text_before = extract_text(source_full)

    removed_names = remove_embedded_files(pdf)

    SANITIZED_DIR.mkdir(parents=True, exist_ok=True)
    out_path = SANITIZED_DIR / f"{approved_sha256}.pdf"
    tmp_path = SANITIZED_DIR / f"{approved_sha256}.pdf.tmp"

    if dry_run:
        record.update(status="DRY_RUN", removed_embedded_names=removed_names, would_write=str(out_path))
        pdf.close()
        return record

    pdf.save(tmp_path)
    pdf.close()

    # 5) Prove the legacy original was never touched.
    post_hash = sha256_file(source_full)
    if post_hash != approved_sha256:
        tmp_path.unlink(missing_ok=True)
        record.update(status="KEEP_REVIEW", failure_reason="SOURCE_FILE_CHANGED_DURING_PROCESSING_ABORTED")
        return record

    # 6) Post-sanitization validation suite. Any failure => KEEP_REVIEW, tmp discarded.
    checks = {}
    sanitized_bytes = tmp_path.read_bytes()
    checks["valid_pdf_header"] = sanitized_bytes[:5] == b"%PDF-"
    try:
        reopened = pikepdf.open(tmp_path)
        pages_after = len(reopened.pages)
        checks["pages_preserved"] = pages_after == pages_before
        scan = structural_scan(reopened)
        checks["embedded_file_absent"] = scan["embedded_file_count"] == 0
        checks["no_open_action"] = not scan["has_OpenAction"]
        checks["no_acroform"] = not scan["has_AcroForm"]
        checks["no_names_javascript"] = not scan["has_Names_JavaScript"]
        checks["no_catalog_aa"] = not scan["has_Catalog_AA"]
        reopened.close()
    except Exception as exc:
        checks["reopen_ok"] = False
        checks["reopen_error"] = str(exc)
        pages_after = None

    checks["importer_regex_no_match"] = not bool(IMPORTER_PDF_REGEX.search(sanitized_bytes))
    text_after = extract_text(out_path if out_path.exists() else tmp_path)
    if text_before and text_after:
        checks["text_extraction_equivalent"] = text_before == text_after
    else:
        checks["text_extraction_equivalent"] = None  # pdftotext unavailable; not faked as pass.

    all_ok = all(v is True for v in checks.values() if v is not None)

    record.update(
        pages_before=pages_before,
        pages_after=pages_after,
        removed_embedded_names=removed_names,
        validation=checks,
    )

    if not all_ok:
        tmp_path.unlink(missing_ok=True)
        failed = [k for k, v in checks.items() if v is False]
        record.update(status="KEEP_REVIEW", failure_reason="VALIDATION_FAILED:" + ",".join(failed))
        return record

    tmp_path.replace(out_path)
    sanitized_sha256 = sha256_file(out_path)
    record.update(
        status="SANITIZED_OK",
        sanitized_file=out_path.name,
        sanitized_sha256=sanitized_sha256,
        size_before=source_full.stat().st_size,
        size_after=out_path.stat().st_size,
    )
    return record


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--dry-run", action="store_true", help="Run all checks; write nothing.")
    args = parser.parse_args()

    approvals = json.loads(APPROVALS_PATH.read_text(encoding="utf-8"))
    if approvals.get("scope") != "PDF_SECURITY_REVIEW_APPROVED":
        print("REFUSED: pdf-approvals.json scope is not PDF_SECURITY_REVIEW_APPROVED", file=sys.stderr)
        return 1

    results = []
    for entry in approvals["group_a_sanitization"]:
        rec = sanitize_one(entry, args.dry_run)
        results.append(rec)
        print(f"{entry['legacy_path']}: {rec['status']}" + (f" ({rec.get('failure_reason')})" if rec.get("failure_reason") else ""))

    if not args.dry_run:
        AUDIT_PATH.write_text(
            json.dumps(
                {
                    "generated_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
                    "sanitizer_version": SANITIZER_VERSION,
                    "sanitized_dir": str(SANITIZED_DIR),
                    "records": results,
                },
                indent=2,
                ensure_ascii=False,
            )
            + "\n",
            encoding="utf-8",
        )
        print(f"\nwrote {AUDIT_PATH}")

    ok = sum(1 for r in results if r["status"] == "SANITIZED_OK")
    print(f"\n{ok}/{len(results)} sanitized successfully.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
