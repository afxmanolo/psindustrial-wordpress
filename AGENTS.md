# PS Industrial WordPress Migration

## Project Purpose

This project migrates an existing custom PHP/MySQL website to WordPress.

The current website is a legacy custom CMS written in:

- PHP
- MySQL
- JavaScript
- HTML/CSS

The new platform will use WordPress while preserving the existing
frontend design, content, catalog structure and SEO value.

The existing legacy application is located in:

/legacy

The future WordPress implementation is located in:

/wordpress

Documentation generated during analysis and migration must be stored in:

/docs


# Primary Objectives

1. Preserve the current frontend visual identity.
2. Preserve existing SEO value.
3. Replace the legacy CMS/backoffice with WordPress.
4. Simplify content administration.
5. Preserve products, categories, brands, images and technical PDFs.
6. Reduce duplicated PHP pages and templates.
7. Produce maintainable and secure code.
8. Build a repeatable migration process.
9. Maintain staging and production deployment through GitHub.


# Legacy System

The /legacy directory contains the reference implementation of the
current production website.

IMPORTANT:

DO NOT modify files inside /legacy unless explicitly instructed.

Treat the legacy application as a functional specification.

Never refactor legacy files merely to make them cleaner.

Before replacing legacy behavior, first understand and document it.

If behavior is unclear, document the uncertainty rather than guessing.


# Legacy Database

The current legacy database includes concepts such as:

- productos
- categorias
- marcas
- files
- users
- permissions

The SQL database dump may exist locally under:

/legacy/database/

Database dumps are intentionally excluded from Git.

Never commit production database dumps or credentials.


# WordPress Architecture

The new site must follow this separation:

## Theme

/wordpress/wp-content/themes/psindustrial

The theme is responsible for:

- presentation
- HTML structure
- frontend templates
- responsive behavior
- CSS
- frontend JavaScript
- accessibility
- visual compatibility with the legacy frontend

Do NOT place business logic in the theme.


## Core Plugin

/wordpress/wp-content/plugins/psindustrial-core

The plugin is responsible for:

- product content model
- categories
- brands
- brand logos
- technical datasheets
- product metadata
- migrations
- roles and capabilities
- URL compatibility
- legacy redirects
- reusable application logic

Business logic belongs in this plugin rather than the theme.


# Content Model

Expected WordPress model:

Product:
Custom Post Type

Category:
Hierarchical taxonomy

Brand:
Taxonomy with associated logo/media metadata

Technical datasheets:
WordPress Media attachments associated with products

Images:
WordPress Media attachments

Videos:
Product metadata or approved WordPress representation


# SEO Requirements

SEO preservation is a critical project requirement.

Do not change existing public URLs without analysis.

Before modifying URLs:

1. identify the legacy URL;
2. identify current content;
3. determine whether the URL should be preserved;
4. document proposed mapping;
5. create a 301 redirect when necessary.

Never silently remove a public legacy URL.

Preserve where appropriate:

- page titles
- meta descriptions
- canonical URLs
- H1 structure
- content
- internal links
- image alt text
- indexability
- HTTP status codes

Maintain the URL inventory under:

/docs/seo/


# Security Rules

Never commit:

- passwords
- FTP credentials
- database credentials
- API secrets
- private keys
- production wp-config.php
- production database dumps

Use WordPress APIs whenever possible.

All WordPress code must follow appropriate security practices including:

- sanitization
- escaping
- nonces
- capabilities
- prepared SQL statements

Do not modify WordPress core.


# Database Rules

Do not directly edit the production database.

Do not synchronize the local database directly to production.

Schema or data transformations must use reproducible migration logic.

Migration scripts must be safe to run intentionally and must document
their expected input and output.


# Git Rules

Primary branches:

main
Production-ready code.

develop
Integration and staging.

Feature work:
feature/<descriptive-name>

Examples:

feature/product-model
feature/brand-taxonomy
feature/frontend-header
feature/product-importer


Never commit directly to main unless explicitly requested.

Keep commits focused and descriptive.


# Documentation

Important discoveries must be documented.

Use:

/docs/legacy-analysis
/docs/architecture
/docs/migration
/docs/seo
/docs/testing

Do not rely only on chat history for architectural decisions.


# Coding Principles

Prefer simple WordPress-native solutions.

Avoid unnecessary frameworks.

Avoid page builders unless explicitly requested.

Avoid unnecessary third-party plugins.

Prefer custom, maintainable code when the requirement is small.

Do not duplicate code.

Do not introduce abstractions without a clear purpose.

Preserve frontend behavior unless the change is intentional.

The PS Industrial frontend must preserve the legacy site's visual identity,
structure and composition with high fidelity. Modernizing implementation does
not mean redesigning. Significant visual changes must be justified and approved
before implementation.


# Current Project Phase

The project initially begins in analysis mode.

During the legacy analysis phase:

DO NOT implement the WordPress migration.

DO NOT refactor the legacy system.

DO NOT delete legacy files.

DO NOT create speculative functionality.

First understand and document the existing application.


# Before Making Significant Changes

Before implementing a significant feature:

1. inspect relevant legacy behavior;
2. inspect existing documentation;
3. describe the intended implementation;
4. identify possible SEO impact;
5. identify migration impact;
6. implement;
7. test;
8. update documentation.


# Definition of Done

A migrated feature is not complete until:

- functionality works;
- frontend behavior is verified;
- administration works;
- permissions are correct;
- security has been reviewed;
- SEO impact has been reviewed;
- legacy data migration has been verified;
- documentation has been updated.
