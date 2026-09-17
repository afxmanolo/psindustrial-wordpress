# PS Industrial WordPress

Reengineering and migration of the existing PS Industrial custom
PHP/MySQL CMS to WordPress.

## Project Structure

legacy/
Reference copy of the current production application.

wordpress/
New WordPress implementation.

docs/
Architecture, analysis, migration, SEO and testing documentation.

.github/
CI/CD workflows.

## Branches

main
Production.

develop
Staging/integration.

feature/*
Individual development work.

## Local Environment

Laragon / Windows.

Legacy runtime:
PHP 7.4

New WordPress target runtime:
PHP 8.4

Production PHP must not be changed until the new WordPress site has
been validated in staging.

## Important

The legacy application must not be modified during the initial
analysis phase.

Production credentials and database dumps must never be committed.