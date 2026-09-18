# PS Industrial — Claude Code Project Context

Read and follow @AGENTS.md first.

This repository is an ongoing reengineering project migrating a legacy
PHP/MySQL website to WordPress.

The project has already completed several phases. Do not restart the
architecture or redesign decisions from scratch unless evidence shows an
actual problem.

## Current project

Legacy application:

`/legacy`

New WordPress:

`/wordpress`

Project documentation:

`/docs`

The legacy system is immutable.

NEVER modify `/legacy`.

## Current stack

Local environment:
- Windows
- Laragon
- PHP 8.4
- MySQL 8
- WordPress
- Git / GitHub

Production legacy currently runs PHP 7.4.

New WordPress targets PHP 8.4.

Hosting:
- shared hosting
- Hepsia
- FTP deployment
- no SSH

## WordPress architecture already approved

Product:
- CPT `psi_producto`

Product categories:
- hierarchical taxonomy

Brands:
- taxonomy
- logo stored as term metadata / WordPress attachment ID

Institutional content:
- WordPress Pages

SEO landing pages:
- WordPress Pages

Media:
- WordPress Media Library

Product images / gallery:
- attachment IDs

Technical PDFs:
- attachment IDs

Custom fields:
- native WordPress controls/metaboxes
- no ACF

Frontend:
- custom `psindustrial` theme
- no page builder

Application/content logic:
- `psindustrial-core` plugin

SEO:
- architecture prepared for Yoast Free
- Yoast must not become a functional dependency of the application

Legacy URLs:
- hybrid strategy
- validated important legacy `.php` URLs may be preserved
- new content uses clean WordPress URLs
- do not create physical PHP files to emulate legacy URLs

## Important existing directories

Theme:

`/wordpress/wp-content/themes/psindustrial`

Core plugin:

`/wordpress/wp-content/plugins/psindustrial-core`

## Completed phases

The following work is already completed:

1. Legacy reverse engineering.
2. Database/content reconciliation.
3. WordPress architecture.
4. WordPress bootstrap.
5. Content administration.
6. Initial legacy importer implementation.
7. Importer subset test.
8. Full migration dry-run.

Do not redo these phases unless explicitly requested.

## Current importer state

A safe local importer already exists.

It includes:

- admin interface
- dry-run
- private manifest
- batching
- conflict handling
- conservative recovery
- idempotency protections

Subset test:

- 15 objects created
- second execution: 15 UNCHANGED
- no duplicates
- two REVIEW cases were intentionally not imported

Current WordPress UI shows only a few subset-test products.
THIS IS EXPECTED.

The full catalog has NOT been imported.

Full dry-run:

- 2,399 source objects analyzed
- 15 UNCHANGED
- 435 SKIP
- 1,949 REVIEW
- 0 errors

Do NOT run a complete real import without explicit user authorization.

## Current priority

The next phase is NOT to rewrite the importer.

The next problem is to analyze and reduce the 1,949 REVIEW cases safely.

We need to classify them into:

- cases safely approvable from existing evidence;
- media/relationship records that belong to approved entities;
- duplicates requiring deterministic merge rules;
- legitimate SKIP cases;
- genuinely ambiguous cases requiring human decisions.

The goal is to reduce manual review without inventing data.

## Canonical documentation

Read these first when onboarding:

@docs/legacy-analysis/00-executive-summary.md
@docs/migration/00-migration-readiness-summary.md
@docs/architecture/00-wordpress-architecture-summary.md
@docs/implementation/10-content-admin-summary.md
@docs/implementation/20-importer-summary.md
@docs/implementation/28-full-dry-run-report.md
@docs/implementation/29-importer-known-issues.md

Other supporting information exists throughout `/docs`.

Do not load every CSV and report unnecessarily. Read them when relevant
to the task.

## Important migration sources

Important canonical datasets include:

- `/docs/migration/product-master.csv`
- `/docs/migration/category-master.csv`
- `/docs/migration/brand-master.csv`
- `/docs/migration/content-master.csv`
- `/docs/migration/media-master.csv`
- `/docs/migration/product-media-relations.csv`
- `/docs/migration/static-product-supplement.csv`
- `/docs/migration/canonical-candidate-groups.csv`
- `/docs/migration/evidence-matrix.md`
- `/docs/migration/manual-decisions-required.md`
- `/docs/seo/url-master.csv`

Importer review report:

`/docs/implementation/importer-reports/all-review.csv`

## Data safety

Never assume a SQL row equals one real product.

Known legacy issues include:

- duplicated product records;
- empty records;
- test records;
- static product pages without strong SQL equivalents;
- missing brands;
- contradictory relationships;
- missing media;
- uncertain entity merges.

Do not resolve uncertain manufacturer/category relationships merely
from semantic similarity.

Use confidence/evidence.

## Git workflow

Branches:

`main`
Production-ready.

`develop`
Integration/staging.

`feature/*`
Active development.

Never commit directly to `main`.

Before editing anything:

1. inspect current branch;
2. inspect `git status`;
3. understand existing changes.

Never discard, reset or overwrite existing user changes.

Do not commit or push unless explicitly asked.

## Security

Never expose or commit:

- passwords
- database credentials
- FTP credentials
- wp-config.php
- SQL production dumps
- secrets
- private migration data

The legacy code contains at least one credential previously identified
for rotation. Do not reproduce it.

## Working style

For significant tasks:

1. inspect evidence;
2. explain the plan;
3. make focused changes;
4. run relevant tests;
5. run syntax/lint checks;
6. run regression tests;
7. verify `/legacy` remains unchanged;
8. inspect git diff/status;
9. summarize changes and remaining risks.

Do not silently make architectural changes.

If existing architecture appears wrong, explain the evidence and stop
for review before replacing it.

Prefer simple WordPress-native solutions.

Avoid frameworks and unnecessary dependencies.

## Current stop rule

Do NOT perform a full real legacy import.

Do NOT publish migrated content.

Do NOT deploy to staging or production.

These actions require explicit authorization.